# Website Expert NI — HMRC VAT Evidence Manifest

**Report title:** Website Expert NI – Business Activity and Preparatory Work Evidence  
**Applicant:** KONRAD SEBASTIAN SZCZEPANIK  
**HMRC letter reference:** 099001404839  
**Manifest prepared:** 16 November 2026  
**Evidence capture timezone:** Europe/Warsaw  
**Requested VAT registration date:** **[MANUAL COMPLETION REQUIRED — original VAT application was not available in the authorised materials]**

## Handling and scope

- The applications, repositories and business records were treated as read-only.
- No account, client, lead, project, quotation, contract, invoice, payment, subscription or audit record was created or changed.
- No credentials, authentication secrets, recovery codes, session tokens, private configuration values or `.env` contents were accessed or included.
- Screenshots show the applications' condition on 16 November 2026. They are not evidence that the same interfaces existed on an earlier date.
- Protected administration modules were verified from current routes and source files, but were not runtime-verified because no approved credentials were supplied through the available environment or credential manager.
- The public homepage contains promotional counters and rating statements. They were not independently verified and are not relied upon as evidence of current customers, revenue or completed sales.

## Screenshot evidence index

| ID | Application / source | File | Capture date | Historical evidence date | Feature or activity demonstrated | Supports requested VAT date? | Limitations |
|---|---|---|---|---|---|---|---|
| E01 | Website Expert NI public website — `https://website-expert.uk/` | `HMRC_Evidence_Screenshots/E01_Website_Expert_Home_2026-11-16.png` | 16 Nov 2026 | None | Live public business presence and web-design proposition | Cannot assess until requested date is supplied | Present-condition evidence only. Promotional counters/ratings are not relied upon. |
| E02 | Website Expert NI services — `https://website-expert.uk/services` | `HMRC_Evidence_Screenshots/E02_Website_Expert_Services_2026-11-16.png` | 16 Nov 2026 | None | Advertised brochure websites, e-commerce, web applications, SEO, advertising, content, audits and maintenance | Cannot assess until requested date is supplied | Present-condition evidence only; prices are public offers, not realised sales. |
| E03 | SEO Checker public interface — `https://seochecker.website-expert.uk/` | `HMRC_Evidence_Screenshots/E03_SEO_Checker_Public_2026-11-16.png` | 16 Nov 2026 | None | Live URL-entry interface, server HTML / rendered DOM modes and whole-site audit entry point | Cannot assess until requested date is supplied | No audit was submitted, so no database record or external fetch was created. |
| E04 | SEO Checker local login — `http://127.0.0.1:5000/login` | `HMRC_Evidence_Screenshots/E04_SEO_Checker_Login_2026-11-16.png` | 16 Nov 2026 | None | Implemented account login and password-recovery entry point | Cannot assess until requested date is supplied | Protected dashboard not accessed; credentials were not supplied. |
| E05 | Website Expert NI local administration — `http://127.0.0.1:8001/admin` | `HMRC_Evidence_Screenshots/E05_Internal_Admin_Login_2026-11-16.png` | 16 Nov 2026 | None | Branded, authentication-protected internal administration entry point | Cannot assess until requested date is supplied | Protected modules are source-verified only; no bypass was attempted. |
| E06 | SEO Checker plans — `https://seochecker.website-expert.uk/plans` | `HMRC_Evidence_Screenshots/E06_SEO_Checker_Plans_2026-11-16.png` | 16 Nov 2026 | None | Implemented Free/Premium plan comparison, 14-day trial message and £28 monthly Premium display | Cannot assess until requested date is supplied | The captured viewport displays £28/month but does not state VAT treatment. The applicant states the planned £28 price includes VAT. No subscriber or payment is evidenced. |
| E07 | SEO Checker local registration — `http://127.0.0.1:5000/register` | `HMRC_Evidence_Screenshots/E07_SEO_Checker_Register_2026-11-16.png` | 16 Nov 2026 | None | Implemented account registration, legal acceptance and Premium trial proposition | Cannot assess until requested date is supplied | No account was created. |
| E08 | Website Expert NI client login — `https://website-expert.uk/login` | `HMRC_Evidence_Screenshots/E08_Website_Expert_Client_Login_2026-11-16.png` | 16 Nov 2026 | None | Live client account / portal authentication interface, including password recovery and social sign-in options | Cannot assess until requested date is supplied | No client account was accessed; interface language was Polish in the capture. |

## Historical and source evidence index

| ID | Source | Evidence date | Activity demonstrated | Supports requested VAT date? | Limitations |
|---|---|---|---|---|---|
| H01 | Git commit `adfafae` in `D:\Works\web-dev-app` | 19 Mar 2026 | Earliest repository commit available for the Website Expert NI application | Conditional — only if requested date is on/after 19 Mar 2026 | Git metadata is not independently notarised. Commit message: “firs commint”. |
| H02 | Git commit `f05e1eb` | 20 Mar 2026 | Calculator/contact-form flow work | Conditional — only if requested date is on/after 20 Mar 2026 | Repository evidence; no historical screenshot. |
| H03 | Git commit `05251e0` | 24 Mar 2026 | Contract module and templates added | Conditional — only if requested date is on/after 24 Mar 2026 | Repository evidence; current implementation may differ from that commit. |
| H04 | Git commit `43bdc88` | 31 Mar 2026 | Recorded start of SaaS-oriented development work | Conditional — only if requested date is on/after 31 Mar 2026 | Commit message is in Polish; does not prove commercial launch. |
| H05 | Git commit `ad78206` | 12 Jun 2026 | Service-page module, administration and public rendering | Conditional — only if requested date is on/after 12 Jun 2026 | Repository evidence; no claim of sales. |
| H06 | Git commit `713b396` | 19 Jul 2026 | English/Polish landing pages with CRM tracking and follow-up jobs | Conditional — only if requested date is on/after 19 Jul 2026 | Repository evidence; automated follow-up capability does not evidence customers. |
| H07 | Git commit `50bfa3b` | 4 Nov 2026 | UK finance work including VAT/net/gross handling and document numbering | Conditional — only if requested date is on/after 4 Nov 2026 | Commit records development and test claims, not live transactions. |
| H08 | Git commit `5ced1fd` | 10 Nov 2026 | VAT Return calculation preview work | Conditional — only if requested date is on/after 10 Nov 2026 | Commit expressly described a safe preview; no return submission evidenced. |
| H09 | Git commit `ef15028` and deployment record `cd4a6c8` | 13 Nov 2026 | Administration RBAC / tenant-isolation hardening and recorded deployment | Conditional — only if requested date is on/after 13 Nov 2026 | Deployment record is repository metadata; no client activity evidenced. |
| S01 | Current Laravel route inventory and `app/Filament` source | Inspected 16 Nov 2026 | Dashboard, CRM, leads, pipeline, clients, projects, services/CMS, quotations, contracts, invoicing, finance, settings, permissions and SaaS billing structures | No — current source inspection only | Protected runtime behaviour not independently authenticated during this review. |
| S02 | Current SEO Checker source and project documentation in `D:\Works\meta_tag_checker` | Inspected 16 Nov 2026 | Meta-tag checking, HTML/DOM comparison, whole-site auditing, projects/history, reports, monitoring, account plans and Stripe-oriented billing code | No — current source inspection only | This directory had no usable Git commit history; filesystem timestamps were excluded as historical proof. Some advanced functions require external configuration or Premium access and were not exercised. |

## File integrity

**Report PDF SHA-256:** `3715ec665fcf4d283ffcac4ea4dc8c98e352de23be81ea0ed940ddebe43664b3`

All screenshots are 1440 × 1100 pixels.

| File | Size (bytes) | SHA-256 |
|---|---:|---|
| `E01_Website_Expert_Home_2026-11-16.png` | 240,993 | `9feacdc0980250dfbfd576fcf156f0348021fcb27465e0e69ccf4e1393ea41c5` |
| `E02_Website_Expert_Services_2026-11-16.png` | 89,769 | `739cfafa02685ab0e0edb061bc4957d71b0815828526fe72521201d6a11215f9` |
| `E03_SEO_Checker_Public_2026-11-16.png` | 263,737 | `975aca8d4065fea9d5ff1dd487b26ad66ca2fdc0d6bc101b624d410915eba13b` |
| `E04_SEO_Checker_Login_2026-11-16.png` | 194,505 | `5430f1010f77c0d071dc90ea72d556635edfc9a07a8aac2f2174162332794173` |
| `E05_Internal_Admin_Login_2026-11-16.png` | 21,600 | `f6362879a44e53768fd89be93d28fd15e1b268577b10550cd959a7f98c55656c` |
| `E06_SEO_Checker_Plans_2026-11-16.png` | 243,115 | `e007bfcbf4de2d8027a13263747e20c3c99e818477e142c2ac1a0933e7340a1b` |
| `E07_SEO_Checker_Register_2026-11-16.png` | 198,096 | `dd451fa337903820b12d0085c40e83cefbbfc9b32c60c7d9e2255fad07a35577` |
| `E08_Website_Expert_Client_Login_2026-11-16.png` | 67,267 | `23bd1282a4f62fcd7cbdf23c054fcb11b9a0c9ff766646fb430548b2d693aa52` |

## Remaining evidence gaps

1. The requested VAT registration date is unavailable because the original VAT application was not present in the authorised workspace or supplied attachment.
2. Approved credentials and any necessary two-factor authentication were not supplied through the available secure mechanisms, so protected internal dashboards could not be captured.
3. The additional, separate SaaS product could not be identified with sufficient certainty in the authorised Website Expert NI materials. No unrelated sibling projects were treated as business evidence.
4. The SEO Checker project directory did not expose usable Git history, so its current screenshots cannot establish an earlier development date.
5. No paying customers, completed commercial sales, paid subscribers or realised turnover were evidenced or assumed.
6. No safe audit demonstration was run because submitting the form could create a database record and trigger external website retrieval.

