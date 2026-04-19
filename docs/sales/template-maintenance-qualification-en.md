# Website Maintenance — Qualification Brief
> Service: maintenance
> Market: UK
> Brief Type: Qualification
> Status: Draft
> Last Updated: 2026-04-17
> Source Anchors: ServiceItemSeeder.php, skrypt-sprzedazowy.md

---

## Goal
Determine whether the lead has a live, business-critical website with an identifiable management gap that the maintenance retainer can directly address, and whether the client understands the ongoing nature of the engagement.

---

## Client Context
| Field | Answer |
|---|---|
| Company name | `[client_name]` |
| Website URL | `[website_url]` |
| Lead source | `[lead_source]` |
| CMS / Platform | `[WordPress / Shopify / custom]` |
| Contact name & role | `[contact_name]`, `[role]` |
| Decision-maker | Yes / No / Shared |
| Monthly budget | £149/mo accepted / Querying / Too high |
| Current management | `[agency / in-house / no one]` |

---

## Offer Fit
| Criterion | Qualified | Not Qualified |
|---|---|---|
| Live website | Active, publicly accessible site | No website, or site under full rebuild |
| Budget | £149/mo accepted | Wants one-off fix only, no retainer |
| Platform | WordPress, Shopify, or supported CMS | Fully custom platform with in-house DevOps |
| Management gap | No one actively managing/updating/backing up | Large in-house team already covering this |
| Business criticality | Revenue, leads, or appointments depend on site | "It's just a placeholder, no traffic" |
| Access | Client can provide or obtain CMS + hosting access | No access and unwilling to resolve |

---

## Qualification Criteria

### Must-Have
- [ ] Live website exists and is the client's business-critical asset
- [ ] £149/mo monthly budget accepted
- [ ] CMS or hosting admin access can be provided (or obtained within onboarding)
- [ ] Identifiable gap in current site management (no updates, no monitoring, no backups)
- [ ] Client understands this is a rolling monthly retainer, not a one-off fix

### Nice-to-Have
- [ ] No active maintenance contract with another agency
- [ ] Recent or upcoming security concern that creates urgency (hack, malware, warning)
- [ ] Interest in `audits` first to establish baseline (increases deal quality)
- [ ] Running paid ads (Google / Meta) that make downtime financially costly
- [ ] WordPress site with multiple active plugins (higher update risk = higher value)

### Red Flags
- 🚨 "I just need you to fix one thing and I'll take it from there" — qualify as one-off developer task, not maintenance
- 🚨 "We have our own IT team that does all of this" — not the right buyer; confirm there is a gap first
- 🚨 Fully custom application (no off-the-shelf CMS) — maintenance scope may be too complex; escalate to `web-applications` team
- 🚨 "My current host does all the updates automatically" — clarify; most hosts do server-level updates only, not CMS plugins/themes
- 🚨 Client refuses to provide any site access — no access, no monitoring; maintenance cannot be delivered

---

## Scope and Boundaries
**Standard plan (£149/mo):**
Updates, uptime monitoring, off-site backups, monthly report, 2-hour critical response SLA, SSL + DNS.

**Managed hosting add-on (£29/mo):**
Hosting environment managed by Website Expert; migration from current host required.

**Not included:**
- New feature development (→ separate dev quote)
- Design changes (→ separate quote)
- SEO work (→ `seo` retainer)
- Initial audit / security scan (→ `audits` £299 one-off — recommend as entry point)

---

## Pricing Anchors
| Plan | Monthly Fee | Key Inclusions |
|---|---|---|
| Standard Maintenance | **£149/mo** | Updates, monitoring, backups, 2hr SLA, SSL/DNS |
| + Managed Hosting | **+ £29/mo** | Hosting environment managed by Website Expert |

- Entry point: `audits` £299 one-off (ideal pre-maintenance baseline)
- Cross-sell: `seo` from £499/mo

---

## Risks and Dependencies
- Severely outdated WordPress installs may require paid remedial work before maintenance baseline
- If no backups exist, first maintenance task is backup setup — first month may have onboarding effort
- Multi-site or complex e-commerce setups may require higher-tier scoping

---

## Assumptions
- Client is on a standard CMS (WordPress or supported platform)
- Rolling monthly contract with 30-day cancellation notice
- Client accepts 2-hour response SLA applies to critical issues, not feature requests

---

## Open Questions
- [ ] Is anyone currently responsible for site updates and monitoring?
- [ ] When was the site last updated — CMS core, plugins, themes?
- [ ] Are there known security or performance issues to address at onboarding?
- [ ] Is managed hosting migration needed or preferred?
- [ ] Has the client had an `audits` health check recently?

---

## Recommended Next Step
- **Qualified** → Propose Standard Maintenance (£149/mo); schedule onboarding call; request access credentials
- **Conditionally qualified** → Recommend `audits` first (£299) to establish baseline; revisit maintenance after debrief
- **Not qualified (one-off)** → Provide fixed-fee dev quote for specific issue; nurture toward maintenance retainer
