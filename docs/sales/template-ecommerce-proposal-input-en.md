# E-Commerce Stores — Proposal Input Brief
> Service: ecommerce
> Market: UK
> Brief Type: Proposal Input
> Status: Draft
> Last Updated: 2026-04-17
> Source Anchors: ServiceItemSeeder.php, skrypt-sprzedazowy.md

---

## Goal
Capture all scoping details needed to write a fixed-price e-commerce proposal: platform, SKU count, payment setup, migration, design direction, milestones, and upsell opportunities.

---

## Client Context
| Field | Answer |
|---|---|
| Client name | `[client_name]` |
| Existing store URL | `[store_url]` (or "none") |
| Industry / product type | `[industry]` |
| Primary contact | `[contact_name]`, `[email]`, `[phone]` |
| Decision-maker confirmed | `[name]` |
| Agreed budget range | `[budget]` |
| Hard launch date | `[launch_date]` |
| Date of this brief | `[brief_date]` |

---

## Offer Fit
- Service: **E-Commerce Store** (slug: `ecommerce`)
- Platform decision: WooCommerce / Headless React / TBD
- Fit confirmed: Yes / Conditionally / Pending
- Key value to client: `[e.g. new revenue channel, migrate from Shopify, scale from Etsy]`

---

## Scope and Boundaries

### Product Catalogue
| Detail | Answer |
|---|---|
| Total SKUs at launch | `[n]` |
| Product variants (size, colour, etc.) | Yes / No / TBD |
| Physical / digital / both | `[type]` |
| Subscription products | Yes / No |
| Product data migration required | Yes / No — from `[platform]` |

### Payments
| Detail | Answer |
|---|---|
| Stripe | Yes / No |
| PayPal | Yes / No |
| Klarna / BNPL | Yes / No |
| Apple Pay / Google Pay | Yes / No |
| VAT configuration | Standard / Exempt / Mixed |
| Multi-currency | Yes / No — currencies: `[list]` |

### Shipping & Fulfilment
| Detail | Answer |
|---|---|
| Shipping model | Fixed rates / Carrier-calculated / Free / Mixed |
| Shipping zones | UK only / EU / International |
| Courier API integration | Yes (`[courier]`) / No |
| Click & collect | Yes / No |
| 3PL integration | Yes (`[3PL name]`) / No |

### Design Direction
- Style references: `[URLs or description]`
- Logo and brand assets: `[provided / TBD]`
- Photography: `[client provides / stock / shoot required]`
- Key UX requirements: `[e.g. mega menu, advanced filtering, wishlist]`

### Technical Requirements
- [ ] Google Analytics / GA4 + enhanced e-commerce tracking
- [ ] Google Shopping feed
- [ ] Meta Pixel + Conversions API
- [ ] Cookie consent banner (GDPR)
- [ ] CMS for blog / content pages
- [ ] Customer account area
- [ ] Other: `[specify]`

### Out of Scope
- Multi-vendor marketplace functionality
- Custom ERP / warehouse integration (unless specified above)
- Subscription billing engine (unless confirmed above)
- Pages beyond agreed scope → change request
- Legal page content (returns, privacy) — client responsibility

---

## Pricing Anchors
| Line Item | Price |
|---|---|
| Platform build (WooCommerce / Headless) | `[£XXX]` |
| Product data upload / migration | `[+£XXX]` |
| Photography sourcing / stock | `[+£XXX]` |
| Google Shopping feed setup | `[+£XXX]` |
| **Project Total** | `[£XXX]` |
| Add-on: Maintenance plan | `[+£149/mo]` |
| Add-on: SEO retainer | `[+£499/mo]` |
| Add-on: Google Ads management | `[+£399/mo]` |
| Add-on: Meta Ads management | `[+£349/mo]` |

**Payment terms:** 50% upfront / 50% on launch (or as agreed).

---

## Milestones
| Milestone | Estimated Date |
|---|---|
| Contract signed + deposit | `[date]` |
| Product data delivered by client | `[date]` |
| Payment gateway confirmed active | `[date]` |
| Design concepts delivered | `[date]` |
| Design approved | `[date]` |
| Development complete | `[date]` |
| UAT / client review | `[date]` |
| Launch | `[date]` |
| 30-day support ends | `[date]` |

---

## Upsells & Cross-sells
| Opportunity | Value | Status |
|---|---|---|
| Google Ads (`google-ads`) | from £399/mo | `[interested / declined / TBD]` |
| Meta Ads (`meta-ads`) | from £349/mo | `[interested / declined / TBD]` |
| SEO retainer (`seo`) | from £499/mo | `[interested / declined / TBD]` |
| Maintenance plan (`maintenance`) | from £149/mo | `[interested / declined / TBD]` |
| Google Shopping feed setup | from £300 | `[interested / declined / TBD]` |
| Security audit (`audits`) | £299 | `[interested / declined / TBD]` |

---

## Risks and Dependencies
- **Product data deadline** — if data is delayed by 5+ days, launch slides accordingly; include in contract
- **Merchant account activation** — Stripe/PayPal must be live at least 3 days before UAT
- **Legal pages** — returns policy, shipping policy, privacy policy are client responsibility; missing = cannot launch
- **VAT complexity** — mixed-rate or EU VAT OSS rules require a pre-launch tax configuration review
- **Migration data quality** — run a data audit before committing to migration timeline and price

---

## Assumptions
- Client provides clean product data (CSV/spreadsheet) by agreed deadline
- A maximum of 2 design revision rounds included in the price
- Client owns domain and provides access within 48 hours of sign-off
- Post-30-day bugs and change requests billed separately at agreed rate

---

## Open Questions
- [ ] Is managed hosting required? (add-on via `maintenance`)
- [ ] Are any industry-specific certifications or compliance labels needed on product pages?
- [ ] Is there a referral / discount code system requirement?
- [ ] Who manages the store post-launch — client or WE?
- [ ] Any third-party integrations (CRM, ERP, accounting software)?

---

## Recommended Next Step
1. Confirm platform choice, SKU count, and migration scope
2. Request product data sample (5–10 products) to validate data quality
3. Draft proposal with phased milestones if data migration is complex
4. Attach upsell summary for Google Ads + SEO to maximise launch ROI
