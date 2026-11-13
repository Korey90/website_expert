# UK Finance golden dataset v1 — accountant review pack

## Status

**READY FOR ACCOUNTANT REVIEW — NOT YET ACCEPTED — NOT SUBMITTED TO HMRC**

This review package describes the versioned fictional dataset used to validate the Website Expert NI finance and UK VAT workspace. The executable source is:

- `resources/demo/uk-accountant-golden-dataset/v1.json`
- Dataset ID: `website-expert-ni-uk-finance-golden-v1`
- Schema version: `1`
- SHA-256: `6271a6a9e179c1a8769e7fd957f401fde892b0421ea2a73bdc7a0918fd2672a1`
- Period: 1 July–30 September 2026
- Basis: accrual, GBP functional currency, standard VAT accounting

All counterparties and transactions are fictional. The business name is the system name `Website Expert NI`. The VAT number in the test fixture is synthetic and the generated accountant pack exposes only its last four characters and SHA-256.

## What the automated test proves

`php artisan test tests/Feature/Finance/GoldenDatasetTest.php`

The test posts every fixture journal through the production posting engine and verifies, to the penny:

- double-entry Trial Balance;
- Profit and Loss classifications and net profit;
- Balance Sheet equation;
- management Cash Flow reconciliation;
- receivables/payables ageing reconciliation;
- VAT Detail control-account reconciliation;
- all nine VAT Return fields and their formulas;
- deterministic VAT Return snapshot and SHA-256;
- creation of the real UKFIN-510 accountant ZIP from the locked snapshot;
- redaction of the full VAT number and `NOT SUBMITTED TO HMRC` labelling.

Passing tests prove that code output matches this fixture. They do **not** prove that the fixture's tax treatments are professionally correct. That decision belongs to the named UK accountant completing the checklist and sign-off.

## Scenario map

The fixture contains:

- share capital and bank opening movement;
- 20% standard, 5% reduced, zero-rated, exempt and outside-scope sales;
- an EUR invoice represented by an immutable GBP exchange-rate snapshot;
- standard, reduced, zero-rated, exempt and outside-scope purchases;
- a reverse-charge service purchase;
- a capital equipment purchase included in VAT purchases but excluded from P&L expenses;
- customer and supplier credits;
- a full journal reversal;
- customer receipt/refund and supplier payment/refund;
- bank charge and interest;
- a documented output VAT accounting adjustment.

The v1 fixture deliberately leaves NI–EU goods boxes 8 and 9 at zero. A reviewer must confirm whether a separate accepted scenario is required before those boxes are enabled for real use.

## Expected control totals

| Control | Expected |
|---|---:|
| VAT Box 1 | £300.00 |
| VAT Box 2 | £0.00 |
| VAT Box 3 | £300.00 |
| VAT Box 4 | £225.00 |
| VAT Box 5 payable | £75.00 |
| VAT Box 6 | £2,100 |
| VAT Box 7 | £1,450 |
| VAT Box 8 | £0 |
| VAT Box 9 | £0 |
| Trial Balance debit / credit | £13,235.00 / £13,235.00 |
| Net profit | £1,165.00 |
| Balance Sheet | £12,195.00 |
| Closing cash | £10,475.00 |
| Receivables / payables | £995.00 / £730.00 |

## Review procedure

1. Verify the fixture hash above before review.
2. Run the dedicated test and retain its output.
3. Generate the accountant ZIP from the resulting locked VAT Return in a non-production review database.
4. Check the workbook, PDF, CSV files, source journals and hash manifest against the checklist.
5. Record every required change. Do not edit the accepted fixture in place; create a new version and hash.
6. Complete `sign-off.md`. Only a real reviewer may change the status from `pending`.

## External reviewer hand-off

Use these documents for the independent engagement:

- [request for quotation](../../legal/uk-vat-mtd-accounting-review-request-for-quotation.md);
- [B2B services agreement](../../legal/uk-vat-mtd-accounting-review-b2b-agreement.md); and
- [bookkeeper review guide](../uk-vat-mtd-bookkeeper-review-guide.md).

The request deliberately contains no target price. The reviewer proposes the fee and commercial terms, which are inserted into the agreement only after written acceptance.

## Scope boundary

This dataset does not cover Cash Accounting, Flat Rate Scheme, margin schemes, partial exemption, CIS, payroll/PAYE, Corporation Tax computation, Self Assessment, capital allowances or HMRC submission. These require their own specifications and professional review.
