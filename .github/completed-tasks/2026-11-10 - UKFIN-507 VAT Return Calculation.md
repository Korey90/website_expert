# 2026-11-10 - UKFIN-507 VAT Return Calculation

**Status:** Zakończone sukcesem
**Data realizacji:** 2026-11-10
**Czas trwania:** 1 etap roboczy

## Cel zadania

Wyliczyć audytowalny, roboczy zestaw dziewięciu pól UK VAT Return bezpośrednio z uzgodnionego VAT Detail, zachowując mapowania tax codes, dokładność pensów oraz jawne blokady przed review dla nieobsługiwanych schematów i niekompletnych danych.

## Zakres wykonanych prac

- Dodano tenantowy `VatReturnCalculationAction`, którego jedynym źródłem kwot jest istniejący, uzgodniony `VatDetailService`.
- Pola 1, 2, 4 i 6–9 są agregowane według jawnego `vat_return_mapping` każdego tax code i właściwej metryki: output VAT, input VAT, net sales albo net purchases.
- Pole 3 jest zawsze sumą pól 1 i 2, a pole 5 bezwzględną różnicą pól 3 i 4; osobno zachowany jest kierunek `payable`, `repayable` lub `nil`.
- Wszystkie obliczenia bazowe działają w integer pennies. Preview HMRC dla pól 1–5 zachowuje dwa miejsca dziesiętne, a dla 6–9 ucina wartość do pełnych funtów w kierunku zera i zachowuje raw GBP do audytu.
- Dodano payload preview używający nazw pól VAT MTD API: `vatDueSales`, `vatDueAcquisitions`, `totalVatDue`, `vatReclaimedCurrPeriod`, `netVatDue`, `totalValueSalesExVAT`, `totalValuePurchasesExVAT`, `totalValueGoodsSuppliedExVAT` i `totalAcquisitionsExVAT`.
- Walidacja mapowań wykrywa niedozwolone pola, błędny kształt, jednoczesne mapowanie output VAT do 1 i 2, brakujące mapowania dla niezerowych kwot oraz brak par 6/8 i 7/9.
- Dodano uzgodnienie z kontrolnymi sumami VAT Detail dla output VAT i input VAT.
- Dodano blokady profilu: brak profilu, brak rejestracji VAT, waluta inna niż GBP, brak VAT number, brak lub nieobsługiwany scheme oraz okres poza datami obowiązywania rejestracji.
- Scheme `standard` i `annual` są obsługiwane. `cash` i `flat_rate` blokują review, ponieważ obecny ledger bazuje na invoice tax point i nie implementuje odrębnej logiki tych schematów.
- Niezmapowane `outside_scope` jest prezentowane jako jawne wyłączenie z ostrzeżeniem; niezerowy outside-scope VAT nadal blokuje review.
- Nieaktywne tax codes i ledger adjustments pozostają widoczne jako ostrzeżenia; credit notes i reversals zachowują znak ledgeru.
- Rozszerzono `/admin/vat-detail` o status gotowości, dziewięć pól, opisy i nazwy HMRC, raw GBP dla pól 6–9, kierunek net VAT oraz listy problemów i ostrzeżeń.
- Wynik jest wyłącznie preview: etap nie zapisuje deklaracji, nie blokuje okresu i nie komunikuje się z HMRC.
- Dodano komplet tłumaczeń PL/EN/PT oraz testy obliczeń, precyzji, wartości ujemnych, błędnych mapowań, profilu, effective dates, outside scope i tenant isolation.

## Użyte agenty i skille

- @BackendEngineer
- @TestingEngineer
- @DocumentationEngineer
- Skill: delta-analysis
- Skill: test-generation
- Skill: code-review
- Skill: task-completion-report

## Zmodyfikowane / utworzone pliki

- `app/Actions/Finance/VatReturnCalculationAction.php`
- `app/Filament/Pages/VatDetailPage.php`
- `resources/views/filament/pages/vat-detail.blade.php`
- `lang/pl/finance.php`
- `lang/en/finance.php`
- `lang/pt/finance.php`
- `tests/Feature/Finance/VatReturnCalculationActionTest.php`
- `tests/Feature/Finance/VatDetailReportTest.php`
- `docs/plans/uk-finance-tax-module-plan.md`
- `.github/live-docs/current-task.md`
- `.github/live-docs/current-sprint.md`
- `.github/live-docs/status-dashboard.md`

## Walidacja końcowa

- ✅ PHP Coding Standard: Laravel Pint dla plików UKFIN-507.
- ✅ JavaScript / TypeScript: brak zmian JS/TS; istniejący status ESLint bez zmian.
- ✅ Multi-language check: identyczne 853 klucze liściowe `finance.php` w PL, EN i PT; zero brakujących i dodatkowych kluczy.
- ✅ Multi-tenancy compliance: test lookup profilu obcej firmy oraz `finance:audit-tenants --json` z `valid: true` i zerem nieprawidłowości.
- ✅ Dedykowane testy `VatReturnCalculationAction`: 6 passed / 55 assertions.
- ✅ Raporty finansowe z VAT workspace: 46 passed / 481 assertions.
- ✅ Finance regression: 251 passed / 1 skipped / 1755 assertions.
- ✅ Full validation: 760 passed / 9 znanych baseline failures / 11 skipped / 3199 assertions.
- ✅ `git diff --check`, skan debug/TODO oraz skan starych nazw firmy bez nowych problemów.

## Uwagi i rekomendacje

Mapowanie VAT Return jest obecnie odczytywane z bieżącego katalogu `TaxCode`, a nie z niezmiennego snapshotu journal line. Zmiana konfiguracji tax code po zaksięgowaniu może więc zmienić historyczny preview pól, choć nie zmieni kwot VAT Detail. Przed produkcyjnym review należy zatwierdzić z księgowym UK mapowania custom codes, domestic reverse charge, transakcje NI–EU do pól 8/9 oraz kwalifikację outside-scope supplies do pola 6. Implementacja opiera semantykę i precyzję na HMRC VAT Notice 700/12 oraz HMRC VAT MTD API 1.0. Dziewięć awarii pełnego pakietu dokładnie odpowiada wcześniejszemu baseline i nie dotyczy Finance ani UKFIN-507.

## Next steps (opcjonalnie)

- `UKFIN-508` — audytowalne VAT adjustments z wymaganym reason, attachment i approval.
- `UKFIN-509` — review, lock i niezmienny snapshot VAT Return; wtedy należy utrwalić mapowania użyte do kalkulacji.
- Przed wysyłką do HMRC przeprowadzić formalny review golden dataset przez prawdziwego księgowego UK.

**Raport wygenerowany automatycznie przez Website Expert NI**
