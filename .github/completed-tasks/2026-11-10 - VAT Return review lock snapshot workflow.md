# 2026-11-10 - VAT Return review/lock/snapshot workflow

**Status:** Zakończone sukcesem
**Data realizacji:** 2026-11-10
**Czas trwania:** około 1 godziny w bieżącej sesji implementacyjnej

## Cel zadania

Utrwalić gotową kalkulację UK VAT Return jako tenantowy, audytowalny i niezmienny snapshot, rozdzielić przygotowanie, niezależną weryfikację oraz blokadę okresu, a także uniemożliwić późniejsze księgowanie nowych journal entries do zablokowanego okresu VAT. Funkcja pozostaje wewnętrznym workspace bez wysyłki do HMRC.

## Zakres wykonanych prac

- Dodano tabelę i model `VatReturn` z numeracją `VATR`, tenant scope, statusem, zakresem okresu, snapshotem JSON, SHA-256, licznikami źródeł i pełnym audytem aktorów/czasów/powodów.
- Zaimplementowano nieodwracalny workflow `draft → reviewed → locked`; `draft` i `reviewed` mogą zostać oznaczone jako `superseded` wyłącznie z uzasadnieniem.
- Snapshot utrwala 9 pól, HMRC field values, checks, issues/warnings, mapowania tax codes, identyfikatory źródłowych journali i linii oraz ich hashe.
- Numer VAT nie jest przechowywany jawnie w snapshotach — pozostają tylko ostatnie 4 znaki i SHA-256.
- Review wymaga `approve_finance` i jawnego potwierdzenia; lock wymaga `close_finance_periods` i powodu. Obie operacje ponownie liczą aktualny hash i odrzucają drift ledgeru, profilu lub konfiguracji.
- Centralny posting journalu odrzuca nowe wpisy w locked VAT period, lecz najpierw obsługuje bezpieczny idempotentny retry istniejącego wpisu i pozwala na korekty w późniejszym okresie.
- Dodano read-only Filament Resource `/admin/vat-returns` z akcjami review, lock i supersede oraz akcję utworzenia snapshotu na stronie VAT Detail.
- Rozszerzono tenant audit o właściciela VAT Return, źródłowe journal/line IDs, liczniki oraz integralność SHA-256 snapshotu.
- Dodano komplet komunikatów i etykiet PL/EN/PT oraz jednoznaczne zastrzeżenie, że zapis/lock nie wysyła deklaracji do HMRC.
- Migrację `2026_11_10_000025_create_vat_returns_table` wykonano na lokalnym MySQL.
- Zachowano bez zmian równoległą pracę użytkownika w `resources/js/Pages/Landing/PlStronyInternetowe.tsx` oraz `package*.json`.

## Użyte agenty i skille

- @BackendEngineer
- @DatabaseEngineer
- @TestingEngineer
- Skill: delta-analysis
- Skill: database-migration
- Skill: filament-resource
- Skill: test-generation
- Skill: multi-language-check
- Skill: task-completion-report

## Zmodyfikowane / utworzone pliki

- `database/migrations/2026_11_10_000025_create_vat_returns_table.php`
- `database/factories/VatReturnFactory.php`
- `app/Models/VatReturn.php`
- `app/Policies/VatReturnPolicy.php`
- `app/Services/Finance/VatReturnSnapshotService.php`
- `app/Services/Finance/VatReturnPeriodGuard.php`
- `app/Actions/Finance/CreateVatReturnReviewAction.php`
- `app/Actions/Finance/ReviewVatReturnAction.php`
- `app/Actions/Finance/LockVatReturnAction.php`
- `app/Actions/Finance/SupersedeVatReturnAction.php`
- `app/Actions/Finance/PostJournalEntryAction.php`
- `app/Actions/Finance/GenerateDocumentNumberAction.php`
- `app/Services/Finance/VatDetailService.php`
- `app/Filament/Resources/VatReturnResource.php`
- `app/Filament/Resources/VatReturnResource/Pages/ListVatReturns.php`
- `app/Filament/Resources/VatReturnResource/Pages/ViewVatReturn.php`
- `app/Filament/Pages/VatDetailPage.php`
- `app/Console/Commands/AuditFinanceTenantData.php`
- `app/Models/Business.php`
- `app/Providers/AppServiceProvider.php`
- `app/Filament/Support/FilamentPermissionRegistry.php`
- `lang/{en,pl,pt}/finance.php`
- `tests/Feature/Finance/VatReturnWorkflowTest.php`
- `docs/plans/uk-finance-tax-module-plan.md`
- `.github/live-docs/{current-task,current-sprint,status-dashboard}.md`

## Walidacja końcowa

- ✅ PHP Coding Standard: Laravel Pint dla wszystkich plików PHP UKFIN-509.
- ➖ JavaScript / TypeScript: brak zmian JS/TS w UKFIN-509; pliki równolegle edytowane przez użytkownika nie były walidowane ani modyfikowane w tym zadaniu.
- ✅ Multi-language check: po 985 identycznych kluczy liściowych `finance.php` w PL/EN/PT; 0 missing i 0 extra.
- ✅ Multi-tenancy compliance: audit lokalnego MySQL zakończony zerem nieprawidłowości, łącznie z hashami i źródłami snapshotów.
- ✅ Testy dedykowane: 5 passed / 44 assertions.
- ✅ Finance regression: 264 passed / 1 skipped / 1883 assertions.
- ✅ Full regression: 773 passed / 11 skipped / 3327 assertions; pozostało dokładnie 9 znanych baseline failures niezwiązanych z finansami.
- ✅ Migracja MySQL `000025` zastosowana poprawnie.

## Uwagi i rekomendacje

- Ten etap nie realizuje HMRC OAuth, obligations ani submission i nie składa prawnej deklaracji VAT; te funkcje pozostają w fazie 6.
- Locked snapshot jest celowo nieodwracalny. Korekty należy księgować w późniejszym okresie VAT i dokumentować przez nowe źródła/snapshoty zgodnie z workflow korekt.
- Znane baseline failures dotyczą SocialAuth, meta strony głównej, waluty Billing, starych tras Profile oraz pojedynczego testu Inertia ServicePage; UKFIN-509 nie dodał nowych regresji.

## Next steps (opcjonalnie)

- UKFIN-510: Accountant pack ZIP z CSV/XLSX/PDF, manifestem załączników i hashami, oparty na locked VAT Return snapshot.
- UKFIN-511: golden dataset zaakceptowany ręcznie przez księgowego UK.

**Raport wygenerowany automatycznie przez WebsiteExpert**
