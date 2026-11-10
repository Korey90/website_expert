# Current Task

**Status:** Brak aktywnego zadania
**Ostatnio zakończone:** UKFIN-506 — VAT Detail
**Data zakończenia:** 2026-11-10
**Plan nadrzędny:** `docs/plans/uk-finance-tax-module-plan.md`

## Wynik ostatniego etapu

- Dodano tenantowy raport VAT Detail w GBP dla inkluzywnego zakresu dat księgowych.
- Net sales/purchases oraz output/input VAT są rekonstruowane z journal lines `posted`/`reversed`, a nie z bieżących sum dokumentu.
- Raport obsługuje standard, reduced, zero, exempt, outside scope i reverse charge oraz prezentuje pełne mapowanie tax code do pól VAT Return.
- Faktury, credit notes, expenses i purchase credit notes są mapowane do dokumentów i kontrahentów, również po soft delete.
- Reversals pozostają osobnymi ujemnymi ruchami w swojej dacie; ręczne linie VAT bez tax code są widoczne jako `UNKNOWN` adjustments.
- Output i input VAT są uzgadniane co do pensa z kontami kontrolnymi rozpoznawanymi po podtypie, także dla kont niestandardowych.
- Dodano stronę Filament `/admin/vat-detail`, prywatny CSV, `view_finance`, ścisłą walidację dat, tenant isolation i tłumaczenia PL/EN/PT.
- Nie dodano migracji ani automatycznego wyliczania/składania VAT Return.

## Walidacja

- Dedykowane UKFIN-506: 5 passed / 85 assertions.
- Raporty finansowe: 40 passed / 422 assertions.
- Finance: 245 passed / 1 skipped / 1696 assertions.
- Pełny pakiet: 754 passed / 9 znanych baseline failures / 11 skipped / 3140 assertions.
- Tenant audit: `valid: true`, zero nieprawidłowości.
- Pint, parity 810 kluczy PL/EN/PT, kontrola trasy, nazwy firmy i `git diff --check`: bez nowych problemów.

## Następne zadanie

- `UKFIN-507` [M] — VatReturnCalculationAction dla 9 pól.
