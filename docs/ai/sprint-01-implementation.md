# Website Expert AI — Sprint 01

Status: rozpoczęty, niezweryfikowany runtime. Branch feature/website-expert-ai-sprint-01, base 7a35ca3cd75c8303c6faba150b5b4305654855b0.

## WEAI-001: delta analysis

Anchor files: CLAUDE.md, composer.json, app/Traits/BelongsToTenant.php, app/Scopes/BusinessScope.php, app/Helpers/BusinessHelper.php, app/Models/User.php, app/Models/Business.php, app/Services/LandingPage/OpenAiLandingClient.php, config/services.php, app/Support/PermissionHelper.php, app/Filament/Pages/BasePage.php, app/Filament/Support/FilamentPermissionRegistry.php, database/seeders/AdminSeeder.php, tests/Feature/TenantIsolationTest.php, tests/Feature/Filament/FilamentPermissionAccessTest.php.

Business ID ULID; user ID bigint. BusinessScope przepuszcza zapytania przy braku currentBusiness; AI wymaga jawnego business_id oraz sprawdzania powiązań, bez defaultBusiness. Tenantowe FK złożone; active_version_id będzie sprawdzany pod blokadą i przez join dokumentu/wersji. MySQL/SQLite, bez dodatkowego SDK. Laravel HTTP Client, istniejące credentials, osobne modele/limity. Nie zmieniamy klienta LandingPage. Publiczne AI pozostaje wyłączone. Rezerwacja budżetu jest konserwatywnym limitem operacji, nie rzeczywistym rozliczeniem dostawcy. Operator musi podać górny koszt żądania w microUSD i dodatni limit; domyślnie brak płatnych operacji. Bez automatycznego retry niepewnego wywołania. UKFIN live-docs bez zmian.

## Walidacja

Baseline: NOT RUN (brak checkout, Composer, vendor i DB; GitHub API zapewnia odczyt i zapis). PHP 8.4 CLI dostępne lokalnie, ale pełny runtime i lockfile wymagają sprawdzenia. Nie uruchomiono migracji ani OpenAI. Nie wdrażano.
