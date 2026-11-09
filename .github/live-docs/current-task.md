# Current Task

**Status:** Brak aktywnego zadania
**Ostatnio zakończone:** UKFIN-504 — Cash Flow w wersji zarządczej
**Data zakończenia:** 2026-11-09
**Plan nadrzędny:** `docs/plans/uk-finance-tax-module-plan.md`

## Wynik ostatniego etapu

- Dodano tenantowy Management Cash Flow w GBP metodą bezpośrednią.
- Raport uzgadnia saldo otwarcia, przepływ netto i saldo zamknięcia dokładnie w pensach.
- Ruchy są klasyfikowane jako operacyjne, inwestycyjne lub finansowe na podstawie kont przeciwstawnych.
- Transfery pomiędzy własnymi kontami pieniężnymi są wyłączone, a reversals pozostają audytowalne.
- Dostępna jest strona Filament `/admin/management-cash-flow` i prywatny eksport CSV.
- UI i eksport wymagają `view_finance`, walidują okresy w aktywnym tenantcie i mają komplet tłumaczeń PL/EN/PT.
- Raport jest jawnie opisany jako zarządczy, a nie ustawowy cash flow według IAS 7.

## Walidacja

- Dedykowane: 5 passed / 67 assertions.
- Regresja raportów: 30 passed / 274 assertions.
- Finance: 235 passed / 1 skipped / 1548 assertions.
- Pełny pakiet: 744 passed / 9 znanych baseline failures / 11 skipped / 2992 assertions.
- Tenant audit, Pint, parity tłumaczeń, trasa, skan nazw firmy i `git diff --check`: poprawne.

## Następny planowany etap

- `UKFIN-505` — Aged Receivables i Aged Payables.
- Przed implementacją należy wykonać delta analysis względem ledgeru, faktur sprzedażowych, wydatków/purchase credits, płatności i aktywnych reconciliation allocations.
