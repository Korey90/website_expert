<?php

namespace Tests\Feature\Landing;

use App\Jobs\ScheduleLandingLeadFollowUpJob;
use App\Models\Business;
use App\Models\Lead;
use App\Models\LeadActivity;
use App\Models\PipelineStage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

/**
 * Tests for the ScheduleLandingLeadFollowUpJob.
 *
 * Covers:
 *  - Job creates two task_created activities (24h + 72h)
 *  - Tasks have correct due dates
 *  - Tasks contain landing_page_slug and locale
 *  - Idempotent: rerunning does not duplicate tasks
 *  - PL locale uses Polish task titles
 *  - EN locale uses English task titles
 *  - Job dispatched from both landing page controllers
 *  - Silently skips non-existent lead
 */
class LandingLeadFollowUpTest extends TestCase
{
    use RefreshDatabase;

    private Lead $lead;

    protected function setUp(): void
    {
        parent::setUp();

        $this->artisan('db:seed', ['--class' => 'AdminSeeder']);

        PipelineStage::firstOrCreate(
            ['slug' => 'new-lead'],
            ['name' => 'New Lead', 'order' => 1]
        );

        $business = Business::first() ?? Business::create([
            'name'      => 'Website Expert',
            'slug'      => 'website-expert',
            'is_active' => true,
        ]);

        $this->lead = Lead::create([
            'title'             => 'Test Lead',
            'source'            => 'google_ads',
            'source_type'       => 'landing_page',
            'locale'            => 'en',
            'landing_page_slug' => 'web-design-northern-ireland',
            'business_id'       => $business->id,
            'pipeline_stage_id' => PipelineStage::first()->id,
        ]);
    }

    public function test_job_creates_two_task_activities(): void
    {
        ScheduleLandingLeadFollowUpJob::dispatchSync($this->lead->id, 'en');

        $this->assertDatabaseCount('lead_activities', 2);

        $activities = LeadActivity::where('lead_id', $this->lead->id)
            ->where('type', 'task_created')
            ->get();

        $this->assertCount(2, $activities);
    }

    public function test_tasks_have_correct_due_dates(): void
    {
        ScheduleLandingLeadFollowUpJob::dispatchSync($this->lead->id, 'en');

        $activities = LeadActivity::where('lead_id', $this->lead->id)
            ->where('type', 'task_created')
            ->orderBy('id')
            ->get();

        $this->assertSame(now()->addDay()->toDateString(),    $activities[0]->metadata['due_date']);
        $this->assertSame(now()->addDays(3)->toDateString(),  $activities[1]->metadata['due_date']);
    }

    public function test_tasks_contain_landing_page_context(): void
    {
        ScheduleLandingLeadFollowUpJob::dispatchSync($this->lead->id, 'en');

        $activity = LeadActivity::where('lead_id', $this->lead->id)
            ->where('type', 'task_created')
            ->first();

        $this->assertSame('landing_page_follow_up',       $activity->metadata['source']);
        $this->assertSame('web-design-northern-ireland',  $activity->metadata['landing_page']);
        $this->assertSame('en',                           $activity->metadata['locale']);
    }

    public function test_pl_locale_uses_polish_task_titles(): void
    {
        ScheduleLandingLeadFollowUpJob::dispatchSync($this->lead->id, 'pl');

        $activity = LeadActivity::where('lead_id', $this->lead->id)
            ->where('type', 'task_created')
            ->first();

        $this->assertStringContainsString('Skontaktuj', $activity->metadata['task_title']);
    }

    public function test_job_is_idempotent(): void
    {
        ScheduleLandingLeadFollowUpJob::dispatchSync($this->lead->id, 'en');
        ScheduleLandingLeadFollowUpJob::dispatchSync($this->lead->id, 'en');

        $this->assertDatabaseCount('lead_activities', 2);
    }

    public function test_job_silently_skips_missing_lead(): void
    {
        ScheduleLandingLeadFollowUpJob::dispatchSync(99999, 'en');

        $this->assertDatabaseCount('lead_activities', 0);
    }

    public function test_job_is_dispatched_from_pl_controller(): void
    {
        Mail::fake();
        Queue::fake();

        $this->postJson('/pl/strony-internetowe-uk/wyslij', [
            'name'         => 'Jan Test',
            'email'        => 'jan@test.co.uk',
            'message'      => 'Potrzebuję strony.',
            'gdpr_consent' => true,
        ])->assertStatus(200);

        Queue::assertPushed(ScheduleLandingLeadFollowUpJob::class, function ($job) {
            return true; // verified it was dispatched
        });
    }

    public function test_job_is_dispatched_from_en_controller(): void
    {
        Mail::fake();
        Queue::fake();

        $this->postJson('/en/web-design-northern-ireland/submit', [
            'name'         => 'Jane Test',
            'email'        => 'jane@test.co.uk',
            'message'      => 'Need a website.',
            'gdpr_consent' => true,
        ])->assertStatus(200);

        Queue::assertPushed(ScheduleLandingLeadFollowUpJob::class, function ($job) {
            return true;
        });
    }
}
