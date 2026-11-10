# 2026-11-10 - UKFIN-506 VAT Detail

**Status:** Zakończone sukcesem
**Data realizacji:** 2026-11-10
**Czas trwania:** 1 etap roboczy

## Cel zadania

Udostępnić księgowemu tenantowy szczegół VAT w GBP dla dowolnego okresu, pokazujący sprzedaż i zakupy netto, output/input VAT, tax codes i dokumenty źródłowe oraz uzgadniający kwoty VAT z ruchem kont kontrolnych ledgeru.

## Zakres wykonanych prac

- Dodano serwis raportowy czytający wyłącznie journal lines z wpisów `posted` i `reversed` w inkluzywnym zakresie dat.
- Output VAT jest liczony jako credit minus debit na kontach `liability/tax_payable`, a input VAT jako debit minus credit na kontach `asset/tax_receivable`.
- Konta kontrolne są rozpoznawane po podtypie, bez zależności od stałych kodów kont.
- Net sales i net purchases pochodzą z tax-coded linii niekontrolnych tego samego journalu i tax code.
- Invoice/CreditNote są klasyfikowane jako sprzedaż, Expense/PurchaseCreditNote jako zakupy, a manual journals są rozpoznawane według kierunku VAT i bazy netto.
- Reverse charge może wykazać w jednym wierszu bazę zakupu oraz równy output i input VAT.
- Obsłużono standard, reduced, zero, exempt, outside scope oraz pełne informacyjne mapowanie tax code do pól VAT Return.
- Reversals dziedziczą dokument źródłowy, ale pozostają osobnym ujemnym ruchem w swojej dacie księgowej.
- Linie VAT control bez tax code pozostają widoczne jako `UNKNOWN` adjustments, dzięki czemu raport nie ukrywa ręcznych korekt.
- Dokumenty źródłowe i kontrahenci są dostępni również po soft delete; kwoty walutowe zachowują oryginalną walutę dokumentu, a raport używa snapshotu GBP ledgeru.
- Wszystkie obliczenia odbywają się w integer pennies i są jawnie uzgadniane z ruchem output/input VAT na kontach kontrolnych.
- Dodano stronę Filament `/admin/vat-detail` z zakresem dat, KPI, metodologią, podsumowaniem kodów, tabelą dokumentów i stanem uzgodnienia.
- Dodano prywatny eksport CSV z `Cache-Control: private, no-store`.
- Strona i CSV wymagają `view_finance`; Form Request i serwis egzekwują aktywny tenant oraz ścisły format i kolejność dat.
- Dodano tłumaczenia PL/EN/PT i testy tax treatments, reverse charge, credits, FX, reversals, manual adjustments, custom accounts, soft deletes, RBAC, UI, CSV oraz tenant isolation.
- Nie dodano migracji, snapshotu VAT Return ani automatycznej deklaracji — wyliczenie 9 pól jest zakresem UKFIN-507.

## Użyte agenty i skille

- @BackendEngineer
- @FrontendEngineer
- @TestingEngineer
- @DocumentationEngineer
- Skill: delta-analysis
- Skill: filament-resource
- Skill: test-generation
- Skill: code-review
- Skill: multi-language-check
- Skill: task-completion-report

## Zmodyfikowane / utworzone pliki

- `app/Services/Finance/VatDetailService.php`
- `app/Http/Requests/Finance/VatDetailReportRequest.php`
- `app/Http/Controllers/Finance/VatDetailReportExportController.php`
- `app/Filament/Pages/VatDetailPage.php`
- `app/Filament/Support/FilamentPermissionRegistry.php`
- `resources/views/filament/pages/vat-detail.blade.php`
- `routes/web/admin.php`
- `lang/pl/finance.php`
- `lang/en/finance.php`
- `lang/pt/finance.php`
- `tests/Feature/Finance/VatDetailReportTest.php`
- `docs/plans/uk-finance-tax-module-plan.md`
- `.github/live-docs/current-task.md`
- `.github/live-docs/current-sprint.md`
- `.github/live-docs/status-dashboard.md`

## Walidacja końcowa

- ✅ PHP Coding Standard: Laravel Pint dla plików UKFIN-506.
- ✅ JavaScript / TypeScript: brak zmian w tym etapie; istniejący status ESLint bez zmian.
- ✅ Multi-language check: identyczne 810 kluczy liściowych `finance.php` w PL, EN i PT; zero brakujących i dodatkowych kluczy.
- ✅ Multi-tenancy compliance: `finance:audit-tenants --json` zwraca `valid: true` i zero błędów.
- ✅ Dedykowane testy UKFIN-506: 5 passed / 85 assertions.
- ✅ Trial Balance + P&L + Balance Sheet + Management Cash Flow + Aged AR/AP + VAT Detail + General Ledger: 40 passed / 422 assertions.
- ✅ Finance regression: 245 passed / 1 skipped / 1696 assertions.
- ✅ Full validation: 754 passed / 9 znanych baseline failures / 11 skipped / 3140 assertions.
- ✅ Trasa `admin.finance.vat-detail.csv`, self-review, skan starych nazw firmy i `git diff --check` bez nowych problemów.

## Uwagi i rekomendacje

Kwoty i identyfikator tax code są niezmiennymi danymi journal line, natomiast etykieta, status i mapowanie VAT Return są odczytywane z bieżącego katalogu `TaxCode`. Zmiana mapowania po zaksięgowaniu może więc zmienić prezentowane metadane historycznego wiersza, lecz nie jego kwoty ani uzgodnienie. Dla niestandardowego tax code z nieodliczalnym VAT, którego posting kapitalizuje część VAT w koszt, baza zakupu wymaga sprawdzenia przez księgowego. Manual journals bez dokumentu są klasyfikowane deterministycznie według kierunku VAT; wpisy niejednoznaczne pozostają jawne jako adjustments. Dziewięć awarii pełnego pakietu odpowiada wcześniejszemu baseline i nie dotyczy modułu Finance.

## Next steps (opcjonalnie)

- `UKFIN-507` — VatReturnCalculationAction dla 9 pól, z jawnym uzgodnieniem do VAT Detail.
- Przed traktowaniem mapowań jako podstawy deklaracji zatwierdzić custom tax codes i przypadki nieodliczalnego VAT z księgowym UK.

**Raport wygenerowany automatycznie przez Website Expert NI**
