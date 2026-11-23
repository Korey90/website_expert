# Universal Credit evidence pack - user guide

- Status: operational guide for local UKFIN-513 testing
- Audience: a Northern Ireland sole trader, authorised bookkeeper or reviewer
- System: Website Expert NI

## 1. What this module does

The `Finance > Universal Credit` module prepares monthly cash-basis values and evidence for the self-employed Universal Credit task.

It does not:

- send data to Universal Credit;
- calculate the final Universal Credit award, minimum income floor, carried losses or surplus earnings;
- replace profit and loss, Self Assessment or VAT Return reporting;
- decide whether an unusual expense is legally allowable.

The claimant must still sign in to the official Universal Credit account, complete `Report your income and expenses`, and then record the confirmation in Website Expert NI.

## 2. Before the first period

1. Confirm that `Accounting profile` uses entity type `Sole trader`.
2. Make sure the role has:
   - `view_universal_credit_reporting` to view periods;
   - `manage_universal_credit_reporting` to prepare, review and lock them;
   - `export_universal_credit_pack` to export reports and evidence packs.
3. Open `Finance > Universal Credit` and select `Configure Universal Credit`.
4. Enable reporting.
5. If VAT registered, choose one consistent method:
   - `Amounts include VAT`; or
   - `Amounts exclude recoverable VAT`.
6. Enter the date from which that VAT method applies.
7. Optionally save the usual start day as a reminder. It never replaces the exact dates shown in the claimant's UC account.

## 3. Monthly workflow

### Step 1 - Create the exact assessment period

Copy the `From` and `To` dates from the claimant's Universal Credit account. Do not derive the period from a calendar month or accounting period. The range is inclusive and cannot exceed 31 days.

Create the period even when there was no income or expense. A zero period must still be reported to Universal Credit.

### Step 2 - Collect cash sources

Select `Collect cash sources`. The action is idempotent and can be repeated while the period is a draft.

The report uses the date money was actually received or paid. An invoice or supplier bill is not enough on its own when no cash movement occurred in the assessment period.

Check every collected line. A line must end as either:

- `Included`, with an allowed UC category; or
- `Excluded`, with a clear reason.

Do not hide personal, duplicated or disallowed activity. Retain it as an explicit exclusion when it is relevant to the source reconciliation.

### Step 3 - Apply business use

Income is reported at 100 percent. For a mixed business and personal expense, enter only the business percentage and explain the basis.

Example: a GBP 36 internet payment with 50 percent business use has a reportable amount of GBP 18.

### Step 4 - Add cash events missing from structured records

Use `Add manual cash event` for a real event that was not collected from invoices, expenses, payments or bank records. Examples include cash receipts, tips, payment in kind, an eligible grant, tax or NI refund, asset transfer or sale, or a tax/NI/pension/VAT payment.

Enter:

- the actual cash date inside the period;
- the original amount and currency;
- a GBP exchange-rate snapshot for a foreign currency;
- a clear description;
- an evidence file or a reason why evidence is unavailable.

A manual event is immutable. If it is wrong, void it with a reason and add a replacement.

### Step 5 - Add simplified expenses where selected

The module supports:

- vehicle mileage;
- working from home;
- living at business premises.

Do not claim both a simplified amount and conflicting actual costs in the same period. The system blocks known double claims, but the user remains responsible for the factual choice and inputs.

### Step 6 - Check evidence and calculate

Attach receipts, invoices, bank evidence and other supporting records. Uploaded files are private and must pass the security scan before they can be included in the ZIP.

Select `Calculate`, then read the readiness indicator:

- `Ready` - no blocking issue or warning;
- `Warnings` - calculation can continue, but every warning must be reviewed and explicitly accepted;
- `Blocked` - fix all blocking issues before review.

Recalculate after changing any draft classification or simplified expense.

### Step 7 - Review and lock

Select `Start review`, confirm the declarations and add useful notes. Review creates a SHA-256 snapshot.

Select `Lock period` and enter a meaningful reason. Locking makes the reviewed values immutable. If a locked period is wrong, do not edit it directly. Supersede it and prepare a replacement.

### Step 8 - Generate and inspect the evidence pack

Select `Generate evidence pack`. Generation runs through the queue. The evidence-pack table refreshes automatically.

When the status is `Completed`, download the private ZIP. Check at least:

- `01-summary/universal-credit-summary.pdf`;
- `01-summary/review-and-declaration.pdf`;
- `02-details/income-detail.csv`;
- `02-details/expense-detail.csv`;
- `02-details/excluded-and-private.csv`;
- `02-details/missing-evidence-and-warnings.csv`;
- `manifest.sha256.json`.

Only clean, integrity-checked files are copied into the ZIP. Missing, pending, rejected or changed files remain visible in the manifest as exclusions.

### Step 9 - Report manually and retain confirmation

Use the values from the locked summary to complete the official Universal Credit task. After successful manual submission, select `Mark as reported`.

You may retain:

- the official confirmation reference; and
- a screenshot or confirmation PDF.

The confirmation metadata is immutable. It is an internal audit record, not proof that Website Expert NI transmitted anything to Universal Credit.

## 4. Corrections

Use `Supersede` with a detailed reason when a draft, reviewed, locked or reported period must no longer be treated as current. Preserve the old period for audit and create a replacement for the same exact dates after the active overlap is removed.

Never overwrite a locked snapshot, generated ZIP or reporting confirmation.

## 5. Evidence checklist

Before review, confirm that the period contains or explains:

- every business receipt, including card, cash, cheque and bank transfer;
- payment in kind, tips, eligible grants and business tax or NI refunds;
- transfers or sales of assets previously reported as an expense;
- every business payment actually made inside the period;
- the business share of mixed-use costs;
- mileage, home-working hours or premises-resident inputs where simplified expenses are used;
- bank statements and reconciliation for bank activity;
- receipts and invoices for included costs;
- an explicit reason for each excluded item and each unavailable document;
- consistent VAT treatment for the whole applicable period.

## 6. Official references

These pages were checked on 23 November 2026:

- [nidirect: Report business income and expenses if you're self-employed](https://www.nidirect.gov.uk/articles/report-business-income-and-expenses-if-youre-self-employed)
- [nidirect: Business expenses you can report if you're self-employed](https://www.nidirect.gov.uk/articles/business-expenses-you-can-report-if-youre-self-employed)
- [GOV.UK: Report business income and expenses to Universal Credit if you are self-employed](https://www.gov.uk/government/publications/universal-credit-for-the-self-employed/how-to-report-your-earnings-from-self-employment)
- [GOV.UK: Business expenses you can report to Universal Credit if you are self-employed](https://www.gov.uk/government/publications/universal-credit-for-the-self-employed/business-expenses-you-can-report-to-universal-credit-if-you-are-self-employed)

Official guidance and legislation can change. Recheck them before production acceptance and whenever a policy version is updated.
