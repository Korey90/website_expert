<?php

namespace Tests\Feature\Filament;

use App\Filament\Pages\PipelinePage;
use App\Models\Business;
use App\Models\BusinessUser;
use App\Models\Lead;
use App\Models\PipelineStage;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class PipelineAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->artisan('db:seed', ['--class' => 'AdminSeeder']);
        Filament::setCurrentPanel('admin');
    }

    public function test_read_only_user_can_view_history_but_cannot_move_a_lead(): void
    {
        $developer = $this->tenantUser('pipeline-read-only@example.test');
        $developer->assignRole('developer');
        $lead = $this->leadFor($developer);
        $this->actingAs($developer);
        $page = app(PipelinePage::class);

        $page->openHistoryModal($lead->getKey());

        $this->assertTrue($page->showHistoryModal);
        $this->assertSame($lead->getKey(), $page->historyLeadId);

        try {
            $page->moveStage($lead->getKey(), 'forward');
            $this->fail('Read-only user moved a pipeline lead.');
        } catch (HttpException $exception) {
            $this->assertSame(403, $exception->getStatusCode());
        }

        $this->assertSame($lead->pipeline_stage_id, $lead->fresh()->pipeline_stage_id);

    }

    public function test_read_only_user_cannot_delete_a_lead_from_pipeline(): void
    {
        $developer = $this->tenantUser('pipeline-delete-read-only@example.test');
        $developer->assignRole('developer');
        $lead = $this->leadFor($developer);
        $this->actingAs($developer);

        try {
            app(PipelinePage::class)->deleteLead($lead->getKey());
            $this->fail('Read-only user deleted a pipeline lead.');
        } catch (HttpException $exception) {
            $this->assertSame(403, $exception->getStatusCode());
        }

        $this->assertNotSoftDeleted($lead);
    }

    public function test_admin_can_move_a_lead_to_the_next_pipeline_stage(): void
    {
        $admin = User::query()->where('email', '20noname22x@gmail.com')->firstOrFail();
        $lead = $this->leadFor($admin);
        $nextStage = PipelineStage::query()
            ->where('order', '>', $lead->stage->order)
            ->orderBy('order')
            ->firstOrFail();
        $this->actingAs($admin);

        app(PipelinePage::class)->moveStage($lead->getKey(), 'forward');

        $this->assertSame($nextStage->getKey(), $lead->fresh()->pipeline_stage_id);
    }

    private function tenantUser(string $email): User
    {
        $business = Business::query()->where('slug', 'website-expert')->firstOrFail();
        $user = User::factory()->create(['email' => $email, 'is_active' => true]);

        BusinessUser::create([
            'business_id' => $business->getKey(),
            'user_id' => $user->getKey(),
            'role' => 'member',
            'is_active' => true,
            'joined_at' => now(),
        ]);

        return $user;
    }

    private function leadFor(User $user): Lead
    {
        $business = $user->currentBusiness();
        $stage = PipelineStage::query()
            ->orderBy('order')
            ->firstOrFail();

        return Lead::withoutGlobalScopes()->create([
            'business_id' => $business->getKey(),
            'title' => 'Pipeline authorization lead',
            'pipeline_stage_id' => $stage->getKey(),
        ]);
    }
}
