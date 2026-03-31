<?php

namespace App\Jobs;

use App\Automation\AutomationActionContract;
use App\Automation\ConditionEvaluator;
use App\Automation\Actions\AddTagAction;
use App\Automation\Actions\ChangeStatusAction;
use App\Automation\Actions\CreatePortalAccessAction;
use App\Automation\Actions\NotifyAdminAction;
use App\Automation\Actions\SendEmailAction;
use App\Automation\Actions\SendInternalEmailAction;
use App\Automation\Actions\SendSmsAction;
use App\Models\AutomationRule;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Evaluates active AutomationRule records for a given trigger event and
 * executes matching actions via dedicated action classes.
 *
 * Dispatched by AutomationEventListener after relevant model changes.
 */
class ProcessAutomationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    /** @var array<string, class-string<AutomationActionContract>> */
    private const ACTION_MAP = [
        'send_email'           => SendEmailAction::class,
        'send_internal_email'  => SendInternalEmailAction::class,
        'send_sms'             => SendSmsAction::class,
        'notify_admin'         => NotifyAdminAction::class,
        'add_tag'              => AddTagAction::class,
        'change_status'        => ChangeStatusAction::class,
        'create_portal_access' => CreatePortalAccessAction::class,
    ];

    public function __construct(
        public readonly string $triggerEvent,
        public readonly array  $context,
        public readonly ?int   $singleRuleId = null,
    ) {}

    public function handle(ConditionEvaluator $evaluator): void
    {
        if ($this->singleRuleId !== null) {
            $rule = AutomationRule::where('is_active', true)->find($this->singleRuleId);
            if ($rule && $evaluator->evaluate($rule->conditions ?? [], $this->context)) {
                foreach ($rule->actions ?? [] as $action) {
                    $this->executeAction($action);
                }
            }
            return;
        }

        $rules = AutomationRule::where('trigger_event', $this->triggerEvent)
            ->where('is_active', true)
            ->get();

        foreach ($rules as $rule) {
            if (! $evaluator->evaluate($rule->conditions ?? [], $this->context)) {
                continue;
            }

            $delay = (int) ($rule->delay_minutes ?? 0);

            if ($delay > 0) {
                self::dispatch($this->triggerEvent, $this->context, $rule->id)
                    ->delay(now()->addMinutes($delay));
                continue;
            }

            foreach ($rule->actions ?? [] as $action) {
                $this->executeAction($action);
            }
        }
    }

    private function executeAction(array $action): void
    {
        $type  = $action['type'] ?? null;
        $class = self::ACTION_MAP[$type] ?? null;

        if (! $class) {
            return;
        }

        try {
            app($class)->execute($action, $this->context, $this->triggerEvent);
        } catch (\Throwable $e) {
            Log::error("AutomationRule action failed [{$type}]: " . $e->getMessage(), [
                'context' => $this->context,
                'action'  => $action,
            ]);
        }
    }
}
