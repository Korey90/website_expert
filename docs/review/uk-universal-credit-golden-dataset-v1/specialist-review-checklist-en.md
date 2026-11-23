# Universal Credit golden dataset v1 - specialist review checklist

- Review status: accepted on paper
- Dataset: `uk-universal-credit-golden-v1`
- Immutable fixture SHA-256: `ef92a67e1c458f001334f4659f9dd87bc54986cd2660c3fd757dd3caa6f0436a`
- Assessment period: 23 November 2026 to 22 December 2026
- Entity: Northern Ireland sole trader, not VAT registered

The completed responses and reviewer details remain on the paper original. The repository decision record is `specialist-sign-off-record.md`, reference `UC-GOLDEN-V1-PAPER-20261124`.

## Purpose

Please decide whether the classifications, calculations, wording and evidence presentation are suitable for a self-employed claimant completing the monthly Universal Credit task in Northern Ireland.

This is not a request to review HMRC Self Assessment, VAT Return or statutory accounts.

## Expected candidate result

| Measure | Expected value |
|---|---:|
| Business income | GBP 1,290.00 |
| Allowable expenses | GBP 199.00 |
| Net income | GBP 1,091.00 |
| Included cash items | 5 |
| Explicitly excluded cash items | 1 |
| Simplified expenses | 2 |

Expense total composition:

- mixed-use internet and telephone: GBP 120.00 paid, 50 percent business use, GBP 60.00 reportable;
- eligible NI payment: GBP 35.00;
- business loan interest: GBP 50.00 paid, capped at GBP 41.00;
- car mileage: 100 miles at 45 pence, GBP 45.00;
- home working: 60 hours, GBP 18.00;
- personal purchase: GBP 75.00, explicitly excluded.

## Files to inspect

Review pack: `docs/demo/uk-universal-credit-golden-v1/universal-credit-golden-v1.zip`

Integrity metadata: `docs/demo/uk-universal-credit-golden-v1/artifact.sha256.json`

Open the generated ZIP and inspect:

- `01-summary/universal-credit-summary.pdf`;
- `01-summary/review-and-declaration.pdf`;
- `02-details/income-detail.csv`;
- `02-details/expense-detail.csv`;
- `02-details/excluded-and-private.csv`;
- `02-details/bank-and-cash-reconciliation.xlsx`;
- `02-details/missing-evidence-and-warnings.csv`;
- `02-details/calculation-policy.pdf`;
- `manifest.sha256.json`.

## Review questions

Mark each item `Pass`, `Fail` or `Needs clarification`.

| Area | Question | Decision | Notes |
|---|---|---|---|
| Period | Are the exact inclusive assessment-period dates handled correctly? |  |  |
| Cash basis | Are receipts and payments assigned by actual cash date rather than invoice date? |  |  |
| Zero period | Does the workflow also allow a valid zero-income and zero-expense report? |  |  |
| Income | Are service receipts and tips presented in the correct income categories? |  |  |
| Mixed use | Is the 50 percent business share clear, supportable and applied only to the expense? |  |  |
| Tax and NI | Is the included NI payment described and categorised correctly for this scenario? |  |  |
| Loan interest | Is the monthly GBP 41 cap applied and disclosed correctly? |  |  |
| Mileage | Is 100 business miles at 45 pence appropriate for this scenario? |  |  |
| Home working | Is the 60-hour flat-rate amount correct and clearly separated from actual home costs? |  |  |
| Exclusions | Is the personal GBP 75 purchase retained visibly but excluded from totals? |  |  |
| Evidence | Are missing synthetic documents shown as warnings rather than silently treated as present? |  |  |
| VAT | Is the non-VAT-registered scenario unambiguous? |  |  |
| Totals | Do all PDF, XLSX and CSV totals agree to the penny? |  |  |
| Review | Are warnings, review, lock, supersede and manual reporting confirmation understandable? |  |  |
| Scope | Is it clear that the system does not submit to Universal Credit or calculate the award? |  |  |
| Terminology | Is the English terminology suitable for a claimant and bookkeeper in Northern Ireland? |  |  |

## Required sign-off

| Field | Reviewer's response |
|---|---|
| Reviewer name | |
| Professional role or relevant UC experience | |
| Organisation, if applicable | |
| Review date | |
| Decision: `Accepted`, `Accepted with listed changes`, or `Rejected` | |
| Signature or traceable approval reference | |

Material comments:

1.
2.
3.

## Version control rule

Do not edit fixture v1 after sign-off. Any change to dates, cases, expected totals or policy assumptions must create a new version, a new SHA-256 and a new specialist decision.
