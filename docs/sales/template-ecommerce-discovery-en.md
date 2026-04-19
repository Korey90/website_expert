# E-Commerce Stores — Discovery Brief
> Service: ecommerce
> Market: UK
> Brief Type: Discovery
> Status: Draft
> Last Updated: 2026-04-17
> Source Anchors: ServiceItemSeeder.php, skrypt-sprzedazowy.md, plan-kampanii.md

---

## Goal
Understand the prospect's product catalogue, selling model, fulfilment setup, and existing tech stack so we can scope an e-commerce solution that converts from day one.

---

## Client Context
| Field | Answer |
|---|---|
| Company name | `[client_name]` |
| Industry / product type | `[industry]` |
| Current selling channel | `[website / Etsy / Amazon / none]` |
| Current platform | `[WooCommerce / Shopify / Magento / none]` |
| Approximate SKU count | `[n]` |
| Target audience | `[B2C / B2B / both]` |
| Primary goal | `[launch / migrate / rebuild / expand]` |
| Decision deadline | `[decision_deadline]` |
| Budget indication | `[budget_indication]` |

---

## Offer Fit
An e-commerce store from Website Expert suits businesses that:
- Sell physical or digital products online (or plan to start)
- Have 1–1,000 SKUs (WooCommerce sweet spot) or 1,000+ SKUs (headless React recommended)
- Need Stripe and/or PayPal payment processing
- Want control over their data (own platform vs. marketplace)

**Not a fit if:** client only needs a lead-capture page with no payments → refer to `brochure-websites`. Complex SaaS billing or marketplace model → refer to `web-applications`.

---

## Discovery Flow

### Products & catalogue
> "How many products are you selling? Do you have variants (size, colour, material)?"
> "Are products physical, digital, or a mix?"
> "Do you need subscription / recurring billing?"

### Current state
> "Are you currently selling somewhere? What's not working?"
> "Do you have product data in a spreadsheet / existing system we need to migrate?"

### Fulfilment & logistics
> "How do you handle shipping? Fixed rates, live carrier rates, or click-and-collect?"
> "Do you ship internationally? Multi-currency needed?"

### Payments
> "Which payment methods do you need — Stripe, PayPal, Klarna, Apple Pay?"
> "Are you set up for VAT / tax? Any VAT-exempt categories?"

### Business goals
> "What does success look like in the first 6 months — GMV target, number of orders, CAC?"
> "Are you planning to run Google Shopping or Meta ads alongside the store?"

### Timeline & urgency
> "Is there a product launch, seasonal peak, or campaign that sets a hard deadline?"
> "Who approves the final build — just you, or a team?"

### Budget
> "E-commerce projects like this typically run from £2,999 for WooCommerce up to £8,000+ for headless. Does that align with your planning?"

---

## Scope and Boundaries
**In scope (WooCommerce starter, from £2,999):**
- WooCommerce setup on WordPress or headless storefront
- Up to 50 products (additional products at extra cost)
- Stripe + PayPal integration
- Product catalogue + inventory management
- Abandoned cart recovery emails
- Mobile-first, conversion-optimised design
- Basic SEO setup
- 30 days post-launch support

**Out of scope (requires separate quote / service):**
- 1,000+ SKU headless build → custom quote
- Custom ERP or warehouse management integrations
- Multi-vendor marketplace functionality
- Google Shopping / Meta Catalogue feed setup (can be added)
- Ongoing SEO → `seo` from £499/mo
- Paid advertising management → `google-ads` or `meta-ads`
- Ongoing maintenance → `maintenance` from £149/mo

---

## Pricing Anchors
| Tier | Price | Description |
|---|---|---|
| WooCommerce Starter | from **£2,999** | Up to 50 products, Stripe + PayPal, mobile-first design |
| WooCommerce Mid | from **£5,000** | Up to 250 products, advanced filtering, integrations |
| Headless React | from **£8,000** | 500+ SKUs, performance-critical, custom storefront |
| Product data migration | from **£500** | From existing platform (Shopify, Magento, WooCommerce) |

- Add-on: Google Shopping / Meta Catalogue feed — from £300 one-off
- Add-on: SEO retainer — from £499/mo (`seo`)
- Add-on: Google Ads — from £399/mo (`google-ads`)
- Add-on: Meta Ads — from £349/mo (`meta-ads`)
- Add-on: Maintenance — from £149/mo (`maintenance`)

---

## Risks and Dependencies
- **Product data quality** — missing images, descriptions, or prices delay go-live significantly
- **Payment gateway approval** — Stripe/PayPal merchant account must be live before launch; can take 1–5 days
- **VAT / tax configuration** — complex tax rules (e.g., digital goods VAT, multi-region) require extra scoping
- **Migration complexity** — migrating from Shopify or Magento often surfaces data quality issues
- **Shipping rules** — complex carrier-calculated shipping needs access to courier API credentials

---

## Assumptions
- Client has or will open a Stripe/PayPal merchant account before launch
- Product images provided by client (or stock photos agreed separately)
- Shipping rates and zones defined by client before development starts
- Legal pages (returns, shipping policy, privacy policy) either provided by client or drafted by client (**not included by default**)

---

## Open Questions
- [ ] WooCommerce or headless? (Depends on SKU count and performance requirements)
- [ ] Is product data migration required from an existing platform?
- [ ] Are there any special pricing rules (trade pricing, bulk discounts, membership pricing)?
- [ ] Is multi-currency or multi-language required at launch?
- [ ] What fulfilment model — self-fulfilment, 3PL, drop-shipping?
- [ ] Will Google Shopping or Meta Catalogue feeds be needed?

---

## Recommended Next Step
1. Confirm SKU count and migration requirement
2. Agree on platform (WooCommerce vs. headless) and payment gateway
3. Move to Proposal Input brief; request product data sample and existing site access
4. Upsell: Google Ads (`google-ads`) + SEO retainer (`seo`) to drive traffic from day one
