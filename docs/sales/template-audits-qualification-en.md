# Security & Performance Audits — Qualification Brief
> Service: audits
> Market: UK
> Brief Type: Qualification
> Status: Draft
> Last Updated: 2026-04-17
> Source Anchors: ServiceItemSeeder.php, skrypt-sprzedazowy.md

---

## Goal
Determine whether the lead has a live website that warrants an audit, can provide necessary access, and understands the audit deliverable (report + debrief, not included fix implementation).

---

## Client Context
| Field | Answer |
|---|---|
| Company name | `[client_name]` |
| Website URL | `[website_url]` |
| Lead source | `[lead_source]` |
| Platform / CMS | `[WordPress / Shopify / custom]` |
| Contact name & role | `[contact_name]`, `[role]` |
| Decision-maker | Yes / No / Shared |
| Budget for audit | £299 confirmed / Querying / Too high |
| Post-audit intent | Fix it themselves / Want us to fix / Unsure |

---

## Offer Fit
| Criterion | Qualified | Not Qualified |
|---|---|---|
| Live website | Active, publicly accessible website | No website or site in active rebuild |
| Budget | £299 one-off accepted | Refuses to pay; expects free audit |
| Access | Can provide CMS / hosting access | "I have no access to my own site" |
| Expectation | Understands audit = report + debrief | Expects audit includes fixing everything |
| Timeline | Standard 5–7 business days acceptable | "I need it fixed by end of today" |
| Business relevance | Website is business-critical or stores data | "It's just a placeholder, no one visits it" |

---

## Qualification Criteria

### Must-Have
- [ ] Live website exists and is publicly accessible
- [ ] £299 one-off budget accepted
- [ ] Client can provide or facilitate access to CMS backend and/or hosting control panel
- [ ] Client understands deliverable: PDF report + 1-hour debrief call (fixes are separate)
- [ ] Standard 5–7 business day delivery timeline is acceptable

### Nice-to-Have
- [ ] Known specific concerns (slow site, security warnings, recent hack, compliance requirement)
- [ ] Interest in follow-on `maintenance` retainer post-audit
- [ ] Interest in `seo` retainer if performance issues are suspected to affect rankings
- [ ] Site handles customer data or payments (increases audit value and urgency)
- [ ] Client has a developer or agency who can action the findings

### Red Flags
- 🚨 "Can you do the audit for free and quote us for the fixes later?" — explain no; audit is a paid, standalone service
- 🚨 "I don't have access to the hosting or CMS — my old agency manages it" — access must be resolved before audit can start
- 🚨 "We're rebuilding the site in 3 months anyway" — audit findings may not be actionable; consider deferring
- 🚨 "I just want a quote for fixing everything without an audit" — redirect to scoping conversation
- 🚨 "We were hacked and need it fixed urgently today" — immediate remediation is a different (higher urgency) service; refer to emergency support process

---

## Scope and Boundaries
**Included in £299:**
OWASP Top 10 scan, Core Web Vitals (LCP/CLS/INP), server config + SSL review, dependency/CVE audit, prioritised fix list, PDF report, 1-hour debrief call.

**Not included — triggers separate quote:**
- Fix implementation (developer time charged separately)
- Ongoing monitoring and updates (→ `maintenance` retainer)
- Penetration / ethical hacking test
- SEO keyword or backlink audit (→ `seo`)

---

## Pricing Anchors
| Item | Price |
|---|---|
| Security & Performance Audit | **£299 one-off** |
| Delivery | 5–7 business days |
| Deliverables | PDF report + 1-hour debrief |
| Post-audit fix implementation | Custom quote (TBD after findings) |
| Post-audit maintenance | From £149/mo (`maintenance`) |

---

## Risks and Dependencies
- Audit cannot begin until CMS and/or hosting access is provided
- If site is in active development or staging only, audit findings may differ from production
- Delivery timeline of 5–7 business days starts from receipt of access, not from signing

---

## Assumptions
- Client owns the domain and controls or can obtain access to CMS and hosting
- The website is the production/live version, not a staging environment
- Client is not expecting the audit to include fix implementation

---

## Open Questions
- [ ] Does the client currently have access to their own CMS and hosting panel?
- [ ] Are there specific concerns to prioritise — security, performance, or compliance?
- [ ] Is the client planning to action findings themselves or through Website Expert?
- [ ] Is there a follow-on maintenance or development budget post-audit?
- [ ] Does the site handle payments, personal data, or operate in a regulated sector?

---

## Recommended Next Step
- **Qualified** → Issue audit agreement (£299); schedule debrief call slot; begin access handover
- **Conditionally qualified** → Resolve access issue or clarify scope expectation; revisit within 5 business days
- **Not qualified** → If emergency remediation needed, direct to support escalation; if site rebuild needed, direct to `brochure-websites`
