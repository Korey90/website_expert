# Web Applications — Proposal Input Brief
> Service: web-applications
> Market: UK
> Brief Type: Proposal Input
> Status: Draft
> Last Updated: 2026-04-17
> Source Anchors: ServiceItemSeeder.php, skrypt-sprzedazowy.md

---

## Goal
Capture the outputs of the paid discovery phase and translate them into a fixed-price proposal: confirmed scope, architecture decisions, user roles, integration map, milestones, and risk register.

*Note: This brief is completed AFTER the paid discovery phase (£599), not before.*

---

## Client Context
| Field | Answer |
|---|---|
| Client name | `[client_name]` |
| Application name / working title | `[app_name]` |
| Primary contact | `[contact_name]`, `[email]`, `[phone]` |
| Decision-maker confirmed | `[name]` |
| Agreed budget range | `[budget]` |
| Hard launch date | `[launch_date]` |
| Discovery phase completed | Yes / Partial |
| Date of this brief | `[brief_date]` |

---

## Offer Fit
- Service: **Web Application** (slug: `web-applications`)
- Application type: `[SaaS / portal / booking / internal tool]`
- Fit confirmed: Yes / Conditionally / Pending
- Key value to client: `[e.g. automates X, replaces spreadsheet Y, enables new revenue via Z]`

---

## Scope and Boundaries

### User Roles
| Role | Permissions | Est. User Count |
|---|---|---|
| `[Admin]` | `[full access]` | `[n]` |
| `[Client]` | `[read + limited write]` | `[n]` |
| `[Staff]` | `[assigned tasks only]` | `[n]` |

### Core Feature Set (Confirmed)
| Feature | Priority | Complexity |
|---|---|---|
| `[Feature 1]` | Must-have | `[Low / Med / High]` |
| `[Feature 2]` | Should-have | `[Low / Med / High]` |
| `[Feature 3]` | Nice-to-have | `[Low / Med / High]` |

### Integration Map
| System | Type | Status |
|---|---|---|
| `[Stripe]` | Payment processing | `[confirmed / TBD]` |
| `[CRM name]` | Data sync | `[confirmed / TBD]` |
| `[API name]` | `[purpose]` | `[confirmed / TBD]` |

### Architecture Decisions
| Decision | Choice | Rationale |
|---|---|---|
| Backend framework | Laravel 13 | Performance, security, team expertise |
| Frontend framework | React 18 + TypeScript | Type safety, component reuse |
| Multi-tenancy | Yes / No | `[reason]` |
| Real-time features | Reverb / Pusher / None | `[reason]` |
| Hosting | `[VPS / cloud / client-managed]` | `[reason]` |

### Out of Scope
- Native mobile app (iOS/Android) — not included
- AI/ML features — not included unless specified above
- Third-party integrations not listed above → change-request process
- Post-launch feature development beyond agreed scope → separate retainer

---

## Pricing Anchors
| Line Item | Price |
|---|---|
| Paid Discovery Phase (completed / credited) | **£599** |
| Build Phase — confirmed scope | `[£XXX]` |
| Third-party integration setup (if applicable) | `[+£XXX]` |
| CI/CD pipeline setup | `[+£XXX]` |
| **Project Total** | `[£XXX]` |
| Add-on: Maintenance retainer | `[+£149/mo]` |
| Add-on: SEO / content (for SaaS marketing) | `[+£499/mo + £199/mo]` |

**Payment terms:** 50% upfront / 25% mid-build milestone / 25% on launch (or as agreed).

---

## Milestones
| Milestone | Estimated Date |
|---|---|
| Contract signed + first invoice | `[date]` |
| Architecture and environment setup | `[date]` |
| Sprint 1 delivered (core user flows) | `[date]` |
| Sprint 2 delivered (integrations) | `[date]` |
| Sprint N delivered | `[date]` |
| Internal QA complete | `[date]` |
| UAT with client | `[date]` |
| Launch / handover | `[date]` |
| Post-launch support ends | `[date]` |

---

## Upsells & Cross-sells
| Opportunity | Value | Status |
|---|---|---|
| Maintenance retainer (`maintenance`) | from £149/mo | `[interested / declined / TBD]` |
| SEO for SaaS landing page (`seo`) | from £499/mo | `[interested / declined / TBD]` |
| Content creation (`content`) | from £199/mo | `[interested / declined / TBD]` |
| Google Ads for SaaS launch (`google-ads`) | from £399/mo | `[interested / declined / TBD]` |
| Meta Ads (`meta-ads`) | from £349/mo | `[interested / declined / TBD]` |

---

## Risks and Dependencies
- **Scope change** — any feature added post-contract is subject to formal change-request and additional cost
- **Third-party API delays** — if an integration partner's API is unavailable or undocumented, timeline shifts
- **Client feedback SLA** — UAT must be completed within 5 business days of delivery or the timeline extends
- **Infrastructure** — server provisioning or cloud account setup must be completed before development starts
- **Data migration** — if existing data must be migrated, a data audit is required before committing to timeline

---

## Assumptions
- Discovery phase outputs (spec, wireframes) are the contractual basis for the build
- A formal change-request process governs any scope additions
- Client provides all third-party credentials and API keys before integration sprints begin
- Post-launch: bug fixes within agreed warranty period included; new features billed separately

---

## Open Questions
- [ ] Has infrastructure / hosting environment been provisioned?
- [ ] Are all third-party API credentials available?
- [ ] Is a staging environment required for UAT?
- [ ] Will the client's internal team be present for sprint reviews?
- [ ] Any GDPR data processing agreements (DPA) required with third-party integrations?

---

## Recommended Next Step
1. Finalise the technical specification as a contractual attachment
2. Issue phased invoice schedule (50% / 25% / 25%)
3. Provision development environments and kick off Sprint 1
4. Schedule weekly sprint review cadence with client
