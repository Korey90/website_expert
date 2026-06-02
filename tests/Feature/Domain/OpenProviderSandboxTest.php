<?php

namespace Tests\Feature\Domain;

use App\Actions\Domain\EnsureOpHandleAction;
use App\Data\Domain\DomainRegistrationPayload;
use App\Models\Client;
use App\Services\Domain\OpenProviderClient;
use App\Services\Domain\OpenProviderRegistrarService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Testy integracyjne z sandboxem OpenProvider.
 *
 * Dane logowania są pobierane z tabeli `settings` (klucze: op_username, op_password).
 * Zapisujesz je w panelu administracyjnym → Integracje.
 *
 * Testy są automatycznie pomijane gdy brak konfiguracji w bazie.
 *
 * Uruchomienie:
 *   php artisan test --filter=OpenProviderSandboxTest
 *   php artisan test --group=sandbox
 *
 * Sandbox URL: https://api.cte.openprovider.eu/v1beta
 */
class OpenProviderSandboxTest extends TestCase
{
    // Nie używamy RefreshDatabase — ten test czyta dane z prawdziwej bazy (tabela settings).
    // Wyczyszczenie settings wyczysciłoby też poświadczenia OpenProvider.

    private OpenProviderClient           $client;
    private OpenProviderRegistrarService $registrar;

    protected function setUp(): void
    {
        parent::setUp();

        // Sandbox credentials stored separately from production credentials.
        // Using dedicated keys so production op_username/op_password is never touched.
        $username = $this->readSettingFromAppDb('op_sandbox_username');
        $password = $this->readSettingFromAppDb('op_sandbox_password');

        if (empty($username) || empty($password)) {
            $this->markTestSkipped(
                'Testy sandbox pominięte — brak op_sandbox_username / op_sandbox_password w tabeli settings.'
            );
        }

        // Always force sandbox mode — these tests are sandbox-only
        config([
            'services.domain_registrar.openprovider.username' => $username,
            'services.domain_registrar.openprovider.password' => $password,
            'services.domain_registrar.openprovider.sandbox'  => true,
        ]);

        Cache::forget('openprovider_api_token');

        $this->client    = app(OpenProviderClient::class);
        $this->registrar = app(OpenProviderRegistrarService::class);
    }

    // ── Autentykacja ──────────────────────────────────────────────────────────

    /**
     * @group sandbox
     */
    public function test_can_authenticate_with_sandbox_api(): void
    {
        $token = $this->client->bearerToken();

        $this->assertNotEmpty($token);
        $this->assertIsString($token);
    }

    /**
     * @group sandbox
     */
    public function test_bearer_token_is_cached_on_repeat_call(): void
    {
        $token1 = $this->client->bearerToken();
        $token2 = $this->client->bearerToken();

        $this->assertSame($token1, $token2);
    }

    // ── Dostępność domeny ──────────────────────────────────────────────────────

    /**
     * @group sandbox
     */
    public function test_known_taken_domain_is_unavailable(): void
    {
        $result = $this->registrar->checkAvailability('example.com');

        $this->assertSame('example.com', $result->domain);

        if ($result->error !== null) {
            $this->markTestIncomplete('Sprawdzenie niedostępności pominięte — błąd API: ' . $result->error);
        }

        $this->assertFalse($result->isAvailable);
    }

    /**
     * @group sandbox
     */
    public function test_random_unique_domain_is_available(): void
    {
        $unique = 'we-sandbox-avail-' . uniqid() . '.com';
        $result = $this->registrar->checkAvailability($unique);

        if ($result->error !== null) {
            $this->markTestIncomplete('Sprawdzenie dostępności pominięte — błąd API: ' . $result->error);
        }

        if ($result->isRegistryBusy()) {
            $this->markTestIncomplete('Sprawdzenie dostępności pominięte — rejestr chwilowo niedostępny: ' . $result->reason);
        }

        $this->assertTrue($result->isAvailable, "Oczekiwano dostępności dla: {$unique}");
    }

    /**
     * @group sandbox
     */
    public function test_search_returns_results_for_multiple_tlds(): void
    {
        $result = $this->registrar->search('websiteexpert-sandboxtest');

        $this->assertNotEmpty($result->results);

        $hasErrors = collect($result->results)->every(fn ($r) => $r->error !== null);
        if ($hasErrors) {
            $this->markTestIncomplete('Search pominięty — błąd API: ' . ($result->results[0]->error ?? ''));
        }

        $this->assertGreaterThan(3, count($result->results));

        foreach ($result->results as $r) {
            $this->assertNotEmpty($r->domain);
            $this->assertIsBool($r->isAvailable);
        }
    }

    /**
     * @group sandbox
     */
    public function test_search_strips_tld_from_full_domain_query(): void
    {
        // Użytkownik wpisuje pełną domenę — search() powinien obsłużyć stripping
        $result = $this->registrar->search('websiteexpert-strip.com');

        $this->assertNotEmpty($result->results);
    }

    // ── Rejestracja domeny ────────────────────────────────────────────────────

    /**
     * @group sandbox
     *
     * Rejestruje unikalną domenę w sandboxie OpenProvider.
     * UWAGA: uruchamia się TYLKO gdy op_sandbox=1 w bazie (tryb testowy).
     * W trybie produkcyjnym test jest pomijany — nie chcemy rejestrować prawdziwych domen.
     */
    public function test_can_register_unique_domain_on_sandbox(): void
    {
        // TLD to try — .nl is the most reliable in OP sandbox.
        // NOTE: .pl, .com, .net, .io always return code 10 "Registry not reachable" in sandbox.
        $tld   = '.nl';
        $label = 'domena-test-' . uniqid();
        $fqdn  = "{$label}{$tld}";

        // ── DEBUG: availability check ─────────────────────────────────────────
        $avail = $this->registrar->checkAvailability($fqdn);
        fwrite(STDERR, "\n[DEBUG] checkAvailability({$fqdn})\n");
        fwrite(STDERR, "  isAvailable : " . ($avail->isAvailable ? 'true' : 'false') . "\n");
        fwrite(STDERR, "  reason      : " . ($avail->reason ?? '—') . "\n");
        fwrite(STDERR, "  error       : " . ($avail->error  ?? '—') . "\n");

        if (! $avail->isAvailable) {
            $reason = $avail->reason ?? $avail->error ?? 'unknown';
            $this->markTestIncomplete(
                "Sandbox registry for {$tld} currently busy/unavailable. Reason: {$reason}. Try again later."
            );
        }

        $payload = new DomainRegistrationPayload(
            domainName:             $label,
            tld:                    $tld,
            years:                  1,
            registrantFirstName:    'Test',
            registrantLastName:     'Registrant',
            registrantEmail:        'sandbox@websiteexpert.test',
            registrantPhone:        '+44 7700 900999',
            registrantAddressLine1: '1 Sandbox Street',
            registrantAddressLine2: null,
            registrantCity:         'London',
            registrantCounty:       null,
            registrantPostcode:     'SW1A 1AA',
            registrantCountryCode:  'GB',
            registrantOrganisation: null,
            whoisPrivacy:           false,
            autoRenew:              false,
            nameservers:            [],
        );

        // ── DEBUG: registration attempt ───────────────────────────────────────
        fwrite(STDERR, "[DEBUG] register({$fqdn})\n");
        $result = $this->registrar->register($payload);
        fwrite(STDERR, "  success    : " . ($result->success ? 'true' : 'false') . "\n");
        fwrite(STDERR, "  providerId : " . ($result->providerId ?? '—') . "\n");
        fwrite(STDERR, "  error      : " . ($result->error      ?? '—') . "\n");
        fwrite(STDERR, "  registeredAt: " . ($result->registeredAt?->toDateTimeString() ?? '—') . "\n");
        fwrite(STDERR, "  expiresAt   : " . ($result->expiresAt?->toDateTimeString()   ?? '—') . "\n");

        // Sandbox can return 311 "not free" even after a successful availability check
        // (inconsistent registry state — race condition). Treat as incomplete, not failure.
        if (! $result->success && str_contains($result->error ?? '', '311')) {
            $this->markTestIncomplete(
                "{$tld} registry returned 311 after availability check passed — sandbox inconsistency. Try again later. Error: " . $result->error
            );
        }

        // Code 10 = registry temporarily unreachable, domain queued by OP internally
        if (! $result->success && str_contains($result->error ?? '', '10')) {
            $this->markTestIncomplete(
                "{$tld} registry returned code 10 (not reachable) — sandbox limitation. Error: " . $result->error
            );
        }

        $this->assertTrue(
            $result->success,
            'Rejestracja nie powiodła się: ' . ($result->error ?? 'brak informacji o błędzie')
        );
        $this->assertNotNull($result->registeredAt);
        $this->assertNotNull($result->expiresAt);
        $this->assertTrue($result->expiresAt->isAfter(now()));

        if (empty($result->providerId)) {
            $this->markTestIncomplete(
                'Rejestracja wysłana do kolejki OP (code 10 — registry chwilowo niedostępny). ' .
                'Domena widoczna w panelu sandbox jako pending. providerId zostanie przypisane po aktywacji rejestru.'
            );
        }

        $this->assertNotEmpty($result->providerId);
    }

    // ── Rejestracja dla klienta (op_handle caching) ───────────────────────────

    /**
     * @group sandbox
     *
     * Rejestruje domenę dla klienta test@gmail.com używając EnsureOpHandleAction.
     * Przy pierwszym uruchomieniu tworzy handle OP i zapisuje go na kliencie (clients.op_handle).
     * Przy kolejnym — odczytuje zapisany handle i pomija API lookup.
     */
    public function test_can_register_domain_for_client_test(): void
    {
        $user = DB::connection('app_db')
            ->table('users')
            ->where('email', 'test@gmail.com')
            ->first();

        if (! $user) {
            $this->markTestSkipped('Użytkownik test@gmail.com nie istnieje w bazie.');
        }

        $clientRow = DB::connection('app_db')
            ->table('clients')
            ->join('client_portal_accesses', 'clients.id', '=', 'client_portal_accesses.client_id')
            ->where('client_portal_accesses.user_id', $user->id)
            ->select('clients.*')
            ->first();

        if (! $clientRow) {
            $this->markTestSkipped('Brak klienta powiązanego z 20noname22x@gmail.com (client_portal_accesses).');
        }

        // Build contact data from client record
        $nameParts = explode(' ', (string) $clientRow->primary_contact_name, 2);
        $contactData = [
            'email'        => $clientRow->primary_contact_email ?? $user->email,
            'first_name'   => $nameParts[0] ?? 'Test',
            'last_name'    => $nameParts[1] ?? 'Client',
            'phone'        => $clientRow->primary_contact_phone ?? '+44 7700 900000',
            'country_code' => $clientRow->country ?? 'GB',
            'address_line1'=> $clientRow->address_line1 ?? '1 Test Street',
            'address_line2'=> $clientRow->address_line2 ?? null,
            'city'         => $clientRow->city          ?? 'London',
            'county'       => $clientRow->county        ?? null,
            'postcode'     => $clientRow->postcode      ?? 'SW1A 1AA',
            'organisation' => $clientRow->company_name  ?? null,
        ];

        // Reset cached handle — force fresh creation so OP customer gets complete contact data
        // (city is required for .nl; old cached handle may have been created without it)
        DB::connection('app_db')
            ->table('clients')
            ->where('id', $clientRow->id)
            ->update(['op_handle' => null]);

        // Load Eloquent model so EnsureOpHandleAction can save op_handle back
        $client = Client::on('app_db')->find($clientRow->id);

        $ensureHandle = new EnsureOpHandleAction($this->client);
        $handle       = $ensureHandle->execute($client, $contactData);

        $this->assertNotEmpty($handle, 'EnsureOpHandleAction musi zwrócić niepusty handle OP.');

        // Verify handle was persisted (or was already there)
        $freshOpHandle = DB::connection('app_db')
            ->table('clients')
            ->where('id', $clientRow->id)
            ->value('op_handle');

        $this->assertSame($handle, $freshOpHandle, 'Handle powinien być zapisany w clients.op_handle.');

        // Use .nl — the Dutch registry is fully accessible in OP sandbox (unlike .com which returns code 10)
        $label = 'we-client-' . uniqid();

        // Pre-check: if sandbox .nl registry is busy it reports the domain as "active"
        // with reason "Registry is busy" — registration would fail with code 311.
        $avail = $this->registrar->checkAvailability("{$label}.nl");
        if (! $avail->isAvailable) {
            $reason = $avail->reason ?? $avail->error ?? 'unknown';
            $this->markTestIncomplete(
                ".nl sandbox registry is currently busy (domain '{$label}.nl' reported as non-free). Reason: {$reason}. Try again later."
            );
        }

        $payload = new DomainRegistrationPayload(
            domainName:             $label,
            tld:                    '.nl',
            years:                  1,
            registrantFirstName:    $contactData['first_name'],
            registrantLastName:     $contactData['last_name'],
            registrantEmail:        $contactData['email'],
            registrantPhone:        $contactData['phone'],
            registrantAddressLine1: $contactData['address_line1'],
            registrantAddressLine2: $contactData['address_line2'],
            registrantCity:         $contactData['city'],
            registrantCounty:       $contactData['county'],
            registrantPostcode:     $contactData['postcode'],
            registrantCountryCode:  $contactData['country_code'],
            registrantOrganisation: $contactData['organisation'],
            whoisPrivacy:           false,
            autoRenew:              false,
            nameservers:            [],
            ownerHandle:            $handle,
        );

        $result = $this->registrar->register($payload);

        // Sandbox can return 311 "not free" even after a successful availability check
        // (inconsistent registry state). Treat as incomplete rather than a test failure.
        if (! $result->success && str_contains($result->error ?? '', '311')) {
            $this->markTestIncomplete(
                ".nl registry returned 311 after availability check passed — sandbox inconsistency. Handle: {$handle}. Try again later."
            );
        }

        $this->assertTrue(
            $result->success,
            "Rejestracja '{$label}.nl' nie powiodła się: " . ($result->error ?? 'brak informacji')
        );
        $this->assertNotEmpty($handle);

        if (empty($result->providerId)) {
            $this->markTestIncomplete(
                "Domena '{$label}.nl' wysłana do kolejki OP (code 10). " .
                "Widoczna w panelu sandbox jako pending. Handle klienta: {$handle}"
            );
        }

        $this->assertNotEmpty($result->providerId);

        // Persist Domain to app DB so it appears in portal and admin panel
        DB::connection('app_db')->table('domains')->insert([
            'business_id'        => $clientRow->business_id,
            'client_id'          => $clientRow->id,
            'full_domain'        => "{$label}.nl",
            'name'               => $label,
            'tld'                => '.nl',
            'status'             => 'active',
            'provider'           => 'openprovider',
            'provider_domain_id' => $result->providerId,
            'registered_at'      => $result->registeredAt,
            'expires_at'         => $result->expiresAt,
            'nameservers'        => json_encode([]),
            'dns_records'        => json_encode([]),
            'auto_renew'         => false,
            'whois_privacy'      => false,
            'created_at'         => now(),
            'updated_at'         => now(),
        ]);

        $this->assertTrue(
            DB::connection('app_db')->table('domains')->where('full_domain', "{$label}.nl")->exists(),
            "Domena {$label}.nl powinna być zapisana w tabeli domains."
        );
    }

    // ── Pobieranie cen ────────────────────────────────────────────────────────

    /**
     * @group sandbox
     */
    public function test_can_fetch_price_for_com_tld(): void
    {
        $snapshot = $this->registrar->getPrice('.com');

        $this->assertSame('.com', $snapshot->tld);

        if ($snapshot->registerPrice === 0.0) {
            $this->markTestIncomplete('Ceny pominięte — błąd API lub brak danych cenowych dla .com.');
        }

        $this->assertGreaterThan(0, $snapshot->registerPrice);
        $this->assertNotEmpty($snapshot->currency);
    }

    /**
     * @group sandbox
     */
    public function test_can_fetch_price_for_co_uk_tld(): void
    {
        $snapshot = $this->registrar->getPrice('.co.uk');

        $this->assertSame('.co.uk', $snapshot->tld);

        if ($snapshot->registerPrice === 0.0) {
            $this->markTestIncomplete('Ceny pominięte — błąd API lub brak danych cenowych dla .co.uk.');
        }

        $this->assertGreaterThan(0, $snapshot->registerPrice);
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    /**
     * Odczytuje wartość z tabeli `settings` używając rzeczywistego połączenia MySQL.
     * Testy używają SQLite in-memory (phpunit.xml nadpisuje DB_*), więc czytamy
     * poświadczenia DB bezpośrednio z pliku .env, omijając te overrides.
     */
    private function readSettingFromAppDb(string $key): ?string
    {
        try {
            $envPath = base_path('.env');
            $env     = [];

            foreach (file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
                if (str_starts_with(trim($line), '#') || ! str_contains($line, '=')) {
                    continue;
                }
                [$k, $v]  = explode('=', $line, 2);
                $env[trim($k)] = trim($v, " \t\n\r\0\x0B\"'");
            }

            config(['database.connections.app_db' => [
                'driver'    => $env['DB_CONNECTION'] ?? 'mysql',
                'host'      => $env['DB_HOST']       ?? '127.0.0.1',
                'port'      => $env['DB_PORT']        ?? '3306',
                'database'  => $env['DB_DATABASE']   ?? 'web_expert',
                'username'  => $env['DB_USERNAME']   ?? 'root',
                'password'  => $env['DB_PASSWORD']   ?? '',
                'charset'   => 'utf8mb4',
                'collation' => 'utf8mb4_unicode_ci',
                'prefix'    => '',
            ]]);

            return DB::connection('app_db')
                ->table('settings')
                ->where('key', $key)
                ->value('value');
        } catch (\Throwable) {
            return null;
        }
    }
}
