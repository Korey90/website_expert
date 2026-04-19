# Security & Performance Audits — Proposal Input Brief
> Service: audits
> Market: UK
> Brief Type: Proposal Input
> Status: Draft
> Last Updated: 2026-04-17
> Source Anchors: ServiceItemSeeder.php, skrypt-sprzedazowy.md

---

## Goal
Capture all details needed to issue an audit agreement and begin the engagement: site details, access requirements, specific concerns to prioritise, deliverable timeline, and post-audit upsell opportunities.

---

## Client Context
| Field | Answer |
|---|---|
| Client name | `[client_name]` |
| Website URL | `[website_url]` |
| Industry | `[industry]` |
| Primary contact | `[contact_name]`, `[email]`, `[phone]` |
| Decision-maker confirmed | `[name]` |
| CMS / Platform | `[WordPress / Shopify / custom]` |
| Hosting provider | `[provider]` |
| SSL active | Yes / No / Unknown |
| Audit agreed | Yes — £299 one-off |
| Debrief call scheduled | `[date]` |

---

## Offer Fit
- Service: **Security & Performance Audit** (slug: `audits`)
- Fit confirmed: Yes
- Client's primary concern: `[security vulnerabilities / slow site / compliance / all-round health check]`
- Key value: Objective, prioritised risk report with actionable fix list

---

## Scope and Boundaries

### Audit Scope Checklist
| Area | Included | Priority (H/M/L) |
|---|---|---|
| OWASP Top 10 vulnerability scan | ✅ | `[H/M/L]` |
| Core Web Vitals: LCP | ✅ | `[H/M/L]` |
| Core Web Vitals: CLS | ✅ | `[H/M/L]` |
| Core Web Vitals: INP | ✅ | `[H/M/L]` |
| Server configuration review | ✅ | `[H/M/L]` |
| SSL/HTTPS configuration | ✅ | `[H/M/L]` |
| Dependency and CVE audit | ✅ | `[H/M/L]` |
| Outdated plugin / theme review | ✅ (WordPress only) | `[H/M/L]` |
| DNS configuration review | ✅ | `[H/M/L]` |

### Additional Concerns (client-specified)
| Concern | Source | Notes |
|---|---|---|
| `[e.g. Recent malware warning]` | `[Client report / Google Search Console]` | `[Prioritise]` |
| `[e.g. Slow checkout]` | `[Customer complaints]` | `[Focus on LCP/INP]` |

### Access Requirements
| Resource | Required | Status |
|---|---|---|
| CMS backend (admin) | Read-only / full | `[granted / pending]` |
| Hosting control panel | Read-only | `[granted / pending]` |
| DNS management panel | Read-only | `[granted / pending]` |
| Google Search Console | View access | `[granted / pending]` |
| Google Analytics / GA4 | View access | `[granted / pending]` |

### Out of Scope
- Fix implementation (→ separate developer quote post-audit)
- Ongoing monitoring (→ `maintenance` retainer)
- Penetration testing / ethical hacking (separate specialist service)
- SEO keyword or backlink audit (→ `seo` retainer)

---

## Pricing Anchors
| Line Item | Price |
|---|---|
| Security & Performance Audit | **£299** |
| **Total (one-off)** | **£299** |

**Payment terms:** Full payment in advance before audit begins.

Post-audit fix implementation: TBD — custom quote issued after report.

---

## Milestones
| Phase | Timeline | Key Activities |
|---|---|---|
| Day 0 | Sign-up | Agreement signed, payment received, access credentials requested |
| Day 1–2 | Access | CMS, hosting, and tool access verified |
| Day 2–5 | Audit | All audit checks completed; findings drafted |
| Day 5–7 | Report | PDF report compiled; executive summary written |
| Day 7 | Delivery | Report emailed to client |
| Within 10 days | Debrief | 1-hour debrief call: walkthrough of findings and fix priorities |

---

## Upsells & Cross-sells
| Opportunity | Value | Status |
|---|---|---|
| Website Maintenance (`maintenance`) | from £149/mo | `[interested / declined / TBD]` |
| SEO retainer (`seo`) | from £499/mo | `[interested / declined / TBD]` |
| Fix implementation (dev hours) | custom quote | `[interested / declined / TBD]` |
| Full site rebuild (`brochure-websites`) | from £799 | `[interested / declined / TBD]` |
| Web application rebuild (`web-applications`) | from £5,999 | `[interested / declined / TBD]` |

---

## Risks and Dependencies
- **Access delays** — if CMS or hosting access is not provided within 2 business days of sign-up, the delivery timeline shifts accordingly
- **Staging vs live** — audit must be conducted on the production (live) site; results on staging may not reflect live vulnerabilities
- **Emergency findings** — if a critical vulnerability is discovered (e.g., active malware, data breach risk), client will be notified immediately outside the standard report timeline
- **Complex custom platform** — non-standard CMS or heavily customised codebases may require additional time; flag to client if scope expands
- **Regulated sector** — healthcare, financial, or legal sites may have specific compliance frameworks (GDPR, PCI DSS) that require specialist review beyond standard audit scope

---

## Assumptions
- Client provides all required access within 2 business days of payment
- Website is in live/production state, not under active development
- Client accepts that fixes are not included in the £299 and will be quoted separately
- Debrief call will be attended by the client decision-maker or technical lead
- If critical issues are found, client agrees to be contacted immediately

---

## Open Questions
- [ ] Has all required access (CMS, hosting, Search Console) been confirmed?
- [ ] Are there specific pages or features to prioritise during the audit?
- [ ] Is there a known compliance requirement (GDPR, PCI, ISO) to check against?
- [ ] Who will action the findings — client's internal team or Website Expert?
- [ ] Is there a post-audit maintenance or development budget to discuss?

---

## Recommended Next Step
1. Issue audit service agreement and payment link (£299)
2. Send access request checklist to client; set 2-business-day deadline
3. Begin audit within 24 hours of receiving all access
4. After debrief call: present `maintenance` retainer or custom fix implementation quote
