# Google Ads (PPC) — Qualification Brief
> Service: google-ads
> Market: UK
> Brief Type: Qualification
> Status: Draft
> Last Updated: 2026-04-17
> Source Anchors: ServiceItemSeeder.php, skrypt-sprzedazowy.md

---

## Goal
Determine whether the lead has sufficient ad budget, a working website, and conversion tracking in place (or the willingness to set it up) to run profitable Google Ads campaigns.

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
| Ad spend budget | ≥ £500/mo confirmed | Below £300/mo |
| Management fee | ≥ £399/mo accepted | Wants ads managed for free or near-free |
| Website | Has landing page with clear CTA | No website or homepage only with no CTA |
| Conversion tracking | Has or willing to set up | Refuses to add tracking |
| Timeline expectations | Understands 2–4 week ramp-up | Wants ROI in 3 days |
| Offer clarity | Clear product/service and target customer | "I sell everything to everyone" |

---

## Qualification Criteria

### Must-Have
- [ ] Ad spend budget ≥ £500/mo (paid to Google, separate from management fee)
- [ ] Management fee ≥ £399/mo accepted
- [ ] Website with at least one conversion action (form, phone number, purchase)
- [ ] Willing to implement conversion tracking before campaign launch
- [ ] Clear product or service with defined target customer

### Nice-to-Have
- [ ] Existing Google Ads account to audit (provides quick-win opportunities)
- [ ] Google Analytics / GA4 already configured
- [ ] Defined CPA or ROAS target
- [ ] Interest in remarketing audiences (increases campaign efficiency)
- [ ] Landing page already optimised for conversion

### Red Flags
- 🚨 "My budget is £200/mo total — ads + management"
- 🚨 "I don't want to pay anything until I see results"
- 🚨 "I sell globally to every industry with no focus"
- 🚨 Competitors have significantly higher budgets in a highly competitive market
- 🚨 No website or landing page available at campaign start
- 🚨 "Previous agency spent my budget with zero results" — audit required before committing

---

## Scope and Boundaries
**Minimum entry (£399/mo management + £500/mo ad spend):**
Google Search campaigns, conversion tracking setup, keyword research, ad copy, monthly reporting.

**Scope that triggers tier upgrade:**
- Google Shopping campaigns → Growth tier recommended (£599/mo)
- Performance Max → Growth tier
- Remarketing + audience layers → Growth tier
- Display or YouTube campaigns → Scale tier or custom quote

---

## Pricing Anchors
| Scenario | Management Fee | Ad Spend | Total Monthly |
|---|---|---|---|
| Starter (Search only) | **£399/mo** | **£500/mo** | **£899/mo** |
| Growth (Search + Shopping/PMax) | **£599/mo** | **£1,000/mo** | **£1,599/mo** |
| Scale (Full account) | **custom** | **£2,000+/mo** | **custom** |
| Free audit of existing account | **£0** | — | Prerequisite |

---

## Risks and Dependencies
- No conversion tracking = no optimisation signal; campaigns will underperform
- Weak landing page will waste ad spend; pair with `brochure-websites` if needed
- Competitive market (e.g., solicitors, insurance) = high CPCs; client must accept variable CPA
- Seasonality: ad spend may need to scale up/down; budget flexibility is important

---

## Assumptions
- Client controls or can access their Google Ads account (or willing to create one)
- Ad spend is a separate budget line, not included in the management fee
- Minimum 3-month engagement to allow campaign learning and optimisation
- Google Tag Manager access provided for conversion tracking setup

---

## Open Questions
- [ ] Does the client have an existing Google Ads account? (Run a free audit)
- [ ] Is conversion tracking currently active?
- [ ] What is the product / service average order or lead value?
- [ ] Has the client defined a maximum acceptable CPA?
- [ ] Are there any existing negative keyword lists or campaign structures?

---

## Recommended Next Step
- **Qualified** → Run free account audit (or keyword research); present 3-month Starter proposal
- **Conditionally qualified** → Address landing page gap or tracking gap first; revisit in 2–4 weeks
- **Not qualified** → Redirect: if organic traffic needed, offer `seo`; if social reach, offer `meta-ads`
