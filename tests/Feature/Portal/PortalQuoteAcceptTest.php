<?php

namespace Tests\Feature\Portal;

use App\Models\Client;
use App\Models\Quote;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PortalQuoteAcceptTest extends TestCase
{
    use RefreshDatabase;

    private function makePortalUser(): array
    {
        $user   = User::factory()->create();
        $client = Client::create([
            'company_name'          => 'Test Client Ltd',
            'primary_contact_email' => $user->email,
            'portal_user_id'        => $user->id,
        ]);

        return [$user, $client];
    }

    public function test_client_can_accept_sent_quote(): void
    {
        [$user, $client] = $this->makePortalUser();

        $quote = Quote::create([
            'number'     => 'QT-001',
            'client_id'  => $client->id,
            'created_by' => $user->id,
            'status'     => 'sent',
            'total'      => 5000,
        ]);

        $this->actingAs($user)
            ->post(route('portal.quotes.accept', $quote))
            ->assertRedirect(route('portal.quotes.show', $quote))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('quotes', [
            'id'     => $quote->id,
            'status' => 'accepted',
        ]);
        $this->assertNotNull($quote->fresh()->accepted_at);
    }

    public function test_client_can_reject_sent_quote(): void
    {
        [$user, $client] = $this->makePortalUser();

        $quote = Quote::create([
            'number'     => 'QT-002',
            'client_id'  => $client->id,
            'created_by' => $user->id,
            'status'     => 'sent',
            'total'      => 3000,
        ]);

        $this->actingAs($user)
            ->post(route('portal.quotes.reject', $quote))
            ->assertRedirect(route('portal.quotes.show', $quote))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('quotes', [
            'id'     => $quote->id,
            'status' => 'rejected',
        ]);
    }

    public function test_cannot_accept_already_accepted_quote(): void
    {
        [$user, $client] = $this->makePortalUser();

        $quote = Quote::create([
            'number'      => 'QT-003',
            'client_id'   => $client->id,
            'created_by'  => $user->id,
            'status'      => 'accepted',
            'total'       => 2000,
            'accepted_at' => now(),
        ]);

        $this->actingAs($user)
            ->post(route('portal.quotes.accept', $quote))
            ->assertRedirect()
            ->assertSessionHas('error');
    }

    public function test_cannot_access_another_clients_quote(): void
    {
        [$user] = $this->makePortalUser();

        $otherClient = Client::create([
            'company_name'          => 'Other Corp',
            'primary_contact_email' => 'other@example.com',
        ]);

        $quote = Quote::create([
            'number'     => 'QT-004',
            'client_id'  => $otherClient->id,
            'created_by' => $user->id,
            'status'     => 'sent',
            'total'      => 1000,
        ]);

        $this->actingAs($user)
            ->post(route('portal.quotes.accept', $quote))
            ->assertForbidden();
    }
}
