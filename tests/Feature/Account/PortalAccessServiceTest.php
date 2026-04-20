<?php

namespace Tests\Feature\Account;

use App\Models\Business;
use App\Models\BusinessUser;
use App\Models\Client;
use App\Models\User;
use App\Services\Account\PortalAccessService;
use DomainException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class PortalAccessServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Role::findOrCreate('client', 'web');
    }

    public function test_portal_only_access_does_not_create_workspace_membership(): void
    {
        $business = Business::create([
            'name' => 'Agency Workspace',
            'slug' => 'agency-workspace',
            'locale' => 'en',
            'timezone' => 'Europe/London',
            'plan' => 'free',
            'is_active' => true,
        ]);

        $client = Client::create([
            'business_id' => $business->id,
            'company_name' => 'Agency Client Ltd',
            'primary_contact_name' => 'Jane Doe',
            'primary_contact_email' => 'jane@example.com',
        ]);

        $result = app(PortalAccessService::class)->ensurePortalAccess($client, [
            'send_invite' => false,
        ]);

        $this->assertTrue($result['user_was_created']);
        $this->assertNull(BusinessUser::where('user_id', $result['user']->id)->first());
        $this->assertSame($result['user']->id, $client->fresh()->portal_user_id);
    }

    public function test_workspace_access_can_be_granted_explicitly(): void
    {
        $business = Business::create([
            'name' => 'Agency Workspace',
            'slug' => 'agency-workspace',
            'locale' => 'en',
            'timezone' => 'Europe/London',
            'plan' => 'free',
            'is_active' => true,
        ]);

        $client = Client::create([
            'business_id' => $business->id,
            'company_name' => 'Agency Client Ltd',
            'primary_contact_name' => 'Jane Doe',
            'primary_contact_email' => 'jane@example.com',
        ]);

        $result = app(PortalAccessService::class)->ensurePortalAccess($client, [
            'grant_workspace_access' => true,
            'send_invite' => false,
        ]);

        $this->assertTrue($result['workspace_membership_created']);

        $this->assertDatabaseHas('business_users', [
            'business_id' => $business->id,
            'user_id' => $result['user']->id,
            'role' => 'client',
            'is_active' => true,
        ]);
    }

    public function test_workspace_access_requires_client_business_link(): void
    {
        $client = Client::create([
            'company_name' => 'Agency Client Ltd',
            'primary_contact_name' => 'Jane Doe',
            'primary_contact_email' => 'jane@example.com',
        ]);

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Workspace access requires the client to be linked to a business.');

        app(PortalAccessService::class)->ensurePortalAccess($client, [
            'grant_workspace_access' => true,
            'send_invite' => false,
        ]);
    }

    public function test_workspace_access_is_rejected_for_user_with_different_active_workspace(): void
    {
        $existingBusiness = Business::create([
            'name' => 'Existing Workspace',
            'slug' => 'existing-workspace',
            'locale' => 'en',
            'timezone' => 'Europe/London',
            'plan' => 'free',
            'is_active' => true,
        ]);

        $targetBusiness = Business::create([
            'name' => 'Target Workspace',
            'slug' => 'target-workspace',
            'locale' => 'en',
            'timezone' => 'Europe/London',
            'plan' => 'free',
            'is_active' => true,
        ]);

        $user = User::factory()->create([
            'email' => 'jane@example.com',
        ]);
        $user->assignRole('client');

        BusinessUser::create([
            'business_id' => $existingBusiness->id,
            'user_id' => $user->id,
            'role' => 'owner',
            'is_active' => true,
            'joined_at' => now(),
        ]);

        $client = Client::create([
            'business_id' => $targetBusiness->id,
            'company_name' => 'Agency Client Ltd',
            'primary_contact_name' => 'Jane Doe',
            'primary_contact_email' => 'jane@example.com',
        ]);

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Workspace access cannot be granted because this user already belongs to a different active workspace.');

        app(PortalAccessService::class)->ensurePortalAccess($client, [
            'grant_workspace_access' => true,
            'send_invite' => false,
        ]);
    }
}