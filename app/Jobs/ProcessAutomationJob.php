<?php

namespace App\Jobs;

use App\Automation\ActionSkippedException;
use App\Automation\AutomationActionContract;
use App\Automation\ConditionEvaluator;
use App\Automation\Actions\AddTagAction;
use App\Automation\Actions\ChangeStatusAction;
use App\Automation\Actions\CreatePortalAccessAction;
use App\Automation\Actions\NotifyAdminAction;
use App\Automation\Actions\SendEmailAction;
use App\Automation\Actions\SendInternalEmailAction;
use App\Automation\Actions\SendSmsAction;
use App\Models\AutomationLog;
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
        public readonly bool   $dryRun = false,
        public readonly string $source = 'automation',
    ) {}

    public function handle(ConditionEvaluator $evaluator): void
    {
        if ($this->singleRuleId !== null) {
            $rule = AutomationRule::where('is_active', true)->find($this->singleRuleId);
            if ($rule && $evaluator->evaluate($rule->conditions ?? [], $this->context)) {
                $actionsResult = [];
                foreach ($rule->actions ?? [] as $action) {
                    $actionsResult[] = $this->executeAction($action);
                }
                $this->writeLog($rule, $actionsResult);
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
                self::dispatch($this->triggerEvent, $this->context, $rule->id, $this->dryRun, $this->source)
                    ->delay(now()->addMinutes($delay));
                continue;
            }

            $actionsResult = [];
            foreach ($rule->actions ?? [] as $action) {
                $actionsResult[] = $this->executeAction($action);
            }
            $this->writeLog($rule, $actionsResult);
        }
    }

    private function executeAction(array $action): array
    {
        $type   = $action['type'] ?? null;
        $class  = self::ACTION_MAP[$type] ?? null;
        $start  = microtime(true);

        if (! $class) {
            return ['type' => $type, 'status' => 'skipped', 'message' => 'Unknown action type'];
        }

        // In dry-run mode, skip real execution
        if ($this->dryRun) {
            return [
                'type'        => $type,
                'status'      => 'dry_run',
                'message'     => 'Dry run — action not executed',
                'duration_ms' => 0,
            ];
        }

        try {
            app($class)->execute($action, $this->context, $this->triggerEvent);
            return [
                'type'        => $type,
                'status'      => 'ok',
                'duration_ms' => (int) ((microtime(true) - $start) * 1000),
            ];
        } catch (ActionSkippedException $e) {
            Log::info("AutomationRule action skipped [{$type}]: " . $e->getMessage(), [
                'context' => $this->context,
                'action'  => $action,
            ]);
            return [
                'type'        => $type,
                'status'      => 'skipped',
                'message'     => $e->getMessage(),
                'duration_ms' => (int) ((microtime(true) - $start) * 1000),
            ];
        } catch (\Throwable $e) {
            Log::error("AutomationRule action failed [{$type}]: " . $e->getMessage(), [
                'context' => $this->context,
                'action'  => $action,
            ]);
            return [
                'type'        => $type,
                'status'      => 'error',
                'message'     => $e->getMessage(),
                'duration_ms' => (int) ((microtime(true) - $start) * 1000),
            ];
        }
    }

    private function writeLog(AutomationRule $rule, array $actionsResult): void
    {
        $statuses   = collect($actionsResult)->pluck('status');
        $hasError   = $statuses->contains('error');
        $hasSkipped = $statuses->contains('skipped');
        $hasOk      = $statuses->contains('ok');

        $status = $this->dryRun
            ? 'test'
            : match (true) {
                $hasOk && ! $hasError && ! $hasSkipped => 'success',
                $hasError && ! $hasOk                  => 'failed',
                default                                => 'partial',
            };

        AutomationLog::create([
            'automation_rule_id' => $rule->id,
            'trigger_event'      => $this->triggerEvent,
            'context'            => $this->context,
            'actions_executed'   => $actionsResult,
            'lead_id'            => $this->context['lead_id'] ?? null,
            'client_id'          => $this->context['client_id'] ?? null,
            'status'             => $status,
            'source'             => $this->source,
            'executed_at'        => now(),
        ]);
    }
}
