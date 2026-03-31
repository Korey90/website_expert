<?php

namespace Tests\Feature;

use App\Jobs\ProcessAutomationJob;
use App\Models\Client;
use App\Models\Invoice;
use App\Models\Lead;
use App\Models\PipelineStage;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class AutomationTriggerTest extends TestCase
{
    use RefreshDatabase;

    private Client $client;
    private PipelineStage $stage;
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->stage  = PipelineStage::create(['name' => 'New', 'slug' => 'new', 'order' => 1]);
        $this->client = Client::create([
            'company_name'          => 'Acme Ltd',
            'primary_contact_email' => 'acme@example.com',
        ]);
        $this->user = User::factory()->create();
    }

    public function test_lead_created_dispatches_automation_job(): void
    {
        Queue::fake();

        Lead::create([
            'title'             => 'New Enquiry',
            'client_id'         => $this->client->id,
            'pipeline_stage_id' => $this->stage->id,
            'source'            => 'contact_form',
        ]);

        Queue::assertPushed(ProcessAutomationJob::class, function ($job) {
            return $job->triggerEvent === 'lead.created'
                && isset($job->context['client_id']);
        });
    }

    public function test_lead_stage_change_dispatches_stage_changed_job(): void
    {
        $stageB = PipelineStage::create(['name' => 'Qualified', 'slug' => 'qualified', 'order' => 2]);

        // Create lead before faking queue so the created event doesn't interfere
        $lead = Lead::create([
            'title'             => 'Existing Lead',
            'client_id'         => $this->client->id,
            'pipeline_stage_id' => $this->stage->id,
            'source'            => 'contact_form',
        ]);

        Queue::fake();

        $lead->update(['pipeline_stage_id' => $stageB->id]);

        Queue::assertPushed(ProcessAutomationJob::class, function ($job) {
            return $job->triggerEvent === 'lead.stage_changed'
                && isset($job->context['old_stage_id']);
        });
    }

    public function test_invoice_status_paid_dispatches_invoice_paid_job(): void
    {
        $invoice = Invoice::create([
            'number'     => 'INV-001',
            'client_id'  => $this->client->id,
            'created_by' => $this->user->id,
            'status'     => 'sent',
            'subtotal'   => 1000,
            'vat_amount' => 200,
            'total'      => 1200,
            'issue_date' => now()->toDateString(),
            'due_date'   => now()->addDays(30)->toDateString(),
        ]);

        Queue::fake();

        $invoice->update(['status' => 'paid']);

        Queue::assertPushed(ProcessAutomationJob::class, function ($job) {
            return $job->triggerEvent === 'invoice.paid';
        });
    }

    public function test_invoice_status_sent_dispatches_invoice_sent_job(): void
    {
        $invoice = Invoice::create([
            'number'     => 'INV-002',
            'client_id'  => $this->client->id,
            'created_by' => $this->user->id,
            'status'     => 'draft',
            'subtotal'   => 800,
            'vat_amount' => 160,
            'total'      => 960,
            'issue_date' => now()->toDateString(),
            'due_date'   => now()->addDays(30)->toDateString(),
        ]);

        Queue::fake();

        $invoice->update(['status' => 'sent']);

        Queue::assertPushed(ProcessAutomationJob::class, function ($job) {
            return $job->triggerEvent === 'invoice.sent';
        });
    }

    public function test_project_created_dispatches_project_created_job(): void
    {
        Queue::fake();

        Project::create([
            'title'     => 'New Website',
            'client_id' => $this->client->id,
            'status'    => 'active',
        ]);

        Queue::assertPushed(ProcessAutomationJob::class, function ($job) {
            return $job->triggerEvent === 'project.created';
        });
    }

    public function test_project_status_change_dispatches_status_changed_job(): void
    {
        $project = Project::create([
            'title'     => 'Existing Project',
            'client_id' => $this->client->id,
            'status'    => 'active',
        ]);

        Queue::fake();

        $project->update(['status' => 'completed']);

        Queue::assertPushed(ProcessAutomationJob::class, function ($job) {
            return $job->triggerEvent === 'project.status_changed'
                && $job->context['status'] === 'completed';
        });
    }
}
