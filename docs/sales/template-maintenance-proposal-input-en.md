# Website Maintenance — Proposal Input Brief
> Service: maintenance
> Market: UK
> Brief Type: Proposal Input
> Status: Draft
> Last Updated: 2026-04-17
> Source Anchors: ServiceItemSeeder.php, skrypt-sprzedazowy.md

---

## Goal
Capture all details needed to issue a maintenance retainer agreement and begin onboarding: site details, access requirements, current state of backups and updates, hosting decision, pricing confirmation, and upsell opportunities.

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
| Current hosting provider | `[provider]` |
| Current WordPress version | `[x.x.x / unknown]` |
| Last plugin update | `[date / unknown]` |
| Existing backups | Yes (where: `[location]`) / No |
| Managed hosting required | Yes / No |
| Contract start date | `[date]` |

---

## Offer Fit
- Service: **Website Maintenance** (slug: `maintenance`)
- Fit confirmed: Yes
- Primary driver: `[uptime guarantee / update management / security / backup recovery / response SLA]`
- Key value to client: `[e.g. peace of mind, remove management burden, 2-hour critical response]`

---

## Scope and Boundaries

### Services Included
| Service | Included | Frequency | Notes |
|---|---|---|---|
| Plugin / theme / CMS core updates | ✅ | As released (monthly sweep) | WordPress-specific for CMS updates |
| 24/7 uptime monitoring + instant alerts | ✅ | Continuous | Alert threshold: `[1min / 5min]` |
| Off-site weekly backups | ✅ | Weekly | 30-day retention |
| Monthly performance + security report | ✅ | Monthly | Delivered by `[5th / 10th]` of month |
| Priority bug fixing | ✅ | On-demand | 2-hour response SLA for critical issues |
| SSL certificate renewal | ✅ | Annual | Auto-renewed where possible |
| DNS management | ✅ | On-demand | Up to `[n]` DNS record changes/mo |

### Managed Hosting (if applicable)
| Item | Status |
|---|---|
| Managed hosting add-on | £29/mo (`[confirmed / declined]`) |
| Migration from current host | `[required / not required]` |
| Migration timeline | `[date or TBD]` |
| Domain transfer | `[required / not required]` |

### Current Site Assessment
| Item | Status | Risk |
|---|---|---|
| WordPress / CMS version | `[current / outdated]` | `[High / Medium / Low]` |
| Plugins last updated | `[date]` | `[High / Medium / Low]` |
| Backups confirmed | `[yes / no]` | `[High / Medium / Low]` |
| SSL active | `[yes / no]` | `[High / Medium / Low]` |
| Recent security warnings | `[yes / no]` | `[High / Medium / Low]` |
| Audit completed | `[yes (date) / no]` | `[recommend before start]` |

### Out of Scope
- New feature development (→ separate dev quote)
- Design or content changes (→ separate quote)
- SEO strategy and optimisation (→ `seo` retainer)
- Paid ads management (→ `google-ads` / `meta-ads`)
- Initial security remediation if site is actively compromised at onboarding (→ emergency remediation quote)

---

## Pricing Anchors
| Line Item | Monthly Cost |
|---|---|
| Standard Maintenance | **£149/mo** |
| Managed Hosting add-on (optional) | **+ £29/mo** |
| **Total monthly** | **`[£149 or £178]/mo`** |

**Payment terms:** Monthly in advance, rolling contract. 30-day written cancellation notice required.

One-off onboarding setup: included in first month.
Emergency remediation (if required at onboarding): custom quote.

---

## Milestones
| Phase | Timeline | Key Activities |
|---|---|---|
| Week 1 | Onboarding | Access credentials received, monitoring tools installed, backup configured |
| Week 1 | Baseline check | CMS version, plugin audit, SSL status confirmed |
| Week 2 | First updates | Plugins, themes, and CMS core updated to latest stable versions |
| Week 2 | Backups | First off-site backup verified and recovery tested |
| End of Month 1 | First report | Monthly performance and security report delivered |
| Month 2 onwards | Ongoing | Monthly update cycle, monitoring alerts, reports |
| Month 3 | Review | Service quality review; discuss any additional needs |

---

## Upsells & Cross-sells
| Opportunity | Value | Status |
|---|---|---|
| Security & Performance Audit (`audits`) | £299 one-off | `[completed / recommended / declined]` |
| SEO retainer (`seo`) | from £499/mo | `[interested / declined / TBD]` |
| Content creation (`content`) | from £199/mo | `[interested / declined / TBD]` |
| Site rebuild (`brochure-websites`) | from £799 | `[interested / declined / TBD]` |
| Managed hosting add-on | £29/mo | `[confirmed / declined]` |

---

## Risks and Dependencies
- **Severely outdated CMS** — if WordPress or CMS core is significantly out of date, major updates may require staging environment testing before applying to production; client approval required
- **No backups at onboarding** — first month priority is backup setup; until verified, site has no disaster recovery
- **Complex hosting environment** — locked reseller accounts or non-standard hosting may restrict monitoring tool installation
- **Third-party developers** — if the client has other developers with access, update conflicts are possible; coordinate access management
- **Actively compromised site** — if site is already hacked or infected, emergency remediation is required before standard maintenance can begin (additional cost)

---

## Assumptions
- Client provides full admin access to CMS and hosting control panel before month 1 begins
- Rolling monthly contract — cancellation requires 30 days' written notice
- Standard maintenance covers standard CMS platforms (WordPress); non-standard platforms to be agreed in writing
- 2-hour SLA applies to critical issues (site down, security breach) — non-critical requests within 24 hours
- Managed hosting migration (if applicable) scheduled within first 30 days

---

## Open Questions
- [ ] Has admin access to CMS and hosting been confirmed?
- [ ] Are there currently any active security warnings, malware, or known issues?
- [ ] Is a managed hosting migration required or preferred?
- [ ] Has an `audits` health check been completed? (Recommend if not)
- [ ] Are there any third-party developers or agencies with current site access to coordinate with?

---

## Recommended Next Step
1. If no recent audit: complete `audits` (£299) before onboarding for a clean baseline
2. Confirm CMS, hosting details, and admin access
3. Issue maintenance agreement (£149/mo ± £29/mo hosting) with 30-day cancellation notice
4. Schedule onboarding call — install monitoring tools and configure backups in week 1
