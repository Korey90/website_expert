<?php

namespace Tests\Feature\LandingPage;

use App\Events\LeadAssigned;
use App\Events\LeadCaptured;
use App\Jobs\ProcessAutomationJob;
use App\Models\Business;
use App\Models\LandingPage;
use App\Models\Lead;
use App\Models\PipelineStage;
use App\Models\User;
use App\Notifications\LeadCapturedNotification;
use App\Services\Leads\LeadService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

/**
 * Tests for events, queue jobs, and notifications in the LP → CRM pipeline.
 *
 * Covers:
 *  - LeadCaptured event dispatched after LP form submit
 *  - AutomationEventListener skips Eloquent event for source=landing_page (no double dispatch)
 *  - ProcessAutomationJob dispatched with trigger=lead.created on LP lead (via LeadCaptured)
 *  - ProcessAutomationJob dispatched with trigger=lead.won after markWon()
 *  - ProcessAutomationJob dispatched with trigger=lead.lost after markLost()
 *  - LeadCaptured → NotifyLeadOwnerListener queued on 'notifications' queue
 *  - LeadCapturedNotification sent to assigned user
 *  - LeadAssigned event dispatched from assign()
 *  - stage_changed automation NOT dispatched when updateQuietly used (markWon internals)
 *  - NewLeadAssignedMail queued for assigned_to user
 */
class LpLeadEventsJobsTest extends TestCase
{
    use RefreshDatabase;

    private Business    $business;
    private User        $assignee;
    private LandingPage $page;

    protected function setUp(): void
    {
        parent::setUp();

        $this->artisan('db:seed', ['--class' => 'AdminSeeder']);
        Cache::flush();

        PipelineStage::firstOrCreate(['slug' => 'new-lead'], ['name' => 'New Lead', 'order' => 1]);
        PipelineStage::firstOrCreate(['slug' => 'won'],      ['name' => 'Won',      'order' => 5, 'is_won'  => true]);
        PipelineStage::firstOrCreate(['slug' => 'lost'],     ['name' => 'Lost',     'order' => 6, 'is_lost' => true]);

        $this->assignee = User::factory()->create(['is_active' => true]);
        $this->assignee->assignRole('manager');

        $this->business = Business::create([
            'name'      => 'Event Corp',
            'slug'      => 'event-corp',
            'is_active' => true,
        ]);

        $this->page = LandingPage::create([
            'business_id'         => $this->business->id,
            'default_assignee_id' => $this->assignee->id,
            'title'               => 'Event LP',
            'slug'                => 'event-lp',
            'status'              => LandingPage::STATUS_PUBLISHED,
            'template_key'        => 'lead_magnet',
        ]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // LeadCaptured event
    // ─────────────────────────────────────────────────────────────────────────

    public function test_lead_captured_event_dispatched_after_submit(): void
    {
        Event::fake([LeadCaptured::class]);

        $this->postJson(route('lp.submit', $this->page->slug), $this->payload());

        Event::assertDispatched(LeadCaptured::class, function (LeadCaptured $e) {
            return $e->lead->source === 'landing_page'
                && $e->landingPage->id === $this->page->id;
        });
    }

    public function test_lead_captured_event_not_dispatched_for_duplicate(): void
    {
        $this->postJson(route('lp.submit', $this->page->slug), $this->payload());

        Event::fake([LeadCaptured::class]);
        $this->postJson(route('lp.submit', $this->page->slug), $this->payload());

        Event::assertNotDispatched(LeadCaptured::class);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // No double dispatch (anti-double-dispatch guard)
    // ─────────────────────────────────────────────────────────────────────────

    public function test_automation_job_dispatched_exactly_once_for_lp_lead(): void
    {
        Queue::fake();

        $this->postJson(route('lp.submit', $this->page->slug), $this->payload());

        // Only one job pushed for trigger=lead.created (via LeadCaptured → onLeadCaptured)
        // NOT two (Eloquent created event is skipped for source=landing_page)
        Queue::assertPushed(ProcessAutomationJob::class, function ($job) {
            return $job->triggerEvent === 'lead.created';
        });

        $jobs = Queue::pushedJobs()[ProcessAutomationJob::class] ?? [];
        $leadCreatedJobs = array_filter($jobs, fn ($j) => $j['job']->triggerEvent === 'lead.created');

        $this->assertCount(1, $leadCreatedJobs);
    }

    public function test_automation_job_context_has_lp_attribution(): void
    {
        Queue::fake();

        $this->postJson(route('lp.submit', $this->page->slug), $this->payload());

        Queue::assertPushed(ProcessAutomationJob::class, function ($job) {
            return $job->triggerEvent === 'lead.created'
                && isset($job->context['landing_page_id'])
                && $job->context['landing_page_id'] === $this->page->id;
        });
    }

    // ─────────────────────────────────────────────────────────────────────────
    // win / lost automation jobs
    // ─────────────────────────────────────────────────────────────────────────

    public function test_mark_won_dispatches_lead_won_automation_job(): void
    {
        $this->postJson(route('lp.submit', $this->page->slug), $this->payload());
        $lead  = Lead::where('landing_page_id', $this->page->id)->firstOrFail();
        $actor = User::factory()->create();

        Queue::fake();

        app(LeadService::class)->markWon($lead, $actor);

        Queue::assertPushed(ProcessAutomationJob::class, function ($job) {
            return $job->triggerEvent === 'lead.won';
        });
    }

    public function test_mark_won_does_not_dispatch_stage_changed_job(): void
    {
        // updateQuietly is used internally → stage change should NOT trigger automation
        $this->postJson(route('lp.submit', $this->page->slug), $this->payload());
        $lead  = Lead::where('landing_page_id', $this->page->id)->firstOrFail();
        $actor = User::factory()->create();

        Queue::fake();

        app(LeadService::class)->markWon($lead, $actor);

        // Exactly one job pushed: lead.won (no stage_changed)
        Queue::assertPushed(ProcessAutomationJob::class, 1);
        Queue::assertPushed(ProcessAutomationJob::class, fn ($j) => $j->triggerEvent === 'lead.won');
    }

    public function test_mark_lost_dispatches_lead_lost_automation_job(): void
    {
        $this->postJson(route('lp.submit', $this->page->slug), $this->payload());
        $lead  = Lead::where('landing_page_id', $this->page->id)->firstOrFail();
        $actor = User::factory()->create();

        Queue::fake();

        app(LeadService::class)->markLost($lead, 'Budget', $actor);

        Queue::assertPushed(ProcessAutomationJob::class, function ($job) {
            return $job->triggerEvent === 'lead.lost'
                && $job->context['lost_reason'] === 'Budget';
        });
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Notifications
    // ─────────────────────────────────────────────────────────────────────────

    public function test_notify_lead_owner_listener_queued_on_notifications_queue(): void
    {
        $listener = new \App\Listeners\NotifyLeadOwnerListener();

        $this->assertSame('notifications', $listener->queue);
        $this->assertInstanceOf(\Illuminate\Contracts\Queue\ShouldQueue::class, $listener);
    }

    public function test_lead_captured_notification_sent_to_assigned_user(): void
    {
        Notification::fake();

        // Process synchronously to test notification
        $this->postJson(route('lp.submit', $this->page->slug), $this->payload());
        $lead = Lead::where('landing_page_id', $this->page->id)->firstOrFail();

        // Directly invoke the listener (bypassing queue for assertions)
        $listener = new \App\Listeners\NotifyLeadOwnerListener();
        $listener->handle(new LeadCaptured($lead->load('client'), $this->page));

        Notification::assertSentTo($this->assignee, LeadCapturedNotification::class);
    }

    public function test_lead_captured_notification_can_be_handled_multiple_times_without_exception(): void
    {
        Notification::fake();

        $this->postJson(route('lp.submit', $this->page->slug), $this->payload());
        $lead = Lead::where('landing_page_id', $this->page->id)->firstOrFail();

        $event    = new LeadCaptured($lead->load('client'), $this->page);
        $listener = new \App\Listeners\NotifyLeadOwnerListener();

        $listener->handle($event);
        $listener->handle($event);

        Notification::assertSentTo($this->assignee, LeadCapturedNotification::class);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // LeadAssigned event
    // ─────────────────────────────────────────────────────────────────────────

    public function test_lead_assigned_event_dispatched_from_assign(): void
    {
        Event::fake([LeadAssigned::class]);

        $this->postJson(route('lp.submit', $this->page->slug), $this->payload());
        $lead     = Lead::where('landing_page_id', $this->page->id)->firstOrFail();
        $newOwner = User::factory()->create();
        $actor    = User::factory()->create();

        app(LeadService::class)->assign($lead, $newOwner, $actor);

        Event::assertDispatched(LeadAssigned::class, fn (LeadAssigned $e) => $e->lead->id === $lead->id);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Email
    // ─────────────────────────────────────────────────────────────────────────

    public function test_new_lead_assigned_mail_queued_for_assigned_user(): void
    {
        Mail::fake();

        $this->postJson(route('lp.submit', $this->page->slug), $this->payload());
        $lead = Lead::where('landing_page_id', $this->page->id)->firstOrFail();

        $listener = new \App\Listeners\NotifyLeadOwnerListener();
        $listener->handle(new LeadCaptured($lead->load(['client', 'landingPage']), $this->page));

        Mail::assertQueued(\App\Mail\NewLeadAssignedMail::class, function ($mail) {
            return $mail->hasTo($this->assignee->email);
        });
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Helpers
    // ─────────────────────────────────────────────────────────────────────────

    private function payload(array $override = []): array
    {
        return array_merge([
            'name'  => 'Event User',
            'email' => 'event@example.com',
        ], $override);
    }
}
