<?php

namespace Tests\Feature;

use App\Automation\ConditionEvaluator;
use App\Jobs\ProcessAutomationJob;
use App\Mail\NewLeadMail;
use App\Models\AutomationRule;
use App\Models\Client;
use App\Models\Lead;
use App\Models\PipelineStage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class AutomationActionTest extends TestCase
{
    use RefreshDatabase;

    private Client $client;
    private PipelineStage $stage;

    protected function setUp(): void
    {
        parent::setUp();
        Queue::fake(); // prevent listener from dispatching jobs during data setup
        Mail::fake();

        $this->stage  = PipelineStage::create(['name' => 'New', 'slug' => 'new', 'order' => 1]);
        $this->client = Client::create([
            'company_name'          => 'Acme Ltd',
            'primary_contact_email' => 'client@acme.com',
            'notify_email_marketing' => true,
        ]);
    }

    private function makeLead(): Lead
    {
        return Lead::create([
            'title'             => 'Test Lead',
            'client_id'         => $this->client->id,
            'pipeline_stage_id' => $this->stage->id,
            'source'            => 'contact_form',
        ]);
    }

    private function runJob(string $trigger, array $context): void
    {
        $job = new ProcessAutomationJob($trigger, $context);
        $job->handle(new ConditionEvaluator());
    }

    public function test_send_email_action_queues_new_lead_mailable(): void
    {
        $lead = $this->makeLead();

        AutomationRule::create([
            'name'          => 'Welcome Email',
            'trigger_event' => 'lead.created',
            'conditions'    => [],
            'actions'       => [
                ['type' => 'send_email', 'recipient' => 'client', 'template' => 'new_lead'],
            ],
            'is_active' => true,
        ]);

        $this->runJob('lead.created', [
            'lead_id'   => $lead->id,
            'client_id' => $this->client->id,
        ]);

        Mail::assertQueued(NewLeadMail::class);
    }

    public function test_send_internal_email_action_runs_without_exception(): void
    {
        $lead = $this->makeLead();

        AutomationRule::create([
            'name'          => 'Internal Alert',
            'trigger_event' => 'lead.created',
            'conditions'    => [],
            'actions'       => [
                [
                    'type' => 'send_internal_email',
                    'to'   => 'admin@example.com',
                    'body' => 'New lead received.',
                ],
            ],
            'is_active' => true,
        ]);

        $this->runJob('lead.created', [
            'lead_id'   => $lead->id,
            'client_id' => $this->client->id,
        ]);

        // No exception means the action completed successfully
        $this->assertTrue(true);
    }

    public function test_rule_with_unmatched_condition_skips_action(): void
    {
        $lead = $this->makeLead();

        AutomationRule::create([
            'name'          => 'Calculator-only Email',
            'trigger_event' => 'lead.created',
            'conditions'    => [
                ['field' => 'source', 'operator' => '=', 'value' => 'calculator'],
            ],
            'actions'   => [
                ['type' => 'send_email', 'recipient' => 'client', 'template' => 'new_lead'],
            ],
            'is_active' => true,
        ]);

        // source = 'contact_form', condition expects 'calculator' → should not match
        $this->runJob('lead.created', [
            'lead_id'   => $lead->id,
            'client_id' => $this->client->id,
            'source'    => 'contact_form',
        ]);

        Mail::assertNothingQueued();
    }

    public function test_inactive_rule_is_skipped(): void
    {
        $lead = $this->makeLead();

        AutomationRule::create([
            'name'          => 'Disabled Rule',
            'trigger_event' => 'lead.created',
            'conditions'    => [],
            'actions'       => [
                ['type' => 'send_email', 'recipient' => 'client', 'template' => 'new_lead'],
            ],
            'is_active' => false,
        ]);

        $this->runJob('lead.created', [
            'lead_id'   => $lead->id,
            'client_id' => $this->client->id,
        ]);

        Mail::assertNothingQueued();
    }

    public function test_unknown_action_type_is_ignored_silently(): void
    {
        $lead = $this->makeLead();

        AutomationRule::create([
            'name'          => 'Rule with bad action',
            'trigger_event' => 'lead.created',
            'conditions'    => [],
            'actions'       => [
                ['type' => 'nonexistent_action_type'],
            ],
            'is_active' => true,
        ]);

        // Should not throw
        $this->runJob('lead.created', [
            'lead_id'   => $lead->id,
            'client_id' => $this->client->id,
        ]);

        Mail::assertNothingQueued();
    }
}
