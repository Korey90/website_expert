# Web Applications — Discovery Brief
> Service: web-applications
> Market: UK
> Brief Type: Discovery
> Status: Draft
> Last Updated: 2026-04-17
> Source Anchors: ServiceItemSeeder.php, skrypt-sprzedazowy.md

---

## Goal
Understand the business problem the prospect is trying to solve with a custom application — scope boundaries, integration needs, user roles, and commercial model — before committing to a discovery phase or fixed-price quote.

---

## Client Context
| Field | Answer |
|---|---|
| Company name | `[client_name]` |
| Industry | `[industry]` |
| Application type | `[SaaS / portal / booking / dashboard / internal tool]` |
| Existing tech stack | `[current_platform]` |
| Primary user types | `[end users / admins / staff / clients]` |
| Estimated users at launch | `[n]` |
| Primary goal | `[automate / replace manual process / new product / expand existing]` |
| Hard deadline | `[deadline]` |
| Budget indication | `[budget_indication]` |

---

## Offer Fit
A bespoke web application from Website Expert suits businesses that:
- Have a specific workflow or problem that off-the-shelf software cannot solve adequately
- Need a Laravel (backend) + React/TypeScript (frontend) stack
- Require user roles, access control, or a multi-tenant architecture
- Have an ongoing development or maintenance relationship in mind

**Not a fit if:** the problem can be solved by a WooCommerce store or a brochure site. For multi-vendor marketplaces without complex custom logic, consider scoping as `ecommerce` first.

---

## Discovery Flow

### The problem
> "What manual process or pain point are you trying to solve with this application?"
> "What do you use today — spreadsheets, off-the-shelf software, manual workflows?"
> "What's the cost of NOT solving this? Revenue lost, time wasted, errors made?"

### Users & roles
> "Who uses this system — internal staff, your customers, partners, or all three?"
> "Do different user types need different permissions or views?"
> "How many users are expected at launch vs. in 12 months?"

### Core features
> "Walk me through the most important thing the system needs to do."
> "Are there any third-party systems it needs to talk to — CRM, payment processor, ERP, API?"
> "Do you need real-time features — notifications, live updates, messaging?"

### Technical context
> "Do you have an existing codebase? If so, what language / framework?"
> "Have you started a specification or wireframes?"
> "Does the team have internal developers who will maintain this after build?"

### Commercial model (for SaaS)
> "Is this an internal tool or a product you'll sell to other businesses?"
> "If SaaS: subscription model, per-seat, usage-based, or freemium?"

### Timeline & budget
> "Is there a regulatory, investment, or operational deadline driving this?"
> "Custom applications typically start from £5,999 for scope-defined projects. Larger builds or SaaS platforms range from £15k–£50k+. Does that align with your planning?"

---

## Scope and Boundaries
**Discovery phase (required for all web application projects):**
- Paid discovery (from £599): requirements gathering, architecture design, fixed-price quote
- Deliverable: technical specification, wireframes, and fixed project quote

**Typical inclusions in build:**
- Laravel 13 backend + React/TypeScript frontend
- Role-based access control (RBAC)
- REST API or GraphQL integration
- Queue jobs, events, real-time notifications (Laravel Reverb)
- Multi-tenancy architecture (if SaaS)
- Automated testing + CI/CD pipeline setup

**Out of scope by default:**
- Mobile native apps (iOS/Android)
- DevOps / server infrastructure beyond standard deployment
- Ongoing product development after handover (requires `maintenance` or separate retainer)
- Data science / ML features

---

## Pricing Anchors
| Tier | Price | Description |
|---|---|---|
| Paid Discovery Phase | from **£599** | Scope, architecture, wireframes → fixed-price quote |
| Small Application | from **£5,999** | Well-defined scope, single user role, no complex integrations |
| Mid-Complexity | from **£15,000** | Multiple roles, third-party integrations, advanced workflows |
| SaaS Platform | from **£25,000+** | Multi-tenancy, billing, admin panel, complex domain logic |
| Code Audit (existing app) | from **£299** | OWASP scan + code quality review before takeover |

- Add-on: Ongoing maintenance retainer — from £149/mo (`maintenance`)
- Add-on: SEO & content for SaaS marketing — from £499/mo (`seo`) + from £199/mo (`content`)

---

## Risks and Dependencies
- **Scope uncertainty** — the most common cause of cost overruns; paid discovery phase is mandatory
- **Third-party API limitations** — undocumented or rate-limited APIs can delay integrations
- **Existing codebase** — taking over legacy code requires a code audit before any fixed-price commitment
- **Non-technical stakeholders** — changing requirements mid-build without a formal change-request process
- **Infrastructure / hosting** — client must own or manage server/cloud infrastructure, or agree to managed hosting add-on

---

## Assumptions
- A paid discovery phase (from £599) precedes any fixed-price development quote
- Client can provide dedicated feedback time during design and development sprints
- All third-party API credentials and accounts are available before build starts
- Post-launch maintenance is handled either by client's internal team or via Website Expert's `maintenance` plan

---

## Open Questions
- [ ] Has a specification or requirements document been started?
- [ ] Is there an existing codebase that needs to be audited or taken over?
- [ ] What hosting / cloud infrastructure does the client use or prefer?
- [ ] Is there internal technical resource who will maintain the app post-launch?
- [ ] Are there compliance or data residency requirements (GDPR, ISO, healthcare)?

---

## Recommended Next Step
1. Qualify on budget (is £5,999+ acknowledged?) and problem clarity
2. Propose a **Paid Discovery Phase** (from £599) as the immediate next step
3. Schedule a 60-minute discovery workshop to map user flows and integrations
4. After discovery: issue fixed-price quote with milestones and change-request process defined
