# Website Expert NI — UK VAT/MTD bookkeeper review guide

**Audience:** independent UK bookkeeper/accountant  
**Environment:** **Website Expert NI — HMRC Sandbox** plus UK Finance golden dataset v1  
**Review status:** independent review required; not an HMRC approval or real VAT filing  
**System contact:** [NAME / EMAIL]  
**Security contact:** [NAME / EMAIL / PHONE]  
**Review release/commit:** [IDENTIFIER]  
**Accountant ZIP SHA-256:** [INSERT BEFORE HAND-OFF]

## 1. Purpose

Please assess whether the reviewed release provides a coherent and adequately evidenced workflow for standard UK accrual accounting and VAT, and whether the HMRC MTD VAT sandbox workflow preserves the approved nine-box return and audit trail.

Your job is to test, challenge and document. A negative or conditional conclusion is acceptable. Do not change a conclusion to help the software pass a release gate.

The review has two distinct tracks:

1. **Accounting logic:** the versioned fictional golden dataset and generated accountant pack.
2. **HMRC integration:** the isolated deployed tenant **Website Expert NI — HMRC Sandbox** and its existing sandbox submission evidence.

The HMRC tenant contains a controlled nil-return journey. It is not expected to contain the full golden accounting dataset. Review each track independently, then assess whether the boundary between the locked VAT Return and HMRC payload is adequately evidenced.

## 2. Safety rules — read before signing in

1. Use only your named account. Do not share credentials or sessions.
2. Enable authenticator-app MFA before testing HMRC functions.
3. In the top-bar workspace selector choose **Website Expert NI — HMRC Sandbox**.
4. Never enter or inspect the operational **Website Expert NI** workspace. If it is visible or selected, stop and contact the owner.
5. Do not enter real customer, supplier, employee or banking data. Test transactions must be fictional.
6. Do not disconnect HMRC, replace the sandbox VRN, change fraud-header configuration or alter environment settings.
7. Do not submit or re-submit a VAT Return unless the owner gives written approval naming the sandbox period and test window.
8. Never copy access/refresh tokens, MFA codes, MFA secrets, full VRNs, fraud-header values or credentials into notes or screenshots.
9. Do not run vulnerability scanners, load tests or attempts to bypass permissions. This is an accounting/UAT review, not a penetration test.
10. Report wrong-tenant access, an unexpected real record, an ambiguous HMRC outcome or a suspected data leak immediately and stop the affected test.

## 3. Materials required before review

- [ ] signed B2B agreement or engagement letter;
- [ ] accepted Provider quotation;
- [ ] unique active account and login URL;
- [ ] access only to **Website Expert NI — HMRC Sandbox**;
- [ ] release/commit identifier;
- [ ] golden dataset README and source or generated pack;
- [ ] accountant ZIP SHA-256 and internal manifest;
- [ ] accountant checklist and sign-off;
- [ ] redacted HMRC sandbox evidence or access to relevant UI records;
- [ ] secure location for findings; and
- [ ] named product and security contacts.

Do not start the substantive review if the release identifier, dataset hash or evidence location is missing.

## 4. Fixed reference data

### Golden dataset identity

- Dataset ID: **website-expert-ni-uk-finance-golden-v1**
- Schema version: **1**
- Expected SHA-256: **6271a6a9e179c1a8769e7fd957f401fde892b0421ea2a73bdc7a0918fd2672a1**
- Period: 1 July to 30 September 2026
- Basis: accrual accounting, GBP functional currency, standard VAT accounting
- Counterparties and transactions: fictional

If the dataset hash differs, stop. Do not approve a modified v1 fixture. A changed fixture needs a new version and fresh review.

### Expected controls

| Control | Expected | Reviewer result |
|---|---:|---:|
| VAT Box 1 | £300.00 | |
| VAT Box 2 | £0.00 | |
| VAT Box 3 | £300.00 | |
| VAT Box 4 | £225.00 | |
| VAT Box 5 payable | £75.00 | |
| VAT Box 6 | £2,100 | |
| VAT Box 7 | £1,450 | |
| VAT Box 8 | £0 | |
| VAT Box 9 | £0 | |
| Trial Balance debit / credit | £13,235.00 / £13,235.00 | |
| Net profit | £1,165.00 | |
| Balance Sheet | £12,195.00 | |
| Closing cash | £10,475.00 | |
| Receivables / payables | £995.00 / £730.00 | |

Matching totals do not by themselves prove the tax treatment is correct. Review the scenario and posting behind every control.

## 5. Review sequence

### Phase A — integrity and scope

- [ ] Record the release/commit identifier.
- [ ] Verify the golden dataset SHA-256.
- [ ] Verify the accountant ZIP against manifest.sha256.json.
- [ ] Confirm every source document and counterparty is fictional.
- [ ] Confirm accrual basis, GBP functional currency and standard VAT accounting.
- [ ] Record all excluded schemes and factual assumptions before reviewing totals.

### Phase B — accounting profile and chart of accounts

- [ ] Legal/business identity is clearly distinguished from demo values.
- [ ] Financial year, accounting basis and VAT settings are understandable.
- [ ] Ledger account codes, names, types and normal balances are suitable.
- [ ] VAT control accounts are distinct and traceable.
- [ ] Trade debtors, trade creditors, bank, fixed assets, income and expense classifications are coherent.
- [ ] Accounting periods move through open, closing, closed and locked states with an audit reason.
- [ ] Locked periods prevent backdated postings and direct mutation.

Record requested design changes as findings; do not redesign the chart inside the test tenant.

### Phase C — source transactions and posting lifecycle

Trace every representative source to its journal, ledger accounts, VAT Detail and report outcome.

- [ ] 20% standard-rated sale and purchase.
- [ ] 5% reduced-rated sale and purchase.
- [ ] Zero-rated sale and purchase.
- [ ] Exempt sale and purchase.
- [ ] Outside-scope sale and purchase, including the Boxes 6/7 decision.
- [ ] Cross-border service reverse-charge purchase and equal Box 1/Box 4 effect where recoverable.
- [ ] Capital equipment purchase in the Balance Sheet and Boxes 4/7, not operating expenses.
- [ ] EUR invoice with immutable GBP exchange-rate snapshot and adequate evidence.
- [ ] Customer credit note and supplier credit note in the correct VAT period.
- [ ] Customer receipt/refund and supplier payment/refund.
- [ ] Bank fee and interest.
- [ ] Full journal reversal with immutable links and net-zero effect.
- [ ] Documented £5 output-VAT adjustment without an artificial Box 6 base.
- [ ] No unbalanced posted journal and no silent edit/delete of posted entries.

Record the source reference and affected debit, credit and VAT boxes for every material sample.

### Phase D — bank and subledger controls

- [ ] Imported bank rows are deduplicated.
- [ ] Suggested matches are distinguishable from confirmed reconciliations.
- [ ] One bank transaction cannot be reconciled twice.
- [ ] Unmatching or reversal leaves a clear audit trail.
- [ ] Bank reconciliation balance and outstanding items are understandable.
- [ ] Receivables and payables reconcile to their control accounts.
- [ ] Manual ledger adjustments in ageing are handled or explicitly disclosed.

### Phase E — financial reports

- [ ] Trial Balance debits equal credits.
- [ ] General Ledger traces balances to journals and source references.
- [ ] Profit and Loss produces net profit £1,165.00 for the fixture.
- [ ] Balance Sheet balances at £12,195.00.
- [ ] Management Cash Flow reconciles to closing cash £10,475.00 and is labelled as a management report, not a statutory IAS 7 statement.
- [ ] Aged receivables/payables reconcile to £995.00 / £730.00.
- [ ] Date and period filters do not silently mix periods.
- [ ] Currency, signs, rounding and export labels are unambiguous.

### Phase F — VAT Detail and nine-box return

- [ ] VAT Detail reconciles to the VAT control accounts.
- [ ] Box 3 equals Box 1 plus Box 2.
- [ ] Box 5 correctly indicates payable or repayable.
- [ ] Boxes 1–5 retain penny precision.
- [ ] Boxes 6–9 use acceptable whole-pound submission values.
- [ ] Credits and reversals affect the correct period and boxes.
- [ ] The service reverse-charge treatment is correct for the stated facts.
- [ ] VAT adjustments include reason, actor, date and treatment metadata.
- [ ] Prior-return errors requiring separate notification remain outside the current-return workflow.
- [ ] A review snapshot is immutable and tied to a visible SHA-256.
- [ ] A changed calculation cannot be submitted under an earlier approval.
- [ ] Superseding a snapshot preserves the old audit record.
- [ ] The accountant ZIP comes only from the locked snapshot and verified source evidence.

Do not approve Boxes 8/9 merely because they are zero. State whether NI–EU goods scenarios are not applicable to launch or require a separate fixture.

### Phase G — HMRC sandbox tenant

The sandbox journey has already completed successfully. Base-scope review uses existing evidence and does not require another submission.

1. Confirm the selected workspace is **Website Expert NI — HMRC Sandbox**.
2. Open **Finance → HMRC Connection**.
3. Confirm the environment is clearly sandbox and expected OAuth scopes are presented.
4. Review the displayed fraud-prevention-header validation without requesting raw values.
5. With owner approval, run **Synchronise VAT obligations** using a fresh authenticator code.
6. Confirm a repeat synchronisation is idempotent and does not duplicate obligations.
7. Review the fulfilled obligation and use **View VAT Return** through the UI.
8. Confirm the nine returned boxes match the submitted nil return for this sandbox track.
9. Review liabilities and payments only within the scenario/date range supplied by the owner.
10. Download the existing receipt PDF. It must say that it is system-generated evidence of an HMRC API response, not an HMRC-issued document.
11. Confirm it contains submission evidence, snapshot/payload hashes and verified audit events without exposing secrets or the full VRN.

Do not use **Disconnect**, **Set sandbox VRN** or **Submit to HMRC** unless the owner separately authorises that exact action in writing.

### Phase H — submission and failure controls

Review the interface and supplied test evidence. Do not manufacture HMRC failures unless the owner schedules them.

- [ ] Submission requires a locked return and matching open obligation.
- [ ] The user must accept the final declaration and enter fresh MFA.
- [ ] The payload comes from the immutable snapshot, not mutable records.
- [ ] A successful response records a validated receipt and correlation evidence.
- [ ] A definitive 4xx is recorded as rejected with sanitised detail.
- [ ] Duplicate submission does not cause another POST.
- [ ] Timeout, malformed response or HMRC 5xx becomes outcome_unknown and blocks blind retry.
- [ ] Reconciliation uses GET only and compares all nine boxes.
- [ ] A mismatch becomes an explicit conflict requiring investigation.
- [ ] Events are append-only and their hash chain is verified before receipt generation.

### Phase I — permissions and tenant isolation

- [ ] Your account can access only intended sandbox business records.
- [ ] Direct links to another business do not reveal a record.
- [ ] You cannot manage users, roles, secrets or unrelated settings.
- [ ] Submission, approval, posting and closing use explicit permissions.
- [ ] Your account is not an owner of the operational tenant.

Do not attempt exploit techniques. If normal navigation or an ordinary copied application link exposes another tenant, classify it as Critical, capture only redacted evidence and stop.

## 6. Professional decisions that need an answer

The report must answer or explicitly defer:

1. For which represented classes should outside-scope supplies or purchases enter Boxes 6/7?
2. Are NI–EU goods movements relevant to launch? If yes, what Boxes 8/9 fixtures are required?
3. What evidence and rounding policy should govern foreign-currency VAT conversion?
4. Is the service reverse-charge example correct for its stated facts and recoverability assumption?
5. What non-recoverable or partially recoverable input-VAT cases are required before wider use?
6. Are VAT-adjustment approval and evidence fields sufficient?
7. Does any represented correction require separate HMRC notification?
8. Are classifications and labels in Trial Balance, P&L, Balance Sheet, ageing and management Cash Flow acceptable?
9. Can every material VAT amount be traced to evidence and posted journals?
10. Which excluded schemes or cases must block production use until implemented?

## 7. Finding format

Use one record per finding:

| Field | Required content |
|---|---|
| Finding ID | ACC-001, ACC-002, etc. |
| Severity | Critical / High / Medium / Low |
| Area | Ledger / VAT / Report / HMRC / Access / Evidence / UX |
| Release | Commit or release identifier |
| Test reference | Phase and checklist item |
| Source reference | Fictional invoice, journal, return or obligation |
| Expected | Correct result and basis |
| Actual | What the system showed or calculated |
| Financial effect | VAT box/account/report and amount, where known |
| Evidence | Redacted reference; never a secret |
| Recommendation | Required correction or decision |
| Re-test | Not tested / Passed / Failed / Accepted risk |

### Severity guide

- **Critical:** wrong VAT submission amount, unauthorised HMRC POST, cross-tenant disclosure, broken double entry, mutable locked return or invalid audit evidence.
- **High:** material VAT-box, control-account, report or period error likely to affect a filing/review decision.
- **Medium:** incomplete traceability, unclear treatment, missing approval evidence or non-material workflow defect.
- **Low:** terminology, layout or usability issue with no accounting-result impact.

## 8. Required outputs

- [ ] completed [accountant-review-checklist.md](uk-finance-golden-dataset-v1/accountant-review-checklist.md);
- [ ] findings log using section 7;
- [ ] completed [sign-off.md](uk-finance-golden-dataset-v1/sign-off.md);
- [ ] explicit assumptions and exclusions;
- [ ] scenarios required before production or wider customer use;
- [ ] accountant ZIP hash and release identifier reviewed; and
- [ ] if included in the accepted quotation, a re-test note marking each finding Passed, Failed or Accepted risk.

The sign-off must select exactly one outcome:

- **Accepted without changes**;
- **Accepted subject to stated conditions**; or
- **Rework required; not accepted**.

Do not imply statutory audit assurance, HMRC certification or correctness outside the reviewed fixture and scope.

## 9. End of engagement

1. Upload final deliverables through the agreed secure channel.
2. Remove local packs and screenshots unless retention is legally required.
3. Confirm deletion or state the mandatory retention basis and period.
4. Sign out of all sessions and tell the owner the account can be revoked.
5. Do not retain or reuse sandbox access.

## 10. Reference documents

- [Golden dataset README](uk-finance-golden-dataset-v1/README.md)
- [Accountant review checklist](uk-finance-golden-dataset-v1/accountant-review-checklist.md)
- [Accountant sign-off](uk-finance-golden-dataset-v1/sign-off.md)
- [HMRC sandbox implementation record](../integrations/hmrc-mtd-vat-sandbox.md)
- [HMRC production readiness gate](../integrations/hmrc-production-readiness.md)
- [B2B review agreement](../legal/uk-vat-mtd-accounting-review-b2b-agreement.md)
