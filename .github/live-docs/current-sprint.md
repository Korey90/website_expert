# Current Sprint

**Nazwa:** UK Finance & Tax — raporty finansowe
**Data rozpoczęcia:** 2026-11-07
**Status:** 🟡 IN PROGRESS
**Źródło:** `docs/plans/uk-finance-tax-module-plan.md`

**Cel:** użytkownik z `view_finance` może uzyskać sprawdzalne podstawowe sprawozdania finansowe w GBP z ledgeru dla wybranego okresu.

## Backlog

### In Progress

- Brak — następne zadanie: `UKFIN-502`.

### To Do

- [ ] `UKFIN-502` [M] — Profit & Loss.
- [ ] `UKFIN-503` [M] — Balance Sheet.
- [ ] `UKFIN-504` [M] — Cash Flow w wersji zarządczej.

### Done

- [x] `UKFIN-501` [M] — Trial Balance per period i comparison.
- [x] Faza 4 / `UKFIN-401–409` — bank reconciliation zamknięte testami przekrojowymi.

## Definition of Done

- Raporty czytają wyłącznie niezmienny tenantowy ledger i nie przeliczają ponownie snapshotów źródłowych.
- Wszystkie sumy księgowe są liczone bez dryfu pensów i mają jawne sumy kontrolne Winien/Ma.
- Okresy i porównania są walidowane względem aktywnego `business_id`.
- Historycznie nieaktywne konta z saldem pozostają widoczne.
- UI i eksporty wymagają `view_finance` oraz mają komplet tłumaczeń PL/EN/PT.
- Testy domenowe i pełna regresja nie dodają nowych błędów względem baseline.
- Dokumentacja opisuje semantykę każdego raportu i jego ograniczenia.

## Poza zakresem tego sprintu

- Produkcyjna integracja HMRC MTD VAT.
- Payroll/PAYE, Corporation Tax, Self Assessment i CIS.
- Automatyczne składanie jakichkolwiek deklaracji.
- Raporty UKFIN-505–511 i accountant review pack.
