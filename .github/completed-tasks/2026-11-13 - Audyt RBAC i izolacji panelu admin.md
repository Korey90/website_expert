# 2026-11-13 - Audyt RBAC i izolacji panelu admin

**Status:** Zakończone sukcesem
**Data realizacji:** 2026-11-13
**Czas trwania:** 1 sesja robocza

## Cel zadania

Sprawdzić wszystkie moduły i adresy `/admin/*`, zapewnić każdemu modułowi jawny klucz dostępu, potwierdzić izolację danych pomiędzy tenantami oraz zgodność menu panelu z rolami i uprawnieniami.

## Zakres wykonanych prac

- Zinwentaryzowano 227 tras admina, 56 Resources i 24 Pages.
- Uzupełniono centralny rejestr uprawnień i zmieniono brak mapowania na odmowę dostępu.
- Dodano klucze `view_calendar`, `manage_calendar`, `view_domains` i `manage_domain_pricing` wraz z migracją istniejących ról.
- Zabezpieczono niestandardowe trasy eksportów, załączników, raportów, HMRC OAuth, Google Calendar i akcji leadów.
- Ograniczono cross-tenant listy CRM, domen, landing pages, rankingów oraz policies do aktywnego tenanta; globalny wgląd wymaga `view_businesses`.
- Ograniczono selektory użytkowników, tablicę zadań i moduł powiadomień do członków aktywnej firmy.
- Dopasowano dashboard, widgety, quick actions i topbar do permissions.
- Rozdzielono `view_calendar` od mutującego `manage_calendar` także po stronie backendu i UI.
- Skorygowano domyślną rolę `manager`, usuwając dostęp do tenantów, użytkowników, billing/plans, sesji i innych funkcji operatora platformy.
- Dodano dynamiczny test kontraktowy, który blokuje przyszłe Resources/Pages bez klucza lub tenant scope.

## Użyte agenty i skille

- @SecurityEngineer
- @BackendEngineer
- Skill: delta-analysis
- Skill: filament-resource
- Skill: multi-language-check
- Skill: task-completion-report

## Zmodyfikowane / utworzone pliki

- `app/Filament/Support/FilamentPermissionRegistry.php`
- `app/Filament/Resources/BaseResource.php`, Resources CRM/domen/notifications/projects/rankingów i ich strony
- `app/Filament/Pages/BasePage.php`, dashboard, profil i kalendarz
- `app/Filament/Widgets/*`
- `app/Policies/{Lead,Briefing,SalesOffer,BriefingTemplate,SalesOfferTemplate}Policy.php`
- `app/Providers/Filament/AdminPanelProvider.php`
- `app/Models/SearchRankingCheck.php`
- `routes/web/{admin,calendar,hmrc}.php`
- `resources/views/filament/pages/calendar.blade.php`
- `database/seeders/AdminSeeder.php`
- `database/migrations/2026_11_13_000033_harden_admin_module_permissions.php`
- `tests/Feature/Filament/AdminAccessCoverageTest.php`
- testy raportów i polityk dostosowane do jawnych permissions/membership

## Walidacja końcowa

- ✅ PHP Coding Standard: Laravel Pint dla całego zmienionego zakresu
- ✅ Permission coverage: 56/56 Resources i 24/24 Pages
- ✅ Menu/RBAC: panel-only deny, pełny admin allow, manager tenant-only
- ✅ Multi-tenancy: dynamiczne sprawdzenie wszystkich modeli Resources z `BelongsToTenant`
- ✅ Custom route middleware i bezpośrednie bramki permissions
- ✅ Audyt admina: 8 testów / 1140 asercji
- ✅ Filament targeted: 14 testów / 1185 asercji
- ✅ Raporty i polityki po hardeningu: 44 testy / 74 asercje
- ⚠️ Pełny pakiet uruchomiony: wszystkie 24 regresje wywołane nowymi bramkami zostały poprawione i retestowane; nadal istnieje 6 niezwiązanych błędów baseline (OAuth redirect, homepage meta ×3, billing currency, ServicePage Inertia).
- ✅ Nie dodano nowych tekstów interfejsu wymagających tłumaczeń PL/EN/PT.
- ✅ Commit `ef15028` wdrożony na produkcję; migracja batch 11, cache, queue restart, role i HTTP smoke test przeszły.
- ✅ Zweryfikowana kopia kodu i MySQL: `storage/app/deploy-backups/20261113-rbac-ef15028-v2` na serwerze.

## Uwagi i rekomendacje

Uprawnienie `view_businesses` jest teraz świadomym kluczem operatora platformy i nie powinno być nadawane rolom tenantowym. Każdy nowy Filament Resource/Page bez wpisu w rejestrze zostanie ukryty i zablokowany, a test kontraktowy wskaże brak podczas CI.

## Next steps (opcjonalnie)

- Naprawić 6 istniejących błędów baseline w osobnym zadaniu.
- Wykonać ręczny test UX menu na istniejącym koncie `bookkeeper` podczas kolejnej sesji akceptacyjnej.

**Raport wygenerowany automatycznie przez WebsiteExpert**
