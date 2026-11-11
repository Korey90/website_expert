<?php

namespace Tests\Feature\Finance;

use App\Actions\Finance\UpdateHmrcSandboxVrnAction;
use App\Models\Business;
use App\Models\BusinessUser;
use App\Models\HmrcConnection;
use App\Models\User;
use DomainException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class HmrcSandboxVrnTest extends TestCase
{
    use RefreshDatabase;

    private Business $business;

    private User $owner;

    private HmrcConnection $connection;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('hmrc.environment', 'sandbox');

        $this->business = Business::create([
            'name' => 'Website Expert NI',
            'slug' => 'website-expert-ni-hmrc-vrn',
            'is_active' => true,
        ]);
        $this->owner = User::factory()->create(['is_active' => true]);
        BusinessUser::create([
            'business_id' => $this->business->id,
            'user_id' => $this->owner->id,
            'role' => 'owner',
            'is_active' => true,
            'joined_at' => now(),
        ]);
        $this->connection = HmrcConnection::withoutGlobalScopes()->create([
            'business_id' => $this->business->id,
            'environment' => 'sandbox',
            'scopes' => ['read:vat', 'write:vat'],
            'access_token' => 'sandbox-access-token',
            'refresh_token' => 'sandbox-refresh-token',
            'expires_at' => now()->addHours(4),
            'status' => HmrcConnection::STATUS_CONNECTED,
            'connected_at' => now(),
            'connected_by' => $this->owner->id,
        ]);
    }

    public function test_owner_can_store_a_normalised_encrypted_sandbox_vrn_with_audit_metadata(): void
    {
        $updated = app(UpdateHmrcSandboxVrnAction::class)->execute(
            $this->business,
            $this->owner,
            'GB 987 654 321',
        );

        $raw = DB::table('hmrc_connections')->where('id', $updated->id)->first();

        $this->assertSame('987654321', $updated->vrn);
        $this->assertNotSame('987654321', $raw->vrn);
        $this->assertSame($this->owner->id, $updated->vrn_updated_by);
        $this->assertNotNull($updated->vrn_updated_at);
        $this->assertSame($this->owner->id, $updated->vrnUpdatedBy->id);
        $this->assertArrayNotHasKey('vrn', $updated->toArray());
    }

    public function test_regular_member_cannot_change_the_sandbox_vrn(): void
    {
        $member = User::factory()->create(['is_active' => true]);
        BusinessUser::create([
            'business_id' => $this->business->id,
            'user_id' => $member->id,
            'role' => 'member',
            'is_active' => true,
            'joined_at' => now(),
        ]);

        try {
            app(UpdateHmrcSandboxVrnAction::class)->execute($this->business, $member, '987654321');
            $this->fail('A regular member must not change the HMRC sandbox VRN.');
        } catch (DomainException $exception) {
            $this->assertSame(__('finance.hmrc_connection.errors.cannot_manage'), $exception->getMessage());
        }

        $this->assertNull($this->connection->fresh()->vrn);
    }

    public function test_actor_cannot_change_another_tenants_sandbox_vrn(): void
    {
        $otherBusiness = Business::create([
            'name' => 'Other Business',
            'slug' => 'other-hmrc-vrn-business',
            'is_active' => true,
        ]);
        $otherConnection = HmrcConnection::withoutGlobalScopes()->create([
            'business_id' => $otherBusiness->id,
            'environment' => 'sandbox',
            'scopes' => ['read:vat'],
            'status' => HmrcConnection::STATUS_DISCONNECTED,
        ]);

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage(__('finance.hmrc_connection.errors.cannot_manage'));

        try {
            app(UpdateHmrcSandboxVrnAction::class)->execute($otherBusiness, $this->owner, '987654321');
        } finally {
            $this->assertNull($otherConnection->fresh()->vrn);
        }
    }

    public function test_invalid_or_production_vrn_changes_are_rejected(): void
    {
        try {
            app(UpdateHmrcSandboxVrnAction::class)->execute($this->business, $this->owner, 'GB123');
            $this->fail('An invalid VRN must be rejected.');
        } catch (DomainException $exception) {
            $this->assertSame(__('finance.hmrc_connection.errors.invalid_vrn'), $exception->getMessage());
        }

        config()->set('hmrc.environment', 'production');

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage(__('finance.hmrc_connection.errors.sandbox_vrn_only'));

        app(UpdateHmrcSandboxVrnAction::class)->execute($this->business, $this->owner, '987654321');
    }
}
