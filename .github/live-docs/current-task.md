# Current Task

**Status:** Brak aktywnego zadania
**Ostatnio zakończone:** UKFIN-502 — Profit & Loss (2026-11-09)
**Następny planowany etap:** UKFIN-503 — Balance Sheet
**Plan nadrzędny:** `docs/plans/uk-finance-tax-module-plan.md`

## Wynik ostatniego etapu

- Tenantowy rachunek zysków i strat w GBP dla wybranego okresu księgowego.
- Sekcje sales, cost of sales, operating expenses, other income, other expenses i corporation tax.
- Zysk brutto, operacyjny, przed opodatkowaniem i netto.
- Opcjonalny okres porównawczy oraz wariancja per konto, sekcja i wynik.
- Obliczenia w integer pennies na podstawie ledgeru `posted`/`reversed`.
- Strona Filament i prywatny eksport CSV chronione przez `view_finance` oraz tenant validation.
- Tłumaczenia PL/EN/PT i testy serwisu, korekt, reversal, RBAC, strony oraz CSV.

## Walidacja

- Dedykowane testy UKFIN-502: 7 passed / 51 assertions.
- P&L + Trial Balance + General Ledger: 19 passed / 146 assertions.
- Finance regression: 224 passed / 1 skipped / 1420 assertions.
- Pełna regresja: 733 passed / 9 znanych baseline failures / 11 skipped / 2864 assertions.
- `finance:audit-tenants --json`: `valid: true`, zero nieprawidłowości.
- Pint, self-review, parity tłumaczeń 631/631/631, skan nazw firmy i `git diff --check`: bez nowych problemów.

## Następny krok

- [ ] `UKFIN-503` — Balance Sheet.
