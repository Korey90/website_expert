<?php

namespace Tests\Feature\Filament;

use App\Filament\Resources\LeadResource\Pages\ViewLead;
use App\Models\Business;
use App\Models\BusinessUser;
use App\Models\Lead;
use App\Models\LeadNote;
use App\Models\PipelineStage;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class LeadDetailAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->artisan('db:seed', ['--class' => 'AdminSeeder']);
        Filament::setCurrentPanel('admin');
    }

    public function test_view_only_user_cannot_mutate_a_lead_from_detail_page(): void
    {
        $user = $this->tenantUser('lead-detail-read-only@example.test');
        $user->givePermissionTo(['access_admin_panel', 'view_leads']);
        $lead = $this->leadFor($user);
        $this->actingAs($user);
        $page = app(ViewLead::class);
        $page->mount($lead->getRouteKey());

        $this->assertForbiddenCall(fn () => $page->moveStage('forward'));
        $page->newNoteText = 'Unauthorized note';
        $this->assertForbiddenCall(fn () => $page->addNote());

        $this->assertSame($lead->pipeline_stage_id, $lead->fresh()->pipeline_stage_id);
        $this->assertFalse(LeadNote::query()->where('lead_id', $lead->getKey())->exists());
    }

    public function test_admin_can_move_a_lead_from_detail_page(): void
    {
        $admin = User::query()->where('email', '20noname22x@gmail.com')->firstOrFail();
        $lead = $this->leadFor($admin);
        $nextStage = PipelineStage::query()->where('order', '>', $lead->stage->order)->orderBy('order')->firstOrFail();
        $this->actingAs($admin);
        $page = app(ViewLead::class);
        $page->mount($lead->getRouteKey());

        $page->moveStage('forward');

        $this->assertSame($nextStage->getKey(), $lead->fresh()->pipeline_stage_id);
    }

    private function assertForbiddenCall(callable $callback): void
    {
        try {
            $callback();
            $this->fail('A view-only user mutated a lead detail record.');
        } catch (HttpException $exception) {
            $this->assertSame(403, $exception->getStatusCode());
        }
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
        $stage = PipelineStage::query()->orderBy('order')->firstOrFail();

        return Lead::withoutGlobalScopes()->create([
            'business_id' => $user->currentBusiness()->getKey(),
            'title' => 'Lead detail authorization',
            'pipeline_stage_id' => $stage->getKey(),
        ]);
    }
}
