# Current Sprint

**Nazwa:** UK Finance & Tax — subledger ageing i VAT workspace
**Data rozpoczęcia:** 2026-11-09
**Status:** 🟡 IN PROGRESS
**Źródło:** `docs/plans/uk-finance-tax-module-plan.md`

**Cel:** użytkownik z `view_finance` może uzyskać uzgodnione z ledgerem raporty wieku należności/zobowiązań, a następnie przygotować i zweryfikować robocze 9 pól VAT Return.

## Backlog

### In Progress

- [ ] `UKFIN-511` [M] — Golden dataset zaakceptowany przez księgowego.

### To Do

- [ ] Formalny gate księgowego UK dla golden datasetu i reguł podatkowych.

### Done

- [x] `UKFIN-510` [M] — Accountant pack ZIP.
- [x] `UKFIN-509` [M] — VAT return review/lock/snapshot workflow.
- [x] `UKFIN-508` [M] — VAT adjustments z reason, attachment i approval.
- [x] `UKFIN-507` [M] — VatReturnCalculationAction dla 9 pól.
- [x] `UKFIN-506` [M] — VAT Detail: output/input VAT, tax codes i dokumenty źródłowe.
- [x] `UKFIN-505` [M] — Aged Receivables i Aged Payables.
- [x] `UKFIN-504` [M] — Cash Flow w wersji zarządczej.
- [x] `UKFIN-503` [M] — Balance Sheet.
- [x] `UKFIN-502` [M] — Profit & Loss.
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
- HMRC OAuth, obligations, submissions i fraud-prevention headers — zakres fazy 6.
