<?php

namespace Tests\Feature\Domain;

use App\Actions\Domain\DeleteDnsRecordAction;
use App\Actions\Domain\FetchDnsRecordsAction;
use App\Actions\Domain\SaveDnsRecordAction;
use App\Actions\Domain\UpdateNameserversAction;
use App\Data\Domain\DnsRecord;
use App\Models\Client;
use App\Models\ClientPortalAccess;
use App\Models\Domain;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

/**
 * Testy HTTP portalu klienta dla zarządzania domenami.
 *
 * Zalogowany użytkownik: 20noname22x@gmail.com (admin z dostępem do portalu).
 * Testy pokrywają: listę domen, podgląd, edycję nameserverów, zarządzanie DNS.
 *
 * Uruchomienie:
 *   php artisan test --filter=DomainPortalTest
 */
class DomainPortalTest extends TestCase
{
    use RefreshDatabase;

    private User   $user;
    private Client $client;
    private Domain $domain;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('db:seed', ['--class' => 'AdminSeeder']);

        $this->user = User::where('email', '20noname22x@gmail.com')->firstOrFail();

        $businessId = \App\Models\Business::where('is_active', true)->first()->id;

        $this->client = Client::create([
            'business_id'            => $businessId,
            'company_name'           => 'Portal Client Ltd',
            'primary_contact_name'   => 'Jan Kowalski',
            'primary_contact_email'  => '20noname22x@gmail.com',
            'primary_contact_phone'  => '+44 7700 900123',
            'address_line1'          => '1 Test Street',
            'city'                   => 'London',
            'postcode'               => 'SW1A 1AA',
            'country'                => 'GB',
        ]);

        ClientPortalAccess::create([
            'client_id' => $this->client->id,
            'user_id'   => $this->user->id,
        ]);

        $this->domain = Domain::create([
            'business_id'        => $businessId,
            'client_id'          => $this->client->id,
            'full_domain'        => 'mysite.co.uk',
            'name'               => 'mysite',
            'tld'                => '.co.uk',
            'status'             => 'active',
            'provider'           => 'openprovider',
            'provider_domain_id' => 'OP-12345',
            'nameservers'        => ['ns1.example.com', 'ns2.example.com'],
            'registered_at'      => now()->subYear(),
            'expires_at'         => now()->addYear(),
        ]);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    // ── Lista domen ───────────────────────────────────────────────────────────

    public function test_portal_client_can_view_domains_index(): void
    {
        $response = $this->actingAs($this->user)
            ->get(route('portal.domains.index'));

        $response->assertOk();
    }

    public function test_domains_index_returns_only_clients_own_domains(): void
    {
        $businessId = $this->client->business_id;

        // Domena należąca do innego klienta — nie powinna być widoczna
        $otherClient = Client::create([
            'business_id'           => $businessId,
            'company_name'          => 'Other Client',
            'primary_contact_name'  => 'Other Person',
            'primary_contact_email' => 'other@example.com',
        ]);
        Domain::create([
            'business_id'   => $businessId,
            'client_id'     => $otherClient->id,
            'full_domain'   => 'otherclient.com',
            'name'          => 'otherclient',
            'tld'           => '.com',
            'status'        => 'active',
            'registered_at' => now()->subYear(),
            'expires_at'    => now()->addYear(),
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('portal.domains.index'));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->where('domains', fn ($domains) => collect($domains)
                ->every(fn ($d) => $d['full_domain'] !== 'otherclient.com')
            )
        );
    }

    // ── Podgląd domeny ────────────────────────────────────────────────────────

    public function test_portal_client_can_view_own_domain(): void
    {
        $response = $this->actingAs($this->user)
            ->get(route('portal.domains.show', $this->domain->id));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Portal/Domains/Show')
            ->has('domain')
        );
    }

    public function test_portal_client_cannot_view_other_clients_domain(): void
    {
        $businessId  = $this->client->business_id;
        $otherClient = Client::create([
            'business_id'           => $businessId,
            'company_name'          => 'Stranger',
            'primary_contact_name'  => 'Stranger',
            'primary_contact_email' => 'stranger@example.com',
        ]);
        $otherDomain = Domain::create([
            'business_id'   => $businessId,
            'client_id'     => $otherClient->id,
            'full_domain'   => 'stranger.com',
            'name'          => 'stranger',
            'tld'           => '.com',
            'status'        => 'active',
            'registered_at' => now()->subYear(),
            'expires_at'    => now()->addYear(),
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('portal.domains.show', $otherDomain->id));

        $response->assertForbidden();
    }

    // ── Nameservery ───────────────────────────────────────────────────────────

    public function test_portal_client_can_update_nameservers(): void
    {
        $mock = Mockery::mock(UpdateNameserversAction::class);
        $mock->shouldReceive('execute')->once()->andReturn($this->domain->fresh());
        $this->app->instance(UpdateNameserversAction::class, $mock);

        $response = $this->actingAs($this->user)
            ->put(route('portal.domains.nameservers.update', $this->domain->id), [
                'nameservers' => ['ns1.cloudflare.com', 'ns2.cloudflare.com'],
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');
    }

    public function test_nameservers_update_rejects_invalid_hostname(): void
    {
        $response = $this->actingAs($this->user)
            ->put(route('portal.domains.nameservers.update', $this->domain->id), [
                'nameservers' => ['not a valid hostname!!'],
            ]);

        $response->assertSessionHasErrors('nameservers.0');
    }

    public function test_nameservers_update_rejects_empty_list(): void
    {
        $response = $this->actingAs($this->user)
            ->put(route('portal.domains.nameservers.update', $this->domain->id), [
                'nameservers' => [],
            ]);

        $response->assertSessionHasErrors('nameservers');
    }

    public function test_nameservers_update_forbidden_for_other_clients_domain(): void
    {
        $businessId  = $this->client->business_id;
        $otherClient = Client::create([
            'business_id'           => $businessId,
            'company_name'          => 'Other',
            'primary_contact_name'  => 'Other',
            'primary_contact_email' => 'other2@example.com',
        ]);
        $otherDomain = Domain::create([
            'business_id'   => $businessId,
            'client_id'     => $otherClient->id,
            'full_domain'   => 'other2.com',
            'name'          => 'other2',
            'tld'           => '.com',
            'status'        => 'active',
            'registered_at' => now()->subYear(),
            'expires_at'    => now()->addYear(),
        ]);

        $response = $this->actingAs($this->user)
            ->put(route('portal.domains.nameservers.update', $otherDomain->id), [
                'nameservers' => ['ns1.cloudflare.com'],
            ]);

        $response->assertForbidden();
    }

    // ── DNS — index ───────────────────────────────────────────────────────────

    public function test_portal_client_can_view_dns_page(): void
    {
        $mock = Mockery::mock(FetchDnsRecordsAction::class);
        $mock->shouldReceive('execute')->once()->andReturn([]);
        $this->app->instance(FetchDnsRecordsAction::class, $mock);

        $response = $this->actingAs($this->user)
            ->get(route('portal.domains.dns.index', $this->domain->id));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Portal/Domains/Dns')
        );
    }

    public function test_dns_index_forbidden_for_other_clients_domain(): void
    {
        $businessId  = $this->client->business_id;
        $otherClient = Client::create([
            'business_id'           => $businessId,
            'company_name'          => 'DNS Stranger',
            'primary_contact_name'  => 'DNS',
            'primary_contact_email' => 'dns@example.com',
        ]);
        $otherDomain = Domain::create([
            'business_id'   => $businessId,
            'client_id'     => $otherClient->id,
            'full_domain'   => 'dns-stranger.com',
            'name'          => 'dns-stranger',
            'tld'           => '.com',
            'status'        => 'active',
            'registered_at' => now()->subYear(),
            'expires_at'    => now()->addYear(),
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('portal.domains.dns.index', $otherDomain->id));

        $response->assertForbidden();
    }

    // ── DNS — store ───────────────────────────────────────────────────────────

    public function test_portal_client_can_create_dns_record(): void
    {
        $mock = Mockery::mock(SaveDnsRecordAction::class);
        $mock->shouldReceive('execute')->once()->andReturn([]);
        $this->app->instance(SaveDnsRecordAction::class, $mock);

        $response = $this->actingAs($this->user)
            ->post(route('portal.domains.dns.store', $this->domain->id), [
                'type'  => 'A',
                'name'  => '@',
                'value' => '1.2.3.4',
                'ttl'   => 3600,
                'prio'  => null,
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');
    }

    public function test_dns_store_rejects_invalid_type(): void
    {
        $response = $this->actingAs($this->user)
            ->post(route('portal.domains.dns.store', $this->domain->id), [
                'type'  => 'INVALID',
                'name'  => '@',
                'value' => '1.2.3.4',
                'ttl'   => 3600,
            ]);

        $response->assertSessionHasErrors('type');
    }

    public function test_dns_store_rejects_ttl_below_minimum(): void
    {
        $response = $this->actingAs($this->user)
            ->post(route('portal.domains.dns.store', $this->domain->id), [
                'type'  => 'A',
                'name'  => '@',
                'value' => '1.2.3.4',
                'ttl'   => 60,
            ]);

        $response->assertSessionHasErrors('ttl');
    }

    // ── DNS — update ──────────────────────────────────────────────────────────

    public function test_portal_client_can_update_dns_record(): void
    {
        $mock = Mockery::mock(SaveDnsRecordAction::class);
        $mock->shouldReceive('execute')->once()->with(Mockery::any(), 42, Mockery::any())->andReturn([]);
        $this->app->instance(SaveDnsRecordAction::class, $mock);

        $response = $this->actingAs($this->user)
            ->put(route('portal.domains.dns.update', [$this->domain->id, 42]), [
                'type'  => 'CNAME',
                'name'  => 'www',
                'value' => 'mysite.co.uk',
                'ttl'   => 3600,
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');
    }

    // ── DNS — destroy ─────────────────────────────────────────────────────────

    public function test_portal_client_can_delete_dns_record(): void
    {
        $mock = Mockery::mock(DeleteDnsRecordAction::class);
        $mock->shouldReceive('execute')->once()->with(Mockery::any(), 99)->andReturnNull();
        $this->app->instance(DeleteDnsRecordAction::class, $mock);

        $response = $this->actingAs($this->user)
            ->delete(route('portal.domains.dns.destroy', [$this->domain->id, 99]));

        $response->assertRedirect();
        $response->assertSessionHas('success');
    }

    public function test_dns_destroy_forbidden_for_other_clients_domain(): void
    {
        $businessId  = $this->client->business_id;
        $otherClient = Client::create([
            'business_id'           => $businessId,
            'company_name'          => 'Del Stranger',
            'primary_contact_name'  => 'Del',
            'primary_contact_email' => 'del@example.com',
        ]);
        $otherDomain = Domain::create([
            'business_id'   => $businessId,
            'client_id'     => $otherClient->id,
            'full_domain'   => 'del-stranger.com',
            'name'          => 'del-stranger',
            'tld'           => '.com',
            'status'        => 'active',
            'registered_at' => now()->subYear(),
            'expires_at'    => now()->addYear(),
        ]);

        $response = $this->actingAs($this->user)
            ->delete(route('portal.domains.dns.destroy', [$otherDomain->id, 1]));

        $response->assertForbidden();
    }

    // ── Unauthenticated ───────────────────────────────────────────────────────

    public function test_unauthenticated_user_is_redirected_from_domains(): void
    {
        $response = $this->get(route('portal.domains.index'));

        $response->assertRedirect(route('login'));
    }}
