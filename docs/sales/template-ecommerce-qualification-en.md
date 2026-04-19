# E-Commerce Stores — Qualification Brief
> Service: ecommerce
> Market: UK
> Brief Type: Qualification
> Status: Draft
> Last Updated: 2026-04-17
> Source Anchors: ServiceItemSeeder.php, skrypt-sprzedazowy.md

---

## Goal
Determine whether the lead has the budget, product readiness, and operational clarity to proceed with an e-commerce build — before investing time in a full proposal.

---

## Client Context
| Field | Answer |
|---|---|
| Company name | `[client_name]` |
| Lead source | `[lead_source]` |
| Products / industry | `[products]` |
| Contact name & role | `[contact_name]`, `[role]` |
| Decision-maker | Yes / No / Shared |
| Budget confirmed | `[budget]` |
| Target launch date | `[launch_date]` |
| SKU count | `[n]` |

---

## Offer Fit
| Criterion | Qualified | Not Qualified |
|---|---|---|
| Budget | ≥ £2,999 confirmed or implied | Below £1,500 |
| SKU count | 1–999 (WooCommerce) or 1000+ (headless) | No products defined or concept only |
| Payment gateway | Stripe and/or PayPal acceptable | Requires unlisted gateway with no API |
| Fulfilment | Self-fulfilled, 3PL, or digital delivery | Unresolved "will figure it out later" |
| Timeline | Minimum 6–8 weeks available | Needs live in 2 weeks |
| Data readiness | Product data in spreadsheet or existing platform | No images, no descriptions, no prices |

---

## Qualification Criteria

### Must-Have
- [ ] Budget ≥ £2,999 acknowledged
- [ ] At least a draft product catalogue exists (even in spreadsheet)
- [ ] Decision-maker identified and accessible
- [ ] Stripe or PayPal merchant account, or willingness to open one
- [ ] Minimum 6 weeks to launch

### Nice-to-Have
- [ ] Existing store to migrate from (increases urgency and data availability)
- [ ] Product photography already done
- [ ] Interest in Google Shopping or Meta Ads on launch
- [ ] Interest in SEO retainer or maintenance plan
- [ ] Clear conversion goal (e.g., 100 orders/month within 90 days)

### Red Flags
- 🚨 "I have 5,000 products, budget is £1,500"
- 🚨 "I'll add products after launch — we only have product names right now"
- 🚨 No merchant account, no VAT registration, no shipping setup
- 🚨 "I need Klarna, AfterPay, and a custom loyalty system for £3k"
- 🚨 Decision to be made by a board with no timeline
- 🚨 Expecting guaranteed sales figures post-launch

---

## Scope and Boundaries
**Minimum viable e-commerce (£2,999):** WooCommerce, up to 50 products, Stripe + PayPal, mobile-first design, basic SEO, 30-day support.

**Scope triggers that require re-qualification:**
- 200+ products at launch → add product upload service
- Multi-currency or multi-language → +£500–£1,500 depending on scope
- Custom ERP integration → separate technical scoping required
- Subscription / recurring billing → platform and complexity review required

---

## Pricing Anchors
| Scenario | Price |
|---|---|
| WooCommerce up to 50 products, client provides data | **£2,999** |
| WooCommerce 50–250 products, standard design | **£4,000–£6,000** |
| Headless React storefront, 500+ SKUs | **from £8,000** |
| Product data migration from existing platform | **from £500** |
| Add: Google Shopping feed | **from £300** |
| Add: Maintenance plan | **+£149/mo** |
| Add: SEO retainer | **+£499/mo** |

---

## Risks and Dependencies
- Product data quality is the single biggest risk to timeline — must be confirmed upfront
- Payment gateway approval from Stripe/PayPal can take up to 5 business days
- Legal pages (returns policy, shipping policy, privacy policy) are client responsibility
- VAT registration and correct tax configuration must be confirmed before launch

---

## Assumptions
- Client has or will promptly open a Stripe/PayPal merchant account
- Product images exist or a photography budget has been allocated
- Shipping zones and rates are pre-defined before development begins

---

## Open Questions
- [ ] WooCommerce or headless — has a recommendation been made?
- [ ] Has the client received quotes from other e-commerce agencies?
- [ ] Are there any industry-specific compliance requirements (food, supplements, age-restricted)?
- [ ] Is there an affiliate or referral programme planned?
- [ ] Who manages inventory post-launch — client or a 3PL?

---

## Recommended Next Step
- **Qualified** → Proceed to Proposal Input; request product data sample and platform access
- **Conditionally qualified** → Confirm product readiness and payment gateway; re-evaluate in 2 weeks
- **Not qualified** → Decline or defer; note potential for `brochure-websites` or `web-applications` if scope changes
