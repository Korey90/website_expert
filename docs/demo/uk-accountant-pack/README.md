# UK accountant demonstration pack

This directory contains English-language examples built with company details from the system and fictional transactions for review by a UK accountant. Every PDF is marked **DEMO / NOT FOR SUBMISSION**.

## Company details used

- Legal name: `Website Expert NI`
- Company number: `123456789`
- VAT number: `2134578541`
- Registered address: `Suite 6, 129 University Street, Belfast, BT7 1HP`
- Contact: `sales@website-expert.uk` · `+447882799050`
- Source: the system's `Legal Settings`, with `AccountingProfile` as fallback.
- Profile consistency: **requires synchronisation**. Synchronise Legal Settings and AccountingProfile before production invoicing.

## System status

- `01`–`03`: generated from the current WebsiteExpert invoice and customer credit-note templates.
- `04`–`05`: examples of supplier evidence that the system stores privately; WebsiteExpert does not issue these supplier documents.
- `06`–`17`: target previews for accountant review. They do not claim that the corresponding production export is already available.

All documents use the same period (1 July–30 September 2026) and reconcile to the control totals in `demo-dataset.json`. `manifest.sha256.json` records integrity hashes. The ZIP is the convenient hand-off artefact.

## Important boundary

This pack is not tax advice, an official HMRC form or a filing-ready VAT Return. The scenario assumes standard VAT accounting on an accrual basis. Special schemes and sector-specific rules are outside scope until reviewed.

## Official reference basis checked on 5 November 2026

- VAT invoice content: https://www.gov.uk/guidance/vat-guide-notice-700#section16
- General invoice content: https://www.gov.uk/invoicing-and-taking-payment-from-customers/invoices-what-they-must-include
- VAT Return boxes: https://www.gov.uk/guidance/how-to-fill-in-and-submit-your-vat-return-vat-notice-70012
- HMRC VAT (MTD) API: https://developer.service.hmrc.gov.uk/api-documentation/docs/api/service/vat-api/1.0

Please use `accountant-review-checklist.md` to record decisions before VAT mapping and report exports are treated as production-ready.
