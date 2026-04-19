# Meta / Pixel Ads — Proposal Input Brief
> Service: meta-ads
> Market: UK
> Brief Type: Proposal Input
> Status: Draft
> Last Updated: 2026-04-17
> Source Anchors: ServiceItemSeeder.php, skrypt-sprzedazowy.md

---

## Goal
Capture all details needed to write a Meta Ads management proposal: campaign structure, creative brief, pixel setup, audience strategy, budget breakdown, KPIs, milestones, and upsell opportunities.

---

## Client Context
| Field | Answer |
|---|---|
| Client name | `[client_name]` |
| Website URL | `[website_url]` |
| Industry | `[industry]` |
| Primary contact | `[contact_name]`, `[email]`, `[phone]` |
| Decision-maker confirmed | `[name]` |
| Facebook Page URL | `[facebook_url]` |
| Instagram Handle | `[instagram_handle]` |
| Monthly ad spend agreed | `[£XXX/mo]` |
| Management fee tier | Starter (£349/mo) / Growth (£499/mo) / Custom |
| Contract start date | `[date]` |
| Existing Meta Ads account | Yes (Business Manager access granted) / No |

---

## Offer Fit
- Service: **Meta / Pixel Ads** (slug: `meta-ads`)
- Campaign types: `[Awareness / Traffic / Leads / Sales / Retargeting / Dynamic Product Ads]`
- Fit confirmed: Yes / Conditionally / Pending
- Key value to client: `[e.g. brand awareness, social leads, retarget website visitors, product launches]`

---

## Scope and Boundaries

### Campaign Structure
| Campaign | Objective | Audience | Monthly Budget | KPI |
|---|---|---|---|---|
| `[Campaign 1]` | `[Leads / Sales]` | `[Interest / Lookalike]` | `[£XXX/mo]` | `[CPL / ROAS]` |
| `[Campaign 2]` | `[Retargeting]` | `[Website visitors]` | `[£XXX/mo]` | `[CTR / CVR]` |

### Pixel and Tracking Setup
| Item | Status |
|---|---|
| Meta Pixel installed | `[yes / needs install]` |
| Conversions API (CAPI) configured | `[yes / to set up]` |
| Page View event | `[firing / to configure]` |
| Lead / Purchase event | `[firing / to configure]` |
| Custom Conversions | `[defined / TBD]` |

### Audience Strategy
| Audience | Type | Size Estimate | Campaign |
|---|---|---|---|
| `[Cold audience 1]` | Interest | `[~XXX,XXX]` | `[Campaign 1]` |
| `[Lookalike 1%]` | Lookalike (email seed) | `[~XXX,XXX]` | `[Campaign 1]` |
| `[Retargeting]` | Custom (website visitors 30d) | `[~XXX]` | `[Campaign 2]` |

### Creative Brief
| Ad Type | Format | Copy Provided By | Visual Provided By |
|---|---|---|---|
| Awareness | Static image / Carousel | `[WE / client]` | `[WE / client]` |
| Lead Form | Lead ad | `[WE / client]` | `[WE / client]` |
| Retargeting | Carousel / Video | `[WE / client]` | `[WE / client]` |

- Brand tone-of-voice: `[formal / conversational / bold]`
- Key USPs to emphasise: `[list]`
- Ad formats: Static / Carousel / Video / Reels / Lead Form — `[confirmed]`

### Landing Pages
| Campaign | Landing Page URL | Pixel Event Firing |
|---|---|---|
| `[Campaign 1]` | `[URL]` | `[Lead / Purchase / ViewContent]` |
| `[Campaign 2]` | `[URL]` | `[Lead / Purchase / ViewContent]` |

### Out of Scope
- Google Ads or paid search (→ `google-ads`)
- Organic social media management
- Full video production (minor editing included; full production is extra)
- Website or landing page builds (→ `brochure-websites`)

---

## Pricing Anchors
| Line Item | Monthly Cost |
|---|---|
| Management fee (`[Starter / Growth]`) | `[£349 / £499]` |
| Monthly ad spend (paid directly to Meta) | `[£XXX/mo]` |
| One-off Pixel / CAPI setup (month 1) | `[£XXX or included]` |
| **Total monthly commitment** | `[£XXX/mo]` |

**Payment terms:** Management fee — monthly in advance. Ad spend — paid to Meta Business Manager.

---

## Milestones
| Phase | Timeline | Key Activities |
|---|---|---|
| Week 1 | Pre-launch | Account/Business Manager setup, Pixel install, audience research |
| Week 2 | Pre-launch | Campaign structure, creative briefs, copy drafting, client approval |
| Week 3 | Launch | Campaigns live, Pixel events verified, spend activated |
| Month 1 review | ~Week 4 | Performance check, audience performance, creative A/B test results |
| Month 2–3 | Optimisation | Scale winning audiences, refresh creative, introduce Reels/video |
| Month 3 | Review | Full performance report, retainer renewal discussion |

---

## Upsells & Cross-sells
| Opportunity | Value | Status |
|---|---|---|
| Google Ads (`google-ads`) | from £399/mo management | `[interested / declined / TBD]` |
| Content creation (`content`) | from £199/mo | `[interested / declined / TBD]` |
| E-commerce build (`ecommerce`) | from £2,999 | `[interested / declined / TBD]` |
| Landing page (`brochure-websites`) | from £799 | `[interested / declined / TBD]` |
| Maintenance plan (`maintenance`) | from £149/mo | `[interested / declined / TBD]` |

---

## Risks and Dependencies
- **Pixel not installed** — campaigns cannot optimise for conversions; Pixel install is a hard dependency before launch
- **No creative assets** — campaigns cannot launch without at least one set of approved visuals; plan asset delivery timeline
- **iOS 14+ attribution gap** — conversion numbers may appear 20–40% lower in Meta dashboard than actual; client must be briefed
- **Audience size too small** — if total audience is below ~100K, campaigns may enter audience fatigue quickly
- **Special Ad Category** — if business is in housing, finance, or employment, confirm targeting restrictions before launch

---

## Assumptions
- Facebook Business Manager and Ad Account created / access granted before setup begins
- Pixel and CAPI installed and verified before spend is activated
- Client approves all ad copy and creative before campaign launch
- Ad spend is financially separate from and in addition to the management fee
- Minimum 3-month engagement for audience learning and campaign optimisation

---

## Open Questions
- [ ] Has a Facebook Business Manager account been created? (Need Admin or Advertiser access)
- [ ] Is the Meta Pixel installed on the website? Are conversion events firing?
- [ ] Has the client provided brand guidelines, logo, and approved images?
- [ ] Is there an existing customer email list for Lookalike seed audience?
- [ ] Are Dynamic Product Ads (e-commerce catalogue) required?

---

## Recommended Next Step
1. Confirm Business Manager access and Pixel status
2. Deliver creative brief to client with asset requirements and approval timeline
3. Issue management agreement with KPI targets and reporting schedule
4. Launch campaigns in week 3; schedule 30-day performance review call
