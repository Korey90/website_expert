# Current Task

**Status:** Brak aktywnego zadania
**Ostatnio zakończone:** UKFIN-505 — Aged Receivables i Aged Payables
**Data zakończenia:** 2026-11-09
**Plan nadrzędny:** `docs/plans/uk-finance-tax-module-plan.md`

## Wynik ostatniego etapu

- Dodano tenantowy raport wieku należności i zobowiązań w GBP na wybrany dzień.
- Salda historyczne są rekonstruowane z journal lines `posted`/`reversed`, a nie z bieżącego `amount_due`.
- Raport rozdziela salda na current, 1–30, 31–60, 61–90 i 91+ dni oraz zachowuje kwoty ujemne.
- Faktury, koszty, płatności, credit notes, purchase credits, supplier refunds i reversals są mapowane do dokumentu źródłowego.
- Ręczne i nierozpoznane zapisy pozostają widoczne jako ledger adjustments, dzięki czemu AR i AP uzgadniają się z kontami kontrolnymi co do pensa.
- Dodano stronę Filament, prywatny CSV, `view_finance`, walidację daty, tenant isolation i tłumaczenia PL/EN/PT.
- Nie dodano migracji.

## Walidacja

- Dedykowane UKFIN-505: 5 passed / 63 assertions.
- Raporty finansowe: 35 passed / 337 assertions.
- Finance: 240 passed / 1 skipped / 1611 assertions.
- Pełny pakiet: 749 passed / 9 znanych baseline failures / 11 skipped / 3055 assertions.
- Tenant audit: `valid: true`, zero nieprawidłowości.
- Pint, parity 749 kluczy PL/EN/PT, kontrola trasy, nazwy firmy i `git diff --check`: bez nowych problemów.

## Następne zadanie

- `UKFIN-506` [M] — VAT Detail: output/input VAT, tax codes i dokumenty źródłowe.
