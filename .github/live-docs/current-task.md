# Current Task

**Status:** Brak aktywnego zadania
**Ostatnio zakończone:** UKFIN-501 — Trial Balance per period i comparison (2026-11-07)
**Następny planowany etap:** UKFIN-502 — Profit & Loss
**Plan nadrzędny:** `docs/plans/uk-finance-tax-module-plan.md`

## Wynik ostatniego etapu

- Tenantowy Trial Balance w GBP dla wybranego okresu księgowego.
- Saldo otwarcia, obroty Winien/Ma, saldo końcowe, opcjonalne porównanie i wariancja.
- Jawna kontrola bilansowania oraz eksport CSV chroniony przez `view_finance`.
- Obliczenia w integer pennies na bazie niezmiennych `journal_lines.debit_gbp` i `credit_gbp`.
- Historycznie nieaktywne konta z saldem lub obrotem pozostają widoczne.
- Tłumaczenia PL/EN/PT oraz testy serwisu, reversal, tenant isolation, RBAC, UI i CSV.

## Walidacja

- Dedykowane testy UKFIN-501 + General Ledger: 12 passed / 95 assertions.
- Finance regression: 217 passed / 1 skipped / 1369 assertions.
- Pełna regresja: 726 passed / 9 znanych baseline failures / 11 skipped / 2813 assertions.
- `finance:audit-tenants --json`: `valid: true`, zero nieprawidłowości.
- Pint, parity tłumaczeń 596/596/596 i kontrola nazw firmy: bez nowych problemów.

## Następny krok

- [ ] `UKFIN-502` — Profit & Loss.
