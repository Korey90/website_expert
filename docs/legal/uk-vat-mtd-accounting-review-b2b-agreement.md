# UK VAT/MTD accounting review - B2B services agreement

> **Template status:** complete all square-bracket fields before signature. The Provider must propose the price. This document deliberately contains no price indication or preferred payment model. It should be reviewed by a Northern Ireland solicitor before use if the negotiated terms or scope change materially.

[Polska wersja](uk-vat-mtd-accounting-review-b2b-agreement-pl.md)

## Particulars

| Item | Agreed detail |
|---|---|
| Effective date | [DATE] |
| Client | [FULL LEGAL NAME / REGISTERED COMPANY NAME], [an individual trading as Website Expert NI / a company incorporated in JURISDICTION with company number NUMBER], of [ADDRESS] (**Client**) |
| Client contact | [NAME, TITLE, EMAIL] |
| Service Provider | [FULL LEGAL NAME / FIRM NAME], [LEGAL FORM AND REGISTRATION NUMBER], of [ADDRESS] (**Provider**) |
| Lead Reviewer | [NAME, QUALIFICATION, PROFESSIONAL BODY AND MEMBERSHIP NUMBER] (**Reviewer**) |
| Provider contact | [EMAIL AND PHONE] |
| Review environment | **Website Expert NI - HMRC Sandbox** and the separately supplied UK Finance golden dataset v1 |
| Accepted quotation | [QUOTATION REFERENCE, VERSION AND DATE - INSERT ONLY AFTER ACCEPTANCE] |
| Agreed fee | [INSERT THE FEE FROM THE ACCEPTED QUOTATION] |
| VAT treatment | [AS STATED IN THE ACCEPTED QUOTATION] |
| Payment terms | [AS STATED IN THE ACCEPTED QUOTATION] |
| Target start and delivery | [AS AGREED AFTER ACCEPTANCE] |
| Re-test | [INCLUDED / SEPARATELY PRICED / NOT INCLUDED - AS QUOTED] |

The Client and Provider are together the **Parties** and each a **Party**.

## 1. Appointment and independent status

1.1 The Client appoints the Provider to perform the independent UK VAT, Making Tax Digital (**MTD**) and accounting-software review described in Schedule 1 (**Services**). The Provider accepts that appointment.

1.2 The Provider shall perform the Services with reasonable skill, care and diligence expected of a suitably qualified UK accounting professional experienced in UK VAT, MTD and accounting systems.

1.3 The Provider is an independent contractor. Nothing in this Agreement creates employment, worker status, agency, partnership, fiduciary relationship or authority to bind the Client. The Provider is responsible for its own tax, National Insurance, personnel and business obligations.

1.4 The named Reviewer shall personally lead the Services. There may be no replacement, delegation or subcontracting without the Client's prior written approval.

## 2. Quotation and formation

2.1 Before work begins, the Provider shall submit a written quotation that states:

- the fixed fee or rate basis proposed by the Provider;
- whether VAT is chargeable;
- payment milestones and due dates;
- estimated effort and delivery timetable;
- assumptions and dependencies;
- whether one re-test is included and any limit on it;
- permitted expenses; and
- the price or rate for work outside scope.

2.2 The quotation is not binding until the Client accepts it in writing. The accepted quotation reference must then be entered in the Particulars.

2.3 The Provider shall not start chargeable work, exceed an agreed estimate or incur an expense without written acceptance. Silence does not constitute acceptance.

2.4 If an accepted quotation conflicts with this Agreement, this Agreement prevails unless the quotation expressly identifies the clause being replaced and both Parties sign that change.

## 3. Scope and deliverables

3.1 The Provider shall review the fictional accounting scenarios, generated reports, VAT calculations, control totals, audit evidence and HMRC sandbox journey listed in Schedule 1.

3.2 The Provider shall deliver:

- a completed accountant review checklist;
- a written findings log showing severity, evidence, expected treatment and recommended correction;
- a completed sign-off stating **accepted**, **accepted subject to conditions**, or **rework required**;
- an explicit list of assumptions, excluded scenarios and unresolved professional decisions; and
- any re-test note included in the accepted quotation.

3.3 Findings shall be classified as:

- **Critical:** risk of an incorrect VAT submission, unauthorised HMRC action, cross-tenant disclosure, materially incorrect ledger or loss of audit integrity;
- **High:** material accounting, VAT-box, reconciliation or reporting error without an immediate unauthorised submission;
- **Medium:** incomplete evidence, ambiguous workflow or non-material treatment requiring correction before release; or
- **Low:** terminology, presentation or usability improvement that does not change accounting results.

3.4 The Provider shall promptly notify the Client of a Critical finding and stop the affected test flow until the Client confirms it is safe to continue.

## 4. Express exclusions

4.1 The Services are an independent product and accounting-logic review. They are not:

- a statutory audit, assurance engagement or independent examination;
- preparation or filing of a real VAT Return;
- tax, legal or investment advice for a real taxpayer or transaction;
- a penetration test or certification of information security;
- an HMRC approval, endorsement or production-access decision;
- a guarantee that the software covers every UK tax rule, business model or VAT scheme; or
- a review of Corporation Tax, PAYE, payroll, CIS, Self Assessment, capital allowances or statutory accounts.

4.2 Unless added by an accepted Change Request, the review excludes Cash Accounting, Flat Rate Scheme, Annual Accounting, margin schemes, partial exemption, non-recoverable input VAT, domestic reverse charge, construction-industry cases, import VAT, postponed import VAT accounting and Northern Ireland-EU goods movements for Boxes 8 and 9.

4.3 The Provider must identify an excluded scenario when it affects a conclusion. Silence does not expand the agreed scope.

## 5. Client responsibilities

5.1 The Client shall provide:

- a unique, time-limited account restricted to the isolated sandbox tenant;
- the materials and access listed in Schedule 1;
- a contact able to answer product questions;
- fictional test data or appropriately minimised and authorised data; and
- timely decisions on findings and Change Requests.

5.2 The Client retains responsibility for the software, implementation choices, production release, HMRC communications and every real VAT submission.

5.3 The Provider is not responsible for delay caused by missing access, unavailable HMRC sandbox services, inaccurate Client instructions or materials changed after their integrity hash was recorded.

## 6. Access and safety boundary

6.1 The Provider shall use only the named sandbox tenant and supplied review artefacts. It shall not access the operational **Website Expert NI** tenant, real customer records or an HMRC production environment.

6.2 The Provider shall not:

- share credentials, MFA codes or sessions;
- disconnect HMRC, replace the sandbox VRN, change fraud-prevention configuration or alter environment settings;
- initiate a VAT submission unless the Client gives written approval for the specific sandbox obligation and test window;
- repeat a failed or ambiguous submission POST;
- probe, scan or attempt to bypass authorisation controls; or
- copy source code, secrets, tokens, full VRNs or personal data into the report.

6.3 If an HMRC submission outcome is uncertain, the Provider shall stop and notify the Client. Only the application's GET-based reconciliation workflow may be used, under the Client's written instruction.

6.4 Schedule 3 contains the full security and acceptable-use requirements.

## 7. Timing and changes

7.1 The timetable begins only after this Agreement and the quotation have been accepted and all required access and materials are available.

7.2 Either Party may propose a written Change Request. It must state the additional work, fee, VAT treatment and timing and takes effect only when accepted in writing.

7.3 The Provider shall not charge for work outside scope without an accepted Change Request.

## 8. Invoices, VAT and expenses

8.1 The Client shall pay valid, undisputed invoices in accordance with the accepted quotation.

8.2 VAT is payable only if properly chargeable and shown on a valid VAT invoice.

8.3 Travel, third-party tools and other expenses require prior written approval and receipts unless they are expressly included in the quotation.

8.4 The Provider may claim statutory interest and recovery charges on overdue commercial debts where legally available. It shall not suspend Services over a genuinely disputed amount while the Parties are actively resolving it.

## 9. Review and acceptance

9.1 The Client has five Working Days after delivery to accept the deliverables or identify material omissions against Schedule 1.

9.2 The Provider shall correct agreed omissions within a reasonable period at no extra charge. A difference of professional opinion, a newly requested scenario or software remediation is not an omission and requires a Change Request.

9.3 Acceptance does not waive a latent breach that could not reasonably have been identified during the acceptance period.

9.4 The Provider does not modify application code under this Agreement. The Client's software team remains responsible for implementing findings.

## 10. Professional assurances

10.1 The Provider warrants that:

- the Reviewer's stated qualifications and memberships are accurate;
- it has the competence and capacity to perform the Services;
- it will comply with applicable professional, ethical and legal obligations;
- it has disclosed every actual or reasonably perceived conflict of interest;
- its conclusions will be independent and not altered to achieve a preferred release decision; and
- it maintains professional indemnity insurance appropriate to the Services and will provide reasonable evidence on request.

10.2 The Provider shall promptly disclose a new conflict, regulatory restriction or loss of relevant professional status.

## 11. Confidentiality and publicity

11.1 **Confidential Information** means non-public technical, security, commercial, financial and product information disclosed for the Services, including credentials, architecture, reports, findings and test evidence.

11.2 The receiving Party shall use Confidential Information only for this Agreement, protect it with at least reasonable care and disclose it only to personnel who need it and are bound by equivalent duties.

11.3 This duty does not apply to information that the receiving Party can show was lawfully known without restriction, becomes public without breach, is independently developed, or is lawfully received from a third party.

11.4 If disclosure is required by law or a professional regulator, the receiving Party shall, where lawful, notify the other Party first and disclose only what is required.

11.5 Confidentiality continues for five years after termination and indefinitely for trade secrets, credentials and security-sensitive information.

11.6 The Provider shall not name the Client, use the **Website Expert NI** brand, publish a case study or disclose the outcome without prior written consent.

## 12. Data protection

12.1 Each Party shall comply with applicable UK data-protection law, including the UK GDPR and Data Protection Act 2018 as amended.

12.2 For personal data processed solely to perform the sandbox review on the Client's documented instructions, the Client is controller and the Provider is processor. Schedule 2 applies.

12.3 Each Party acts as an independent controller for personal data it must process for its own invoicing, legal, insurance, anti-money-laundering or professional-regulatory obligations.

12.4 No special-category data, criminal-offence data or real customer financial data is intended to be supplied. The Provider shall stop and notify the Client if such data is encountered unexpectedly.

## 13. Intellectual property

13.1 Each Party retains ownership of materials, methods, templates and intellectual property it owned or developed independently before the Services (**Background Materials**).

13.2 Subject to full payment, the Provider assigns to the Client, with full title guarantee, all present and future copyright and other transferable intellectual-property rights in the bespoke findings report, completed checklist and sign-off created specifically under this Agreement (**Deliverables**) for the full term of those rights worldwide.

13.3 The Provider shall execute reasonable documents required to confirm the assignment and waives, to the extent legally permitted, moral rights in the Deliverables.

13.4 If Provider Background Materials are embedded in a Deliverable, the Provider grants the Client a perpetual, worldwide, irrevocable, royalty-free, non-exclusive licence to use, copy, modify and share them as part of or in connection with the Deliverable and software.

13.5 The Client grants the Provider a limited, non-transferable, revocable licence to use supplied materials only for the Services.

## 14. Professional judgement and reliance

14.1 Conclusions apply only to the stated release, hashed datasets, assumptions and scenarios reviewed. A software, law, HMRC guidance or factual change may require a new review.

14.2 The Provider shall distinguish confirmed errors, professional judgements, recommendations and matters requiring specialist tax advice.

14.3 The Client may rely on the Deliverables for internal product-release decisions within the agreed scope. No third party may rely on them without the Provider's written agreement.

14.4 The Provider does not guarantee uninterrupted HMRC sandbox availability or that HMRC will grant production access.

## 15. Liability

15.1 Nothing limits liability for fraud or fraudulent misrepresentation, wilful misconduct, death or personal injury caused by negligence, or any liability that cannot lawfully be limited.

15.2 Subject to clause 15.1, neither Party is liable for indirect or consequential loss, or loss of profit, revenue, goodwill or anticipated savings. Direct remediation costs reasonably incurred because of a proven breach are not excluded merely by their description.

15.3 Subject to clauses 15.1 and 15.4, each Party's aggregate liability shall not exceed the cap stated in the accepted quotation or, if no cap is stated, the total fee paid or payable under this Agreement.

15.4 A negotiated cap does not apply to deliberate breach of confidentiality, infringement of the other Party's intellectual-property rights, or the Provider's processing of personal data outside the Client's documented instructions, unless the Parties expressly agree a lawful separate cap in writing.

15.5 Each Party shall mitigate its loss. The limitations are intended to be reasonable having regard to the sandbox scope, accepted price, insurance and the Client's retained control of production release and filing.

## 16. Termination

16.1 Either Party may terminate immediately if the other commits a material breach and, where capable of remedy, fails to remedy it within five Working Days after written notice.

16.2 The Client may terminate for convenience on five Working Days' written notice. The Client shall pay only for Services properly performed up to termination in accordance with the accepted quotation, and the Provider shall deliver usable work in progress.

16.3 The Provider may immediately suspend access where continued testing reasonably appears to create a security, confidentiality, data-protection or HMRC-submission risk, but must notify the Client promptly.

16.4 On termination, access ends immediately. Provisions intended by their nature to continue survive, including confidentiality, data protection, intellectual property, liability, payment and governing law.

## 17. Return, deletion and account closure

17.1 Within seven days after acceptance or termination, the Provider shall return or securely delete Client data and local evidence at the Client's choice, except for one copy required by law, insurance or professional rules.

17.2 A retained copy remains confidential, may be used only for the mandatory retention purpose and must be deleted when that purpose ends.

17.3 The Provider shall confirm deletion in writing. The Client shall revoke the Provider's account and sessions when the engagement and any agreed re-test end.

## 18. Notices

18.1 Contract notices must be sent by email to the contacts in the Particulars and are received on the next Working Day unless a delivery failure is received.

18.2 Operational findings may be recorded in the agreed issue log, but termination, liability claims and changes to fee or scope must also be sent by email.

## 19. General

19.1 This Agreement, its Schedules and the accepted quotation form the entire agreement about the Services. Neither Party relies on a statement not recorded in them, without limiting liability for fraud.

19.2 Neither Party may assign this Agreement without the other's prior written consent, except to a successor to substantially all relevant business assets.

19.3 A person who is not a Party has no right to enforce this Agreement.

19.4 A delay is not a waiver. If a provision is unenforceable, it shall be adjusted only as much as necessary and the remainder continues.

19.5 Neither Party is liable for delay caused by events beyond its reasonable control, but payment for completed Services and security/confidentiality duties remain unaffected.

19.6 This Agreement may be signed in counterparts and electronically.

19.7 **Working Day** means Monday to Friday excluding bank and public holidays in Northern Ireland.

19.8 If the Parties sign both the English and Polish versions, the Polish version is provided as a convenience translation and the English version prevails in the event of inconsistency.

## 20. Governing law and jurisdiction

20.1 This Agreement and related non-contractual obligations are governed by the law of Northern Ireland.

20.2 The courts of Northern Ireland have exclusive jurisdiction, after the Parties first try in good faith for ten Working Days to resolve the dispute between senior representatives.

## Signatures

### Client

Name: __________________________________________

Title / capacity: __________________________________________

Legal name: __________________________________________

Signature: __________________________________________

Date: __________________________________________

### Provider

Name: __________________________________________

Title / capacity: __________________________________________

Legal name: __________________________________________

Signature: __________________________________________

Date: __________________________________________

### Lead Reviewer acknowledgement

I confirm that my stated qualifications are accurate, I have read the scope and safety boundary, and I will record independent professional conclusions.

Reviewer name: __________________________________________

Qualification / membership: __________________________________________

Signature: __________________________________________

Date: __________________________________________

---

# Schedule 1 - Statement of Work

## A. Objective

Independently assess whether the reviewed release gives a UK bookkeeper/accountant a coherent and adequately evidenced workflow for standard accrual-basis UK VAT and whether the implemented HMRC MTD VAT sandbox journey corresponds with the locked nine-box VAT Return.

## B. Review materials

The Client shall provide:

1. the bookkeeper guide at docs/review/uk-vat-mtd-bookkeeper-review-guide.md;
2. golden dataset ID **website-expert-ni-uk-finance-golden-v1**;
3. expected dataset SHA-256 **6271a6a9e179c1a8769e7fd957f401fde892b0421ea2a73bdc7a0918fd2672a1**;
4. the generated accountant ZIP and its SHA-256 manifest;
5. the checklist and sign-off form in docs/review/uk-finance-golden-dataset-v1/;
6. an account restricted to **Website Expert NI - HMRC Sandbox**;
7. a release/commit identifier and relevant redacted sandbox evidence; and
8. an agreed secure location for findings.

## C. Accounting review

The review covers:

- chart of accounts and double-entry integrity;
- sales invoices, purchase expenses/invoices and customer/supplier credits;
- receipts, payments, refunds and bank reconciliation;
- posted journals, reversals, accounting periods and locks;
- standard, reduced, zero-rated, exempt and outside-scope VAT examples;
- a cross-border service reverse-charge example;
- fixed-asset purchase classification;
- foreign-currency source evidence with an immutable GBP-rate snapshot;
- VAT adjustments and correction boundaries;
- Trial Balance, General Ledger, Profit and Loss, Balance Sheet, management Cash Flow and aged receivables/payables; and
- VAT Detail, Boxes 1-9, immutable review snapshot, accountant pack and audit evidence.

## D. HMRC sandbox review

The review covers existing sandbox evidence for:

- OAuth connection and read:vat / write:vat scope presentation;
- fraud-prevention-header validation outcome without revealing header values;
- obligation synchronisation;
- the recorded locked nil VAT Return submission;
- View VAT Return retrieval;
- liabilities and payments retrieval;
- receipt PDF and append-only audit-chain evidence; and
- safe handling of rejected, duplicate, timeout or unknown outcomes.

A new sandbox POST is optional and may occur only with the Client's case-specific written approval and an available open test obligation. Review of the existing successful submission and evidence satisfies the base scope.

## E. Expected golden-dataset controls

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

Matching totals alone is insufficient. The Provider must assess the treatments, classifications, traceability and exclusions.

## F. Completion criteria

The initial Services are complete when all available listed materials have been reviewed and the Provider has delivered the checklist, findings log, sign-off decision, assumptions and exclusions. A conditional or rework-required decision is a valid deliverable and does not itself constitute breach.

---

# Schedule 2 - Data processing terms

## 1. Processing details

| Requirement | Detail |
|---|---|
| Subject matter | Controlled access to a software sandbox for the accounting and HMRC MTD review |
| Duration | From account activation until deletion under clause 17 |
| Nature and purpose | Accessing, viewing, testing, annotating and reporting on sandbox workflows and audit evidence |
| Personal-data types | Reviewer and Client staff names, business emails, user IDs, IP/security event data, timestamps and audit actors; no special-category data intended |
| Data subjects | Client and Provider personnel and test users; counterparties in the dataset are fictional |
| Location | United Kingdom; no international transfer without prior written approval and a lawful transfer mechanism |

## 2. Processor obligations

The Provider shall:

1. process personal data only on the Client's documented instructions unless UK law requires otherwise;
2. inform the Client if an instruction appears to infringe data-protection law;
3. bind authorised persons to confidentiality;
4. implement appropriate technical and organisational security measures;
5. obtain specific written authorisation before appointing a sub-processor and impose equivalent terms;
6. assist with data-subject rights, security duties, breach notification, impact assessments and regulator consultation where relevant;
7. notify the Client without undue delay and within 12 hours after becoming aware of a personal-data breach;
8. delete or return data at the end of Services unless law requires retention;
9. provide information reasonably needed to demonstrate compliance and permit a proportionate audit on reasonable notice; and
10. remain within the stated purpose, duration and data categories.

The Client shall provide lawful instructions, minimise data, maintain a lawful basis and avoid unnecessary real personal or financial data in the sandbox.

---

# Schedule 3 - Security and acceptable use

The Provider and Reviewer shall:

- use a supported, patched device with screen lock, disk encryption and malware protection;
- use a unique account, unique password and authenticator-app MFA;
- keep credentials in a password manager and never send passwords or MFA codes by email/chat;
- select and remain in **Website Expert NI - HMRC Sandbox**;
- store authorised exports only in the agreed encrypted location;
- redact full VRNs, credentials, tokens, MFA values, header values and unnecessary personal data;
- record page, test reference, time, expected result and actual result for every finding;
- report suspected unauthorised access, wrong-tenant access or ambiguous HMRC outcome immediately to [SECURITY CONTACT];
- stop after a Critical security or submission issue until instructed to continue;
- refrain from scanning, load testing, source-code download or privilege escalation; and
- securely delete local material and sign out when the engagement ends.

---

# Completion notes - remove before signature

1. Confirm whether the Client is a sole trader, partnership or company and insert the exact legal contracting name. **Website Expert NI** alone may be only a trading/system name.
2. Ask the Provider to propose the price. Do not put a benchmark or target price into the request.
3. Verify the Provider's identity, qualification, professional-body status and professional indemnity insurance.
4. Enter only the accepted quotation, fee, VAT, payment, dates and re-test terms.
5. If the Provider uses its own engagement letter, reconcile liability, IP, data processing, scope and governing-law clauses.
6. Do not insert the fictional company or VAT numbers from the demonstration pack.
7. Obtain Northern Ireland legal review if the scope expands to real taxpayer data or production filing.

Drafting references checked on 13 November 2026:

- ICO controller-processor terms: https://ico.org.uk/for-organisations/uk-gdpr-guidance-and-resources/accountability-and-governance/contracts-and-liabilities-between-controllers-and-processors-multi/what-needs-to-be-included-in-the-contract/
- Copyright, Designs and Patents Act 1988, section 90: https://www.legislation.gov.uk/ukpga/1988/48/section/90
- Unfair Contract Terms Act 1977, section 2: https://www.legislation.gov.uk/ukpga/1977/50/section/2
- GOV.UK late commercial payments: https://www.gov.uk/late-commercial-payments-interest-debt-recovery/charging-interest-commercial-debt
