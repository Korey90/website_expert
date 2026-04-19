# Meta / Pixel Ads — Qualification Brief
> Service: meta-ads
> Market: UK
> Brief Type: Qualification
> Status: Draft
> Last Updated: 2026-04-17
> Source Anchors: ServiceItemSeeder.php, skrypt-sprzedazowy.md

---

## Goal
Determine whether the lead has the social presence, creative assets, budget, and audience profile to run effective Meta Ads campaigns on Facebook and Instagram.

---

## Client Context
| Field | Answer |
|---|---|
| Company name | `[client_name]` |
| Website URL | `[website_url]` |
| Lead source | `[lead_source]` |
| Industry | `[industry]` |
| Contact name & role | `[contact_name]`, `[role]` |
| Decision-maker | Yes / No / Shared |
| Monthly ad spend available | `[£XXX/mo]` |
| Management fee accepted | Yes / No |

---

## Offer Fit
| Criterion | Qualified | Not Qualified |
|---|---|---|
| Ad spend budget | ≥ £300/mo confirmed | Below £200/mo |
| Management fee | ≥ £349/mo accepted | Wants management included in ad spend |
| Business type | B2C product, service, or brand-awareness | Pure B2B with no consumer audience |
| Creative assets | Has images/video or willing to commission | No visuals and refuses to invest in creative |
| Social pages | Active Facebook Page / Instagram account | No social presence and unwilling to create one |
| Conversion mechanism | Landing page, lead form, or product page | Traffic destination unclear or non-existent |
| Meta Pixel | Installed or willing to install | Refuses tracking setup |

---

## Qualification Criteria

### Must-Have
- [ ] Ad spend budget ≥ £300/mo (paid to Meta, separate from management fee)
- [ ] Management fee ≥ £349/mo accepted
- [ ] Active Facebook Business Page (Instagram optional but recommended)
- [ ] At least one conversion destination: landing page, product page, or lead form
- [ ] Willing to install Meta Pixel before campaign launch
- [ ] Minimum creative asset: at least one set of approved brand images

### Nice-to-Have
- [ ] Existing Meta Pixel with event data (website visitors, add-to-cart, etc.)
- [ ] Email list or customer data for Custom Audiences / Lookalike seed
- [ ] Video content available or in production
- [ ] Instagram presence with existing organic engagement
- [ ] Clear promotional calendar (seasonal offers, launches)
- [ ] E-commerce product catalogue (for Dynamic Product Ads)

### Red Flags
- 🚨 "My total budget is £200 — that includes everything"
- 🚨 "I tried Meta Ads before and it was a complete waste of money" (investigate root cause before committing)
- 🚨 "I don't want any tracking on my website"
- 🚨 Pure B2B with high-value niche clients (e.g., enterprise SaaS) — Meta often wrong channel, suggest `google-ads`
- 🚨 No website or landing page available at campaign start
- 🚨 Industry in Meta's Special Ad Category (housing, finance, employment) — limited targeting available

---

## Scope and Boundaries
**Minimum entry (£349/mo management + £300/mo ad spend):**
Facebook and Instagram campaigns, Pixel setup, interest audience targeting, ad copy creation, monthly report.

**Scope that triggers tier upgrade:**
- Dynamic Product Ads (e-commerce catalogue) → Growth tier (£499/mo)
- Lookalike audience campaigns → Growth tier
- Reels + video ads → Growth tier
- Full-funnel multi-campaign strategy → Scale or custom

---

## Pricing Anchors
| Scenario | Management Fee | Ad Spend | Total Monthly |
|---|---|---|---|
| Starter | **£349/mo** | **£300/mo** | **£649/mo** |
| Growth (Lookalike, DPA, Reels) | **£499/mo** | **£600/mo** | **£1,099/mo** |
| Scale (Full funnel) | **custom** | **£1,500+/mo** | **custom** |
| Free Meta audit (existing account) | **£0** | — | Prerequisite |

---

## Risks and Dependencies
- No Pixel = no conversion optimisation, only traffic or reach objectives
- Weak creative is the top reason Meta campaigns underperform; brief client on creative expectations
- iOS 14+ attribution loss — Meta may under-report conversions by 20–40%; client must understand this
- Special Ad Categories (housing, employment, finance) restrict audience targeting significantly
- Small total audience size on Meta in niche B2B markets can lead to audience fatigue quickly

---

## Assumptions
- Client controls or will create a Facebook Business Manager and Ad Account
- Meta Pixel will be installed before any spend is activated
- Client provides or approves creative assets before campaign launch
- Minimum 3-month engagement for learning phase
- Ad spend is a separate budget commitment from the management fee

---

## Open Questions
- [ ] Has the client run Meta Ads before? What was the outcome?
- [ ] Is a product catalogue available (for DPA / e-commerce)?
- [ ] Does the client have an email list for Custom Audience seeding?
- [ ] What is the primary conversion goal — lead, purchase, or call?
- [ ] Are there any upcoming product launches or seasonal promotions?

---

## Recommended Next Step
- **Qualified** → Run free account audit or audience sizing; present 3-month Starter proposal
- **Conditionally qualified** → Address creative gap or landing page issue; revisit in 2–4 weeks
- **Not qualified** → Redirect: if intent-based traffic needed, offer `google-ads`; if content is lacking, offer `content` retainer first
