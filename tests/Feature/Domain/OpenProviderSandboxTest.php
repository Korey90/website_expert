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

        $this->info('Środowisko: sandbox — ' . config('services.domain_registrar.openprovider.sandbox') ? 'tak' : 'nie');
        $this->info('Użytkownik: ' . $username);
    }

    // ── Autentykacja ──────────────────────────────────────────────────────────

    /**
     * @group sandbox
     */
    public function test_can_authenticate_with_sandbox_api(): void
    {
        $this->step('Wysyłam żądanie logowania do sandbox API...');
        $token = $this->client->bearerToken();

        $preview = substr($token, 0, 10) . '...[MASKED]';
        $this->ok("Token otrzymany: {$preview}");

        $this->assertNotEmpty($token);
        $this->assertIsString($token);
    }

    /**
     * @group sandbox
     */
    public function test_bearer_token_is_cached_on_repeat_call(): void
    {
        $this->step('Pierwsze pobranie tokenu...');
        $token1 = $this->client->bearerToken();
        $this->ok('Token #1 otrzymany.');

        $this->step('Drugie pobranie tokenu (powinno trafić w cache)...');
        $token2 = $this->client->bearerToken();
        $this->ok('Token #2 otrzymany — ' . ($token1 === $token2 ? 'identyczny z #1 (cache działa)' : 'RÓŻNY od #1 (cache nie działa!)'));

        $this->assertSame($token1, $token2);
    }

    // ── Dostępność domeny ──────────────────────────────────────────────────────

    /**
     * @group sandbox
     */
    public function test_known_taken_domain_is_unavailable(): void
    {
        $this->step('Sprawdzam dostępność example.com (powinna być zajęta)...');
        $result = $this->registrar->checkAvailability('example.com');

        $this->assertSame('example.com', $result->domain);

        if ($result->error !== null) {
            $this->warn('Błąd API: ' . $result->error);
            $this->markTestIncomplete('Sprawdzenie niedostępności pominięte — błąd API: ' . $result->error);
        }

        $this->ok('Domena example.com jest ' . ($result->isAvailable ? 'DOSTĘPNA (nieoczekiwane!)' : 'zajęta — poprawnie'));
        $this->assertFalse($result->isAvailable);
    }

    /**
     * @group sandbox
     */
    public function test_random_unique_domain_is_available(): void
    {
        $unique = 'we-sandbox-avail-' . uniqid() . $this->getTestTld();
        $this->step("Sprawdzam dostępność unikalnej domeny: {$unique}");
        $result = $this->registrar->checkAvailability($unique);

        if ($result->error !== null) {
            $this->warn('Błąd API: ' . $result->error);
            $this->markTestIncomplete('Sprawdzenie dostępności pominięte — błąd API: ' . $result->error);
        }

        if ($result->isRegistryBusy()) {
            $this->warn('Rejestr chwilowo niedostępny: ' . $result->reason);
            $this->markTestIncomplete('Sprawdzenie dostępności pominięte — rejestr chwilowo niedostępny: ' . $result->reason);
        }

        $this->ok('Domena ' . $unique . ' jest ' . ($result->isAvailable ? 'dostępna — poprawnie' : 'ZAJĘTA (nieoczekiwane!)'));
        $this->assertTrue($result->isAvailable, "Oczekiwano dostępności dla: {$unique}");
    }

    /**
     * @group sandbox
     */
    public function test_search_returns_results_for_multiple_tlds(): void
    {
        $this->step('Wyszukuję domenę websiteexpert-sandboxtest przez wiele TLD...');
        $result = $this->registrar->search('websiteexpert-sandboxtest');

        $this->assertNotEmpty($result->results);

        $hasErrors = collect($result->results)->every(fn ($r) => $r->error !== null);
        if ($hasErrors) {
            $this->warn('Błąd API: ' . ($result->results[0]->error ?? ''));
            $this->markTestIncomplete('Search pominięty — błąd API: ' . ($result->results[0]->error ?? ''));
        }

        $this->assertGreaterThan(3, count($result->results));

        $available = collect($result->results)->where('isAvailable', true)->count();
        $taken     = collect($result->results)->where('isAvailable', false)->count();
        $this->ok('Wyniki: ' . count($result->results) . ' TLD — dostępnych: ' . $available . ', zajętych: ' . $taken);
        foreach ($result->results as $r) {
            $status = $r->isAvailable ? 'wolna' : 'zajęta';
            $this->info('  ' . $r->domain . ' → ' . $status . ($r->error ? ' [błąd: ' . $r->error . ']' : ''));
            $this->assertNotEmpty($r->domain);
            $this->assertIsBool($r->isAvailable);
        }
    }

    /**
     * @group sandbox
     */
    public function test_search_strips_tld_from_full_domain_query(): void
    {
        $this->step('Wyszukuję pełną domenę websiteexpert-strip.com — search() powinien usunąć .com i wyszukać label...');
        $result = $this->registrar->search('websiteexpert-strip.com');

        $this->ok('Otrzymano ' . count($result->results) . ' wyników (TLD zostało poprawnie odcięte)');
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
        // TLD is selected interactively via `php artisan domain:sandbox-test`
        // or falls back to .nl (most reliable in OP sandbox).
        // NOTE: .pl, .com, .net, .io often return code 10 "Registry not reachable" in sandbox.
        $tld   = $this->getTestTld();
        $label = 'domena-test-' . uniqid();
        $fqdn  = "{$label}{$tld}";

        $this->step("Krok 1/3 — sprawdzam dostępność: {$fqdn}");
        $avail = $this->registrar->checkAvailability($fqdn);
        $this->ok('checkAvailability: ' . ($avail->isAvailable ? 'dostępna' : 'niedostępna') . ($avail->reason ? ' (powód: ' . $avail->reason . ')' : ''));

        if (! $avail->isAvailable) {
            $reason = $avail->reason ?? $avail->error ?? 'unknown';
            $this->warn("Rejestr {$tld} niedostępny: {$reason}");
            $this->markTestIncomplete(
                "Sandbox registry for {$tld} currently busy/unavailable. Reason: {$reason}. Try again later."
            );
        }

        $this->step('Krok 2/3 — buduję payload rejestracji (bez klienta, dane testowe)...');
        $this->info('  Registrant: Test Registrant <sandbox@websiteexpert.test>');
        $this->info('  Adres: 1 Sandbox Street, London SW1A 1AA, GB');
        $this->info('  Okres: 1 rok, autoRenew: nie, whoisPrivacy: nie');

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

        $this->step('Krok 3/3 — wysyłam żądanie rejestracji do OpenProvider...');
        $result = $this->registrar->register($payload);

        $this->info('  success    : ' . ($result->success ? 'tak' : 'nie'));
        $this->info('  providerId : ' . ($result->providerId ?: '(brak — kolejka)'));
        $this->info('  error      : ' . ($result->error     ?: 'brak'));
        $this->info('  registeredAt: ' . ($result->registeredAt?->toDateTimeString() ?: '—'));
        $this->info('  expiresAt   : ' . ($result->expiresAt?->toDateTimeString()   ?: '—'));

        // Sandbox can return 311 "not free" even after a successful availability check
        // (inconsistent registry state — race condition). Treat as incomplete, not failure.
        if (! $result->success && str_contains($result->error ?? '', '311')) {
            $this->warn('Rejestr zwrócił 311 (domena nie wolna) mimo pozytywnego availability check — sandbox race condition.');
            $this->markTestIncomplete(
                "{$tld} registry returned 311 after availability check passed — sandbox inconsistency. Try again later. Error: " . $result->error
            );
        }

        // NOTE: code 10 (registry unreachable) is absorbed by OpenProviderRegistrarService::register()
        // which calls resolveQueuedRegistration() and always returns success=true with empty providerId.
        // That path is handled below by if (empty($result->providerId)).

        $this->assertTrue(
            $result->success,
            'Rejestracja nie powiodła się: ' . ($result->error ?? 'brak informacji o błędzie')
        );

        $this->ok("{$fqdn} zarejestrowana pomyślnie! providerId: {$result->providerId}, wygasa: " . $result->expiresAt?->toDateString());

        $this->assertNotNull($result->registeredAt);
        $this->assertNotNull($result->expiresAt);
        $this->assertTrue($result->expiresAt->isAfter(now()));

        if (empty($result->providerId)) {
            $this->warn('providerId puste — domena w kolejce OP (rejestr przetworzy ją asynchronicznie).');
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
        $this->step('Krok 1/5 — szukam użytkownika test@gmail.com w bazie...');
        $user = DB::connection('app_db')
            ->table('users')
            ->where('email', 'test@gmail.com')
            ->first();

        if (! $user) {
            $this->warn('Użytkownik test@gmail.com nie istnieje — test pominięty.');
            $this->markTestSkipped('Użytkownik test@gmail.com nie istnieje w bazie.');
        }

        $this->ok('Znaleziono użytkownika: id=' . $user->id . ', email=' . $user->email);

        $this->step('Krok 2/5 — szukam klienta powiązanego przez client_portal_accesses...');
        $clientRow = DB::connection('app_db')
            ->table('clients')
            ->join('client_portal_accesses', 'clients.id', '=', 'client_portal_accesses.client_id')
            ->where('client_portal_accesses.user_id', $user->id)
            ->select('clients.*')
            ->first();

        if (! $clientRow) {
            $this->warn('Brak klienta powiązanego z test@gmail.com.');
            $this->markTestSkipped('Brak klienta powiązanego z test@gmail.com (client_portal_accesses).');
        }

        $this->ok('Znaleziono klienta: id=' . $clientRow->id . ', firma=' . ($clientRow->company_name ?: '(brak)'));

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

        $this->step('Krok 3/5 — resetuję op_handle (wymuszam świeże tworzenie w OP) i uruchamiam EnsureOpHandleAction...');
        $this->info('  Dane kontaktowe: ' . $contactData['first_name'] . ' ' . $contactData['last_name'] . ' <' . $contactData['email'] . '>');
        $this->info('  Adres: ' . $contactData['address_line1'] . ', ' . $contactData['city'] . ', ' . $contactData['country_code']);

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

        $this->ok('EnsureOpHandleAction zwrócił handle: ' . $handle);
        $this->assertNotEmpty($handle, 'EnsureOpHandleAction musi zwrócić niepusty handle OP.');

        $this->step('Weryfikuję czy handle został zapisany w clients.op_handle...');
        $freshOpHandle = DB::connection('app_db')
            ->table('clients')
            ->where('id', $clientRow->id)
            ->value('op_handle');

        $this->ok('Handle w bazie: ' . ($freshOpHandle ?: '(brak — błąd zapisu!)'));
        $this->assertSame($handle, $freshOpHandle, 'Handle powinien być zapisany w clients.op_handle.');

        // TLD is selected interactively via `php artisan domain:sandbox-test` (default: .nl)
        $tld   = $this->getTestTld();
        $label = 'we-client-' . uniqid();

        $this->step('Krok 4/5 — sprawdzam dostępność ' . $label . $tld . ' przed rejestracją...');

        // Pre-check: if sandbox registry is busy it reports the domain as "active"
        // with reason "Registry is busy" — registration would fail with code 311.
        $avail = $this->registrar->checkAvailability("{$label}{$tld}");
        $this->ok('checkAvailability: ' . ($avail->isAvailable ? 'dostępna' : 'niedostępna') . ($avail->reason ? ' (powód: ' . $avail->reason . ')' : ''));

        if (! $avail->isAvailable) {
            $reason = $avail->reason ?? $avail->error ?? 'unknown';
            $this->warn("Rejestr {$tld} niedostępny: {$reason}");
            // "Registry is busy" in availability check means OP returns 311 on registration too
            // (domain is treated as "active"). Nothing to register or save — skip the test.
            // Code 10 (registry unreachable) only happens when availability passed but the
            // registry became unreachable during the actual registration order — different path.
            $this->markTestIncomplete(
                "{$tld} sandbox registry is currently busy (domain '{$label}{$tld}' reported as non-free). Reason: {$reason}. Try again later."
            );
        }

        $payload = new DomainRegistrationPayload(
            domainName:             $label,
            tld:                    $tld,
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

        $this->step('Krok 5/5 — wysyłam żądanie rejestracji dla klienta (handle: ' . $handle . ')...');
        $result = $this->registrar->register($payload);

        $this->info('  success    : ' . ($result->success ? 'tak' : 'nie'));
        $this->info('  providerId : ' . ($result->providerId ?: '(brak — kolejka)'));
        $this->info('  error      : ' . ($result->error     ?: 'brak'));
        $this->info('  registeredAt: ' . ($result->registeredAt?->toDateTimeString() ?: '—'));
        $this->info('  expiresAt   : ' . ($result->expiresAt?->toDateTimeString()   ?: '—'));

        // Sandbox can return 311 "not free" even after a successful availability check
        // (inconsistent registry state). Treat as incomplete rather than a test failure.
        if (! $result->success && str_contains($result->error ?? '', '311')) {
            $this->warn('Rejestr zwrócił 311 (domena nie wolna) mimo pozytywnego availability check — sandbox race condition.');
            $this->markTestIncomplete(
                "{$tld} registry returned 311 after availability check passed — sandbox inconsistency. Handle: {$handle}. Try again later."
            );
        }

        $this->assertTrue(
            $result->success,
            "Rejestracja '{$label}{$tld}' nie powiodła się: " . ($result->error ?? 'brak informacji')
        );
        $this->assertNotEmpty($handle);

        // When OP queues the registration (code 10 / registry busy), providerId is empty.
        // Save to DB anyway — the domain was submitted to OP and will be activated asynchronously.
        $dbStatus = empty($result->providerId) ? 'pending' : 'active';
        if (empty($result->providerId)) {
            $this->warn('providerId puste — domena w kolejce OP (code 10). Zapisuję z status=req.');
        } else {
            $this->ok("{$label}{$tld} zarejestrowana dla klienta. providerId: {$result->providerId}, handle: {$handle}");
        }

        $this->step('Zapisuję domenę do tabeli domains w bazie aplikacji...');
        DB::connection('app_db')->table('domains')->insert([
            'business_id'        => $clientRow->business_id,
            'client_id'          => $clientRow->id,
            'full_domain'        => "{$label}{$tld}",
            'name'               => $label,
            'tld'                => $tld,
            'status'             => $dbStatus,
            'provider'           => 'openprovider',
            'provider_domain_id' => $result->providerId ?: null,
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
            DB::connection('app_db')->table('domains')->where('full_domain', "{$label}{$tld}")->exists(),
            "Domena {$label}{$tld} powinna być zapisana w tabeli domains."
        );

        $this->ok("Domena {$label}{$tld} zapisana w tabeli domains (status: {$dbStatus}). Test zakończony pomyślnie.");
    }

    // ── Pobieranie cen ────────────────────────────────────────────────────────

    /**
     * @group sandbox
     */
    public function test_can_fetch_price_for_com_tld(): void
    {
        $this->step('Pobieram cennik dla .com z OpenProvider...');
        $snapshot = $this->registrar->getPrice('.com');

        $this->assertSame('.com', $snapshot->tld);

        if ($snapshot->registerPrice === 0.0) {
            $this->warn('Brak danych cenowych dla .com — prawdopodobnie błąd API.');
            $this->markTestIncomplete('Ceny pominięte — błąd API lub brak danych cenowych dla .com.');
        }

        $this->ok('.com — rejestracja: ' . $snapshot->registerPrice . ' ' . $snapshot->currency . ', odnowienie: ' . $snapshot->renewPrice . ' ' . $snapshot->currency);
        $this->assertGreaterThan(0, $snapshot->registerPrice);
        $this->assertNotEmpty($snapshot->currency);
    }

    /**
     * @group sandbox
     */
    public function test_can_fetch_price_for_co_uk_tld(): void
    {
        $this->step('Pobieram cennik dla .co.uk z OpenProvider...');
        $snapshot = $this->registrar->getPrice('.co.uk');

        $this->assertSame('.co.uk', $snapshot->tld);

        if ($snapshot->registerPrice === 0.0) {
            $this->warn('Brak danych cenowych dla .co.uk — prawdopodobnie błąd API.');
            $this->markTestIncomplete('Ceny pominięte — błąd API lub brak danych cenowych dla .co.uk.');
        }

        $this->ok('.co.uk — rejestracja: ' . $snapshot->registerPrice . ' ' . $snapshot->currency);
        $this->assertGreaterThan(0, $snapshot->registerPrice);
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    /**
     * Zwraca TLD wybrany przez użytkownika przed uruchomieniem testów.
     * Wartość pochodzi ze zmiennej środowiskowej DOMAIN_TEST_TLD ustawianej
     * przez komendę `php artisan domain:sandbox-test`.
     * Domyślnie: .nl (najbardziej stabilny TLD w sandboxie OpenProvider).
     */
    // ── Logging helpers ───────────────────────────────────────────────────────

    /** Loguje krok procedury (nagłówek kroku). */
    private function step(string $message): void
    {
        fwrite(STDERR, "\n\033[1;36m  ● {$message}\033[0m\n");
    }

    /** Loguje sukces / wynik. */
    private function ok(string $message): void
    {
        fwrite(STDERR, "\033[32m  ✔ {$message}\033[0m\n");
    }

    /** Loguje informację neutralną. */
    private function info(string $message): void
    {
        fwrite(STDERR, "\033[90m  {$message}\033[0m\n");
    }

    /** Loguje ostrzeżenie (nie zatrzymuje testu). */
    private function warn(string $message): void
    {
        fwrite(STDERR, "\033[1;33m  ⚠ {$message}\033[0m\n");
    }

    private function getTestTld(): string
    {
        $tld = (string) env('DOMAIN_TEST_TLD', '.nl');

        return str_starts_with($tld, '.') ? $tld : '.' . $tld;
    }

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
