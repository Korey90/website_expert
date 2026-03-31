<?php

namespace Tests\Feature\Portal;

use App\Models\Client;
use App\Models\Contract;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PortalContractSignTest extends TestCase
{
    use RefreshDatabase;

    private function makePortalUser(): array
    {
        $user   = User::factory()->create(['password' => bcrypt('password')]);
        $client = Client::create([
            'company_name'          => 'Test Client Ltd',
            'primary_contact_name'  => 'John Doe',
            'primary_contact_email' => $user->email,
            'portal_user_id'        => $user->id,
        ]);

        return [$user, $client];
    }

    public function test_client_can_sign_a_sent_contract(): void
    {
        [$user, $client] = $this->makePortalUser();

        $contract = Contract::create([
            'number'     => 'CT-001',
            'title'      => 'Web Development Contract',
            'client_id'  => $client->id,
            'created_by' => $user->id,
            'status'     => 'sent',
        ]);

        $this->actingAs($user)
            ->post(route('portal.contracts.sign', $contract), [
                'signer_name'    => 'John Doe',
                'signature_data' => 'data:image/png;base64,abc123',
                'confirmed'      => '1',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('contracts', [
            'id'          => $contract->id,
            'status'      => 'signed',
            'signer_name' => 'John Doe',
        ]);
        $this->assertNotNull($contract->fresh()->signed_at);
    }

    public function test_signing_requires_signer_name(): void
    {
        [$user, $client] = $this->makePortalUser();

        $contract = Contract::create([
            'number'     => 'CT-002',
            'title'      => 'Contract',
            'client_id'  => $client->id,
            'created_by' => $user->id,
            'status'     => 'sent',
        ]);

        $this->actingAs($user)
            ->post(route('portal.contracts.sign', $contract), [
                'signer_name' => '',
                'confirmed'   => '1',
            ])
            ->assertSessionHasErrors('signer_name');
    }

    public function test_cannot_sign_already_signed_contract(): void
    {
        [$user, $client] = $this->makePortalUser();

        $contract = Contract::create([
            'number'     => 'CT-003',
            'title'      => 'Contract',
            'client_id'  => $client->id,
            'created_by' => $user->id,
            'status'     => 'signed',
            'signed_at'  => now(),
        ]);

        $this->actingAs($user)
            ->post(route('portal.contracts.sign', $contract), [
                'signer_name' => 'John Doe',
                'confirmed'   => '1',
            ])
            ->assertRedirect()
            ->assertSessionHas('error');
    }

    public function test_cannot_sign_another_clients_contract(): void
    {
        [$user] = $this->makePortalUser();

        $otherClient = Client::create([
            'company_name'          => 'Other Corp',
            'primary_contact_email' => 'other@example.com',
        ]);

        $contract = Contract::create([
            'number'     => 'CT-004',
            'title'      => 'Contract',
            'client_id'  => $otherClient->id,
            'created_by' => $user->id,
            'status'     => 'sent',
        ]);

        $this->actingAs($user)
            ->post(route('portal.contracts.sign', $contract), [
                'signer_name' => 'Hacker',
                'confirmed'   => '1',
            ])
            ->assertForbidden();
    }

    public function test_unauthenticated_user_is_redirected(): void
    {
        [$user, $client] = $this->makePortalUser();

        $contract = Contract::create([
            'number'     => 'CT-005',
            'title'      => 'Contract',
            'client_id'  => $client->id,
            'created_by' => $user->id,
            'status'     => 'sent',
        ]);

        $this->post(route('portal.contracts.sign', $contract), [
            'signer_name' => 'Anon',
            'confirmed'   => '1',
        ])->assertRedirect(route('login'));
    }
}
