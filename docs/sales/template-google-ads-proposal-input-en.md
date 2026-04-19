# Google Ads (PPC) — Proposal Input Brief
> Service: google-ads
> Market: UK
> Brief Type: Proposal Input
> Status: Draft
> Last Updated: 2026-04-17
> Source Anchors: ServiceItemSeeder.php, skrypt-sprzedazowy.md

---

## Goal
Capture all details needed to write a Google Ads management proposal: campaign structure, tracking setup, budget breakdown, KPIs, milestones, and upsell opportunities.

---

## Client Context
| Field | Answer |
|---|---|
| Client name | `[client_name]` |
| Website URL | `[website_url]` |
| Industry | `[industry]` |
| Primary contact | `[contact_name]`, `[email]`, `[phone]` |
| Decision-maker confirmed | `[name]` |
| Monthly ad spend agreed | `[£XXX/mo]` |
| Management fee tier | Starter (£399/mo) / Growth (£599/mo) / Custom |
| Contract start date | `[date]` |
| Existing Google Ads account | Yes (MCC access granted) / No (new account) |

---

## Offer Fit
- Service: **Google Ads (PPC)** (slug: `google-ads`)
- Campaign types: `[Search / Shopping / Performance Max / Display / Remarketing]`
- Fit confirmed: Yes / Conditionally / Pending
- Key value to client: `[e.g. immediate leads, lower CPA than current, launch new product]`

---

## Scope and Boundaries

### Campaign Structure
| Campaign | Type | Monthly Budget | KPI |
|---|---|---|---|
| `[Campaign 1]` | `[Search / Shopping / PMax]` | `[£XXX/mo]` | `[leads / ROAS / CPA]` |
| `[Campaign 2]` | `[Remarketing]` | `[£XXX/mo]` | `[CTR / conversions]` |

### Conversion Tracking Setup
| Conversion Action | Tracking Method | Status |
|---|---|---|
| Form submission | GA4 + GTM goal | `[configured / to set up]` |
| Phone call (clicks) | GTM click listener | `[configured / to set up]` |
| Purchase | GA4 e-commerce event | `[configured / to set up]` |

### Targeting
| Parameter | Detail |
|---|---|
| Geography | `[UK national / city / postcode radius]` |
| Language | `[English]` |
| Device | All / Desktop priority / Mobile priority |
| Audience lists | Remarketing / Customer match / Lookalike |

### Ad Copy and Creative
- Brand guidelines: `[provided / TBD]`
- Key messages / USPs: `[list]`
- Ad extensions: Sitelinks, Callouts, Call, Location — `[confirmed / TBD]`
- Display / video creative: `[client provides / WE produces / N/A]`

### Landing Pages
| Campaign | Landing Page URL | Conversion Rate (if known) |
|---|---|---|
| `[Campaign 1]` | `[URL]` | `[n%]` |
| `[Campaign 2]` | `[URL]` | `[n%]` |

### Out of Scope
- Meta / Facebook / Instagram campaigns (→ `meta-ads`)
- SEO or organic activities (→ `seo`)
- Video / YouTube production (unless specified)
- Website redesign (→ `brochure-websites`)

---

## Pricing Anchors
| Line Item | Monthly Cost |
|---|---|
| Management fee (`[Starter / Growth]`) | `[£399 / £599]` |
| Monthly ad spend (paid directly to Google) | `[£XXX/mo]` |
| One-off GTM / tracking setup (month 1) | `[£XXX or included]` |
| **Total monthly commitment** | `[£XXX/mo]` |

**Payment terms:** Management fee — monthly in advance. Ad spend — paid directly to Google Ads account.

---

## Milestones
| Phase | Timeline | Key Activities |
|---|---|---|
| Week 1 | Pre-launch | Account audit / creation, tracking setup, keyword research |
| Week 2 | Pre-launch | Campaign structure, ad copy drafting, landing page review |
| Week 3 | Launch | Campaigns go live, conversion tracking verified |
| Month 1 review | ~Week 4 | Performance check, first optimisation round |
| Month 2–3 | Optimisation | Negative keyword build-out, bid adjustments, A/B testing |
| Month 3 | Review | Full performance report, retainer renewal discussion |

---

## Upsells & Cross-sells
| Opportunity | Value | Status |
|---|---|---|
| SEO retainer (`seo`) | from £499/mo | `[interested / declined / TBD]` |
| Meta Ads (`meta-ads`) | from £349/mo | `[interested / declined / TBD]` |
| Landing page build (`brochure-websites`) | from £799 | `[interested / declined / TBD]` |
| Maintenance plan (`maintenance`) | from £149/mo | `[interested / declined / TBD]` |
| Content creation (`content`) | from £199/mo | `[interested / declined / TBD]` |

---

## Risks and Dependencies
- **Conversion tracking** — campaigns cannot be optimised without verified tracking; setup must be complete before launch
- **Landing page quality** — if conversion rate is below 1%, ad spend is being wasted; recommend CRO review
- **Ad account history** — if account has poor Quality Scores or suspended policies, recovery takes 2–4 weeks
- **Budget volatility** — if client needs to pause ad spend, campaigns lose learning data; restart takes 2–4 weeks
- **Policy restrictions** — some industries (healthcare, finance, legal) face additional Google Ads restrictions; verify before launch

---

## Assumptions
- Google Ads account created or MCC access granted before campaign setup begins
- All conversion tracking events verified live before any spend is activated
- Client approves ad copy before campaign launch
- Ad spend is separate from and in addition to the management fee
- KPIs are agreed in writing before month 1 to avoid scope disputes

---

## Open Questions
- [ ] Has Google Tag Manager been installed on the website?
- [ ] Are there any brand keyword restrictions or competitor name policies?
- [ ] Does the client have an existing remarketing audience (website visitors)?
- [ ] Are there any special promotional periods (e.g., seasonal offers, product launches)?
- [ ] Will the client be managing any campaigns themselves alongside Website Expert?

---

## Recommended Next Step
1. Complete tracking setup audit and confirm all conversion actions are firing
2. Draft campaign structure document for client approval
3. Issue management agreement with KPI targets and reporting schedule
4. Launch campaigns in week 3; schedule 30-day performance review call
