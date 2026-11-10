# 2026-11-10 - UKFIN-508 VAT adjustments workflow

**Status:** Zakończone sukcesem
**Data realizacji:** 2026-11-10
**Czas trwania:** 1 etap roboczy

## Cel zadania

Dodać audytowalny workflow korekt VAT, który rozróżnia normalne korekty rachunkowe od błędów poprzednich deklaracji, wymaga przyczyny i czystego załącznika przed akceptacją, a po zatwierdzeniu tworzy niezmienny, zbilansowany zapis ledgeru wpływający wyłącznie na właściwy output albo input VAT.

## Zakres wykonanych prac

- Dodano tenantowy model `VatAdjustment` i migrację z numerem `VADJ`, statusami `draft`, `approved`, `posted`, typem korekty, stroną output/input, kierunkiem, metodą HMRC, datami, kwotą GBP i pełnym audytem użytkowników.
- Dodano Actions create/update/approve/post z blokadami DB, kontrolą aktywnego membership, rozdzielonymi uprawnieniami `manage_finance`, `approve_finance`, `post_finance` i idempotencją księgowania.
- Walidator wymusza tenantowe, aktywne tax codes oraz konta control/contra, zgodność mapowania box 1/2 albo 4, dodatnią kwotę, reason, prawidłowe daty i bezpieczną metodę korekty.
- Akceptacja wymaga co najmniej jednego prywatnego załącznika `vat_adjustment_evidence` ze statusem skanu `clean`; SHA-256 dowodów jest uwzględniany w hash source snapshotu journalu.
- Posting tworzy dwie zbilansowane linie: tax code znajduje się wyłącznie na VAT control line, dzięki czemu korekta wpływa na output/input VAT bez sztucznego zwiększania box 6/7.
- Obsłużono wszystkie cztery kombinacje output/input × increase/decrease, atomowy rollback dla zamkniętego okresu i powtórne wywołanie bez duplikatu journalu.
- Błędy poprzednich deklaracji mają metody `current_return` i `separate_notification`; deliberate wymaga osobnego zgłoszenia.
- Przy kalkulacji VAT Return błędy current-return są agregowane netto. Do £10,000 przechodzą bez testu procentowego; powyżej £10,000 muszą mieścić się w 1% box 6 i nie mogą przekroczyć £50,000.
- Korekty `separate_notification` pozostają w VAT account i VAT Detail, ale są jawnie wyłączone z bieżącego VAT Return, z ostrzeżeniem i bez fałszywego błędu uzgodnienia.
- Dodano Filament `/admin/vat-adjustments`: listę, create/edit/view, upload dowodu, approve, post, audit danych, dowodów i powiązanego journalu.
- Zarejestrowano policy, relację biznesu, permission registry, dokument type, numerację, tenant audit i rozpoznawanie źródła w VAT Detail.
- Dodano komplet tłumaczeń PL/EN/PT oraz testy workflow, RBAC, tenant isolation, progi HMRC, Filament, audit, idempotencję i ledger signs.
- Migracja została wykonana na lokalnym MySQL; `finance:audit-tenants --json` zwraca `valid: true` i zero nieprawidłowości.

## Użyte agenty i skille

- @BackendEngineer
- @DatabaseEngineer
- @TestingEngineer
- @DocumentationEngineer
- Skill: delta-analysis
- Skill: database-migration
- Skill: filament-resource
- Skill: test-generation
- Skill: code-review
- Skill: multi-language-check
- Skill: task-completion-report

## Zmodyfikowane / utworzone pliki

- `database/migrations/2026_11_10_000024_create_vat_adjustments_table.php`
- `database/factories/VatAdjustmentFactory.php`
- `app/Models/VatAdjustment.php`
- `app/Policies/VatAdjustmentPolicy.php`
- `app/Services/Finance/VatAdjustmentPayloadValidator.php`
- `app/Actions/Finance/CreateVatAdjustmentAction.php`
- `app/Actions/Finance/UpdateVatAdjustmentAction.php`
- `app/Actions/Finance/ApproveVatAdjustmentAction.php`
- `app/Actions/Finance/PostVatAdjustmentAction.php`
- `app/Actions/Finance/GenerateDocumentNumberAction.php`
- `app/Actions/Finance/VatReturnCalculationAction.php`
- `app/Services/Finance/VatDetailService.php`
- `app/Filament/Resources/VatAdjustmentResource.php`
- `app/Filament/Resources/VatAdjustmentResource/Pages/*.php`
- `app/Console/Commands/AuditFinanceTenantData.php`
- `app/Filament/Support/FilamentPermissionRegistry.php`
- `app/Models/Business.php`
- `app/Providers/AppServiceProvider.php`
- `config/finance.php`
- `lang/{pl,en,pt}/finance.php`
- `tests/Feature/Finance/VatAdjustmentWorkflowTest.php`
- `docs/plans/uk-finance-tax-module-plan.md`
- `.github/live-docs/current-task.md`
- `.github/live-docs/current-sprint.md`
- `.github/live-docs/status-dashboard.md`

## Walidacja końcowa

- ✅ PHP Coding Standard: `vendor/bin/pint --dirty`; `git diff --check` bez błędów. Pełny `pint --test` nadal raportuje zastany dług formatowania/line endings poza plikami zadania.
- ✅ JavaScript / TypeScript: brak zmian JS/TS, więc lint/build nie wymagał ponownego uruchomienia.
- ✅ Multi-language check: po 909 identycznych kluczy liściowych `finance.php` w PL, EN i PT; zero brakujących i dodatkowych.
- ✅ Multi-tenancy: `VatAdjustment` rozpoznany przez validator, audit danych finansowych `valid: true`, tenant reference tests zielone.
- ✅ Dedykowane testy UKFIN-508: 8 passed / 84 assertions.
- ✅ Finance regression: 259 passed / 1 skipped / 1839 assertions.
- ✅ Full validation: 768 passed / 9 znanych baseline failures / 11 skipped / 3279 assertions; po pełnym przebiegu rozszerzono wyłącznie test progu 1% o 4 zielone asercje.
- ✅ Migracja MySQL: `2026_11_10_000024_create_vat_adjustments_table` uruchomiona jako batch 17.
- ✅ Route check: cztery chronione trasy Filament dla list/create/view/edit.
- ✅ Skan debug/TODO i hardcoded UI dla nowych plików bez trafień.

## Uwagi i rekomendacje

Implementacja odzwierciedla HMRC VAT Notice 700/45: korekty przed submission wymagają jasnego powodu w ewidencji, a po submission metoda bieżącej deklaracji zależy od łącznej wartości netto błędów i box 6; deliberate oraz błędy poza limitem wymagają osobnego zgłoszenia. Zgodnie z VAT Notice 700/12 wpływ korekty current-return trafia do właściwego output/input VAT. System nie wysyła zgłoszenia do HMRC i nie zastępuje oceny księgowego. Mapowania tax codes nadal są odczytywane z bieżącego katalogu, dlatego UKFIN-509 powinien utrwalić je w niezmiennym snapshotcie review. Odwrócenie zaksięgowanej korekty odbywa się przez istniejący journal reversal, nie przez edycję lub usunięcie adjustmentu.

Źródła: [HMRC VAT Notice 700/45](https://www.gov.uk/guidance/how-to-correct-vat-errors-and-make-adjustments-or-claims-vat-notice-70045), [HMRC VAT Notice 700/12](https://www.gov.uk/guidance/how-to-fill-in-and-submit-your-vat-return-vat-notice-70012), [GOV.UK — correct VAT Return errors](https://www.gov.uk/submit-vat-return/correct-errors-in-your-vat-return), [GOV.UK — keeping VAT records](https://www.gov.uk/charge-reclaim-record-vat/keeping-vat-records).

## Next steps (opcjonalnie)

- `UKFIN-509` — review, lock i niezmienny snapshot VAT Return wraz z utrwaleniem mapowań i źródeł.
- Przed użyciem na prawdziwej deklaracji przekazać workflow i przykładowe korekty księgowemu UK do formalnej akceptacji.
- W fazie 6 dodać osobny, audytowalny kanał zgłoszeń/submissions HMRC; obecny etap nie komunikuje się z HMRC.

**Raport wygenerowany automatycznie przez Website Expert NI**
