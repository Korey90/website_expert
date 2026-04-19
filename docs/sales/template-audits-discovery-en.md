# Security & Performance Audits — Discovery Brief
> Service: audits
> Market: UK
> Brief Type: Discovery
> Status: Draft
> Last Updated: 2026-04-17
> Source Anchors: ServiceItemSeeder.php, skrypt-sprzedazowy.md

---

## Goal
Understand the client's website technology stack, security concerns, performance issues, and business context to scope and position an audit as a clear, low-risk entry point that identifies specific, actionable issues.

---

## Client Context
| Field | Answer |
|---|---|
| Company name | `[client_name]` |
| Website URL | `[website_url]` |
| Industry | `[industry]` |
| CMS / Platform | `[WordPress / Shopify / custom / other]` |
| Hosting provider | `[hosting_provider]` |
| Last security review | `[never / month-year / unknown]` |
| Known issues | `[slow load times / hacked previously / vulnerabilities flagged / none]` |
| Data processed | `[customer data / payments / none]` |
| Decision deadline | `[decision_deadline]` |
| Budget indication | `[one-off_budget]` |

---

## Offer Fit
A Website Expert Security & Performance Audit fits businesses that:
- Have a website that hasn't been professionally reviewed for security or performance
- Are concerned about vulnerabilities, outdated plugins, or slow load times affecting conversions
- Need an objective, prioritised fix list before committing to a larger development engagement
- Want to understand their risk exposure before renewing or cancelling a hosting or maintenance contract

**Poor fit when:** Website is being rebuilt from scratch imminently (audit findings may be irrelevant). Client has no website at all. Client wants ongoing monitoring rather than a point-in-time report (→ `maintenance` instead).

---

## Discovery Flow

### Business Context
> "Is your website business-critical — do customers book, buy, or contact you through it?"
> "Have you experienced any security incidents — hacking, malware, data breaches, or unexpected redirects?"
> "Are you storing or processing any customer personal data or card payments?"

### Current Tech Stack
> "What CMS or platform is your site built on? (WordPress, Shopify, custom?)"
> "Who hosts your website, and is SSL/HTTPS currently active?"
> "When was the last time plugins, themes, or the CMS core were updated?"

### Performance Concerns
> "Have customers or staff mentioned the website is slow?"
> "Do you know your current Google PageSpeed or Core Web Vitals scores?"
> "Have you lost any Google rankings recently that might indicate a Core Web Vitals issue?"

### Security Concerns
> "Has your site been flagged by Google Safe Browsing or antivirus tools?"
> "Are there unused user accounts, old plugins, or external scripts running on the site?"
> "Do you have any compliance requirements (GDPR, PCI DSS, ISO 27001) that the site must meet?"

### Outcomes
> "What would a successful audit look like to you — a list of fixes, a risk score, or both?"
> "Who internally would action the findings — your developer, your hosting provider, or us?"
> "Is the goal to fix issues yourselves, or would you want us to implement the fixes?"

---

## Scope and Boundaries
**In scope (£299 one-off):**
- OWASP Top 10 vulnerability scan
- Core Web Vitals assessment: LCP, CLS, INP
- Server configuration and SSL/HTTPS review
- Dependency and CVE (Common Vulnerability Exposure) audit
- Prioritised fix list with effort estimates
- PDF report + 1-hour debrief call (within 5–7 business days)

**Out of scope:**
- Penetration testing / ethical hacking (separate quote, specialist service)
- Fixing identified issues (→ can be scoped as follow-on work)
- Ongoing monitoring after the audit (→ `maintenance` retainer)
- SEO keyword or backlink audit (→ `seo` retainer)
- Full website rebuild (→ `brochure-websites` or `web-applications`)

---

## Pricing Anchors
| Item | Price |
|---|---|
| Security & Performance Audit | **£299 one-off** |
| Delivery timeline | **5–7 business days** |
| Deliverables | PDF report + 1-hour debrief call |
| Follow-on fix implementation | **custom quote** |

- Post-audit upsell: `maintenance` from £149/mo (monitoring + updates)
- Post-audit upsell: `seo` from £499/mo (if performance issues affect rankings)
- Post-audit upsell: `web-applications` if site requires significant rebuild

---

## Risks and Dependencies
- **Access requirements** — audit requires read-only or limited access to hosting dashboard, CMS backend, and ideally DNS/SSL settings; confirm access can be provided
- **Scope creep** — client may expect fixes included in the £299 price; be explicit that audit = report only
- **Timeline dependency** — 5–7 business day delivery depends on receiving access credentials promptly
- **Regulated industries** — healthcare, financial, or legal sites may have additional compliance requirements beyond OWASP/Core Web Vitals

---

## Assumptions
- Client provides read-only or staging access to CMS and hosting control panel
- Website is currently live and accessible (not in maintenance mode)
- Client accepts that the £299 audit covers assessment and report only; fixes are quoted separately
- 1-hour debrief call is scheduled within 10 business days of report delivery

---

## Open Questions
- [ ] What platform/CMS is the site built on?
- [ ] Who can provide hosting and CMS access for the audit?
- [ ] Has the site been involved in any security incidents or received security warnings?
- [ ] Are there compliance requirements (GDPR, PCI, ISO) to check against?
- [ ] Is the client looking for a one-off health check or ongoing maintenance post-audit?

---

## Recommended Next Step
1. Confirm site URL, platform, and access method
2. Issue audit agreement (£299 one-off, 5–7 business day delivery)
3. Schedule debrief call date at sign-up
4. Post-audit: present `maintenance` retainer or fix implementation quote based on findings
