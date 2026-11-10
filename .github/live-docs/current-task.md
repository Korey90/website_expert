# Current Task

**Status:** Brak aktywnego zadania
**Ostatnio zakończone:** UKFIN-507 — VatReturnCalculationAction dla 9 pól (2026-11-10)
**Następne zadanie:** UKFIN-508 — VAT adjustments z reason, attachment i approval
**Plan nadrzędny:** `docs/plans/uk-finance-tax-module-plan.md`

## Stan przekazania

- Preview dziewięciu pól UK VAT Return działa w `/admin/vat-detail` i bazuje na uzgodnionym VAT Detail.
- Kalkulacja, mapowania tax codes, precyzja HMRC, status payable/repayable oraz blokady gotowości są pokryte testami.
- Cash Accounting i Flat Rate pozostają jawnie zablokowane; brak zapisu, locka i wysyłki do HMRC.
- Finance: 251 passed / 1 skipped / 1755 assertions.
- Pełny pakiet: 760 passed / 9 znanych baseline failures / 11 skipped / 3199 assertions.
- Tenant audit: `valid: true`, zero nieprawidłowości.

## Następny krok

Rozpocząć UKFIN-508 od delta analysis istniejących financial attachments, approval patterns i immutable ledger adjustments; korekty VAT muszą zachować reason, dowód, autora, akceptację i pełny tenant scope.
