<?php

namespace Tests\Feature\Filament;

use App\Filament\Resources\ProjectResource\Pages\ViewProject;
use App\Models\Business;
use App\Models\BusinessUser;
use App\Models\Client;
use App\Models\Project;
use App\Models\ProjectPhase;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class ProjectDetailAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->artisan('db:seed', ['--class' => 'AdminSeeder']);
        Filament::setCurrentPanel('admin');
    }

    public function test_read_only_user_cannot_change_project_or_phase_status_from_detail_page(): void
    {
        $developer = $this->tenantUser('project-detail-read-only@example.test');
        $developer->givePermissionTo(['access_admin_panel', 'view_projects']);
        [$project, $phase] = $this->projectWithPhase($developer);
        $this->actingAs($developer);
        $page = app(ViewProject::class);
        $page->mount($project->getRouteKey());

        $this->assertForbiddenCall(fn () => $page->updateProjectStatus('completed'));
        $this->assertForbiddenCall(fn () => $page->updatePhaseStatus($phase->getKey(), 'completed'));
        $this->assertSame('active', $project->fresh()->status);
        $this->assertSame('pending', $phase->fresh()->status);
    }

    public function test_admin_can_change_project_and_phase_status_from_detail_page(): void
    {
        $admin = User::query()->where('email', '20noname22x@gmail.com')->firstOrFail();
        [$project, $phase] = $this->projectWithPhase($admin);
        $this->actingAs($admin);
        $page = app(ViewProject::class);
        $page->mount($project->getRouteKey());

        $page->updateProjectStatus('completed');
        $page->updatePhaseStatus($phase->getKey(), 'completed');

        $this->assertSame('completed', $project->fresh()->status);
        $this->assertNotNull($project->fresh()->completed_at);
        $this->assertSame('completed', $phase->fresh()->status);
    }

    private function assertForbiddenCall(callable $callback): void
    {
        try {
            $callback();
            $this->fail('A read-only user changed project detail state.');
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

    /** @return array{Project, ProjectPhase} */
    private function projectWithPhase(User $user): array
    {
        $client = Client::withoutGlobalScopes()->create([
            'business_id' => $user->currentBusiness()->getKey(),
            'company_name' => 'Project detail client',
            'status' => 'active',
        ]);
        $project = Project::withoutGlobalScopes()->create([
            'business_id' => $user->currentBusiness()->getKey(),
            'client_id' => $client->getKey(),
            'title' => 'Project detail authorization',
            'status' => 'active',
        ]);
        $phase = ProjectPhase::create([
            'project_id' => $project->getKey(),
            'name' => 'Discovery',
            'order' => 1,
            'status' => 'pending',
        ]);

        return [$project, $phase];
    }
}
