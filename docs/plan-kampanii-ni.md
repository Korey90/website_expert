# Plan kampanii Google Search — Northern Ireland (NI)
> Data: 2026-04-09  
> Domena: https://website-expert.uk/  
> Rynek: Northern Ireland (BT postcodes) — język angielski  
> Model: Usługi agencji cyfrowej — web design, e-commerce, SEO, Google Ads, Meta Ads  
> Bazuje na: `docs/project-analysis.md`, `docs/plan-kampanii.md`, analiza kodu źródłowego

---

## AUDIT SEO — stan obecny (website-expert.uk)

### ✅ Co działa poprawnie

| Element | Stan | Szczegóły |
|---------|------|-----------|
| `<title>` na stronie głównej | ✅ Istnieje | `WebsiteExpert – Professional Web Development UK` |
| `<meta name="description">` na stronie głównej | ✅ Istnieje | `Bespoke web design and development for UK businesses.` |
| GTM (Google Tag Manager) | ✅ Skonfigurowany | Integracja z GA4 + Meta Pixel — gotowy do kampanii |
| Consent Mode v2 (GDPR) | ✅ Zaimplementowany | `analytics_storage: denied` domyślnie do zgody — zgodny z UK GDPR |
| Meta Pixel | ✅ Gotowy | `useMetaPixel` hook w `MarketingLayout` |
| `dataLayer.js` | ✅ Gotowy | Zdarzenia: `contact_form_submit`, `calculator_lead` — śledzenie konwersji możliwe |
| Wielojęzyczność | ✅ EN/PL/PT | `lang={{ str_replace('_', '-', app()->getLocale()) }}` w `<html>` |
| Formularz kontaktowy → DB | ✅ Działa | `POST /contact` → `ContactController@store` → zapis Lead + email |
| Kalkulator → DB | ✅ Działa | `POST /calculator-lead` → `CalculatorLeadController@store` |
| Robots.txt | ✅ Istnieje | `public/robots.txt` |

### ⚠️ Problemy SEO do naprawy (priorytet przed uruchomieniem kampanii)

| # | Problem | Priorytet | Plik do zmiany |
|---|---------|-----------|----------------|
| 1 | **Brak meta description na stronie kalkulatora w języku angielskim** — obecna treść jest po polsku: `"Oblicz orientacyjny koszt..."` | 🔴 WYSOKI | `resources/js/Pages/Kalkulator.jsx` |
| 2 | **Brak `<meta name="robots">` na stronach auth/portalu** — indexowanie stron `/login`, `/register`, `/portal/*` | 🔴 WYSOKI | `resources/views/app.blade.php` — dodać noindex dla tras auth/portal |
| 3 | **Brak OG tags (Open Graph)** — brak `og:title`, `og:description`, `og:image` na stronie głównej i podstronach usług — wpływa na CTR z social media | 🟡 ŚREDNI | `resources/js/Pages/Welcome.jsx` |
| 4 | **Brak canonical URL** — żadna strona nie ma `<link rel="canonical">` — ryzyko duplicate content przy wielojęzyczności | 🔴 WYSOKI | `resources/views/app.blade.php` lub per-page w `<Head>` |
| 5 | **Title strony głównej nie zawiera lokalizacji NI/Belfast** — dla kampanii lokalnej to krytyczne dla Quality Score | 🔴 WYSOKI | `resources/js/Pages/Welcome.jsx` |
| 6 | **Brak `hreflang`** — przy 3 językach (EN/PL/PT) Google nie wie którą wersję serwować dla UK/PL/BR | 🟡 ŚREDNI | `resources/views/app.blade.php` |
| 7 | **Brak strony `/sitemap.xml`** — Google nie ma mapy witryny; route `/kalkulator` może nie być indeksowany | 🟡 ŚREDNI | Dodać `laravel/sitemap` lub ręczną trasę |
| 8 | **`app.blade.php` tytuł fallback to `'Laravel'`** — `config('app.name', 'Laravel')` — jeśli `APP_NAME` nie ustawione w `.env`, strona ma tytuł "Laravel" | 🔴 WYSOKI | `.env`: `APP_NAME="Website Expert"` |
| 9 | **Brak `<meta name="geo.region">` i `<meta name="geo.placename">`** — dla kampanii lokalnej NI warto dodać | 🟢 NISKI | `resources/js/Pages/Welcome.jsx` |
| 10 | **Brak `<link rel="icon">` (favicon)** — nie widać w `app.blade.php` | 🟡 ŚREDNI | `resources/views/app.blade.php` |

### Szybkie poprawki SEO — do wprowadzenia przed kampanią

#### 1. Welcome.jsx — nagłówek i meta pod NI
```jsx
// resources/js/Pages/Welcome.jsx
<Head>
    <title>Website Expert – Web Design & SEO Belfast, Northern Ireland</title>
    <meta name="description"
          content="Professional web design, e-commerce and SEO services in Belfast and across Northern Ireland. Get a free quote today — website-expert.uk" />
    <meta name="robots" content="index, follow" />
    <link rel="canonical" href="https://website-expert.uk/" />
    <meta property="og:title" content="Website Expert – Web Design Belfast, Northern Ireland" />
    <meta property="og:description"
          content="Bespoke websites, e-commerce, SEO and Google Ads for NI businesses. Fast delivery, fixed prices." />
    <meta property="og:url" content="https://website-expert.uk/" />
    <meta property="og:type" content="website" />
    <meta property="og:locale" content="en_GB" />
    <meta name="geo.region" content="GB-NIR" />
    <meta name="geo.placename" content="Belfast, Northern Ireland" />
    <meta name="twitter:card" content="summary_large_image" />
    <meta name="twitter:title" content="Website Expert – Web Design Belfast" />
    <meta name="twitter:description"
          content="Bespoke web design, SEO and digital marketing for Northern Ireland businesses." />
</Head>
```

#### 2. app.blade.php — noindex dla tras auth i portalu
```blade
{{-- resources/views/app.blade.php — w <head>, po <meta viewport> --}}
@php
    $noIndexRoutes = ['login', 'register', 'password.request', 'password.reset',
                      'verification.notice', 'profile.edit', 'dashboard'];
    $isNoIndex = Route::is(...array_merge($noIndexRoutes, ['portal.*', 'onboarding.*']));
@endphp
@if($isNoIndex)
    <meta name="robots" content="noindex, nofollow">
@else
    <meta name="robots" content="index, follow">
@endif
<link rel="canonical" href="{{ url()->current() }}">
```

#### 3. .env — APP_NAME
```
APP_NAME="Website Expert"
```

---

## 1. Analiza rynku — Northern Ireland

### Rynek NI w liczbach (kontekst)
- Populacja: ~1,9 mln (Belfast: ~340 tys.)
- PKB per capita: ~£25 000/rok (poniżej średniej UK — wrażliwość cenowa wyższa)
- Dominujące sektory: gastronomia/hospitality, budownictwo, handel detaliczny, usługi profesjonalne, turystyka
- Penetracja internetu: ~94% (wysoka)
- Wyszukiwania lokalne: **65% wyszukiwań biznesowych zawiera lokalizację** (Belfast / Northern Ireland / NI)
- Główni konkurenci w Google Ads: małe lokalne agencje + freelancerzy z Dublin/Cork

### Specyfika rynku NI vs reszta UK
| Cecha | Implikacja dla kampanii |
|-------|------------------------|
| Sektor publiczny mocno obecny (NHS, rząd nordirlandzki) | pomijamy — długi cykl sprzedaży, specjalne przetargi |
| Dużo mikrofirm i sole traders | CPC niższe, budżety klientów skromniejsze — komunikuj "fixed price", "no hidden costs" |
| Bliskość Irlandii — wiele firm obsługuje ROI + NI | szansa: obsługa firm cross-border |
| Mniejszy rynek = mniej konkurencji w Google Ads | CPC niższe niż Londyn o ~30-40%; łatwiej osiągnąć top 3 |
| Duże znaczenie polecenia i zaufania lokalnego | social proof: "Belfast-based", "local team" w reklamach |

---

## 2. Persony — rynek NI

### Persona NI-A — Właściciel małej firmy lokalnej (Belfast / Derry)
- **Kim jest:** właściciel restauracji, salonu beauty, firmy budowlanej, sklepu, warsztatu samochodowego
- **Problem:** Strona jest stara lub jej nie ma, pojawia się na stronie 3 Google, traci klientów na rzecz konkurencji
- **Szuka:** "web design Belfast", "website for my business Belfast", "cheap website Northern Ireland"
- **Decyzja:** szybka — wie że potrzebuje, nie wie jak i ile kosztuje
- **Wrażliwość cenowa:** WYSOKA — porównuje, potrzebuje jasnej ceny

### Persona NI-B — Właściciel e-commerce / sklepu
- **Kim jest:** sprzedaje lokalnie + online, ma Shopify lub WooCommerce z lat i chce coś lepszego
- **Problem:** Sklep nie konwertuje, wolny, przestarzały design, brak integracji
- **Szuka:** "e-commerce website Belfast", "Shopify developer Northern Ireland", "online shop redesign NI"
- **Decyzja:** przemyślana — chce portfolio, case study
- **Wrażliwość cenowa:** ŚREDNIA

### Persona NI-C — Właściciel firmy usługowej (SME, 5–50 pracowników)
- **Kim jest:** firma prawnicza, księgowa, budowlana, clinic/healthcare, agencja nieruchomości
- **Problem:** Chce nowej strony z portfolio, formularzem kontaktowym, SEO — "wyglądamy profesjonalnie"
- **Szuka:** "professional website design Northern Ireland", "web agency Belfast", "SEO services Belfast"
- **Decyzja:** powolna, chce wyceny, rozmowy, portfolio
- **Wrażliwość cenowa:** NISKA-ŚREDNIA

### Persona NI-D — Startup / nowa firma
- **Kim jest:** zakłada biznes, potrzebuje szybko strony z branding
- **Szuka:** "new business website Belfast", "startup website design NI", "affordable web design"
- **Decyzja:** szybka — potrzebuje "teraz"
- **Wrażliwość cenowa:** WYSOKA

---

## 3. Słowa kluczowe — kampanie NI

### Metodologia
- Każde słowo z lokalizatorem (`Belfast` / `Northern Ireland` / `NI`) — lepszy QS, wyższy CTR
- Typy dopasowania: `[exact]` dla high-intent + `"phrase"` dla odkrywania
- Wykluczenia: kandydaci do pracy, DIY, darmowe narzędzia, inne kraje (Dublin → +Ireland ≠ Northern Ireland)

---

### Kampania 1 — Web Design Belfast (główna)

**Grupa reklam 1.1 — Web Design Belfast**
```
[web design belfast]
[website design belfast]
[web designer belfast]
[website designer belfast]
"web design belfast"
"website design northern ireland"
[web design northern ireland]
[website designer northern ireland]
[web design company belfast]
[web design agency belfast]
```

**Grupa reklam 1.2 — Web Design NI (szersze NI)**
```
[web design northern ireland]
[website design ni]
[web designer derry]
[web design londonderry]
[web design lisburn]
[web design newry]
[web design antrim]
```

**Grupa reklam 1.3 — Nowa strona internetowa**
```
[new website belfast]
[get a website belfast]
[need a website belfast]
"website for my business belfast"
[small business website belfast]
[business website design belfast]
[affordable website belfast]
[cheap website design belfast]
```

**Wykluczenia kampanii 1:**
```
-free website
-free website builder
-wix
-squarespace
-diy website
-template
-how to build
-wordpress tutorial
-job
-jobs
-employment
-web design course
-learn web design
-web design salary
-dublin
-ireland (bez "northern")
-republic of ireland
```

---

### Kampania 2 — E-Commerce Belfast

**Grupa reklam 2.1 — Sklep online**
```
[ecommerce website belfast]
[online shop design belfast]
[ecommerce developer belfast]
[shopify developer belfast]
[woocommerce developer northern ireland]
[online store design northern ireland]
[ecommerce design ni]
```

**Grupa reklam 2.2 — Redesign sklepu**
```
[ecommerce redesign belfast]
[shopify redesign northern ireland]
[improve online shop belfast]
[ecommerce agency northern ireland]
[magento developer belfast]
```

**Wykluczenia kampanii 2:**
```
-free shop
-etsy
-ebay
-amazon seller
-dropshipping course
-how to start
-tutorial
```

---

### Kampania 3 — SEO Belfast

**Grupa reklam 3.1 — SEO usługi**
```
[seo belfast]
[seo services belfast]
[seo agency belfast]
[local seo belfast]
[seo northern ireland]
[search engine optimisation belfast]
[google ranking belfast]
[get found on google belfast]
```

**Grupa reklam 3.2 — SEO dla konkretnych branż**
```
[seo for restaurants belfast]
[seo for builders belfast]
[seo for solicitors belfast]
[seo for dentists belfast]
[seo for estate agents belfast]
[seo for accountants northern ireland]
```

**Wykluczenia kampanii 3:**
```
-seo course
-learn seo
-seo tool
-seo software
-seo jobs
-seo salary
-free seo
```

---

### Kampania 4 — Google Ads & Digital Marketing Belfast

**Grupa reklam 4.1 — Google Ads management**
```
[google ads management belfast]
[ppc agency belfast]
[google ads agency northern ireland]
[ppc management northern ireland]
[google ads consultant belfast]
[adwords management belfast]
```

**Grupa reklam 4.2 — Meta / Social Ads**
```
[facebook ads belfast]
[meta ads agency belfast]
[social media advertising belfast]
[facebook advertising northern ireland]
[instagram ads belfast]
```

**Grupa reklam 4.3 — Digital marketing ogólne**
```
[digital marketing belfast]
[digital marketing agency belfast]
[online marketing belfast]
[digital agency northern ireland]
[marketing agency belfast]
```

**Wykluczenia kampanii 4:**
```
-free google ads
-google ads course
-learn ppc
-tutorial
-jobs
-internship
-salary
```

---

### Kampania 5 — Branżowe / Niche (Long-tail, niskie CPC)

**Branże o wysokim LTV klienta w NI:**
```
[solicitor website design belfast]
[accountant website belfast]
[dental website design northern ireland]
[healthcare website design ni]
[restaurant website belfast]
[hotel website design northern ireland]
[construction company website belfast]
[estate agent website northern ireland]
[beauty salon website belfast]
[gym website design northern ireland]
[garage website design belfast]
```

> **Uwaga:** Long-tail branżowe mają CPC £0.50–1.80 (vs £3–6 dla „web design belfast"). Konwertują lepiej bo intencja zakupowa jest wysoka i konkretna.

---

## 4. Struktura konta Google Ads — NI

```
Konto: Website Expert UK (website-expert.uk)
Waluta: GBP
Strefa czasowa: Europe/London
│
├── Kampania 1: [NI] Web Design Belfast
│   ├── Grupa reklam: Web Design Belfast (brand/city)
│   ├── Grupa reklam: Web Design Wider NI (Derry, Newry, Lisburn...)
│   └── Grupa reklam: New / Small Business Website
│
├── Kampania 2: [NI] E-Commerce Belfast
│   ├── Grupa reklam: Ecommerce Website Design
│   └── Grupa reklam: Shopify / WooCommerce / Redesign
│
├── Kampania 3: [NI] SEO Services Belfast
│   ├── Grupa reklam: SEO Belfast (general)
│   └── Grupa reklam: SEO by Industry (niche)
│
├── Kampania 4: [NI] Digital Marketing Belfast
│   ├── Grupa reklam: Google Ads Management
│   ├── Grupa reklam: Meta / Facebook Ads
│   └── Grupa reklam: Digital Agency (ogólne)
│
├── Kampania 5: [NI] Niche / Industry Verticals
│   └── Grupo reklam: Brand Vertical (1 per industry max)
│
└── Kampania 6: [NI] Remarketing RLSA
    ├── Homepage visitors (no conversion, 14 dni)
    ├── Calculator abandoners (7 dni)
    └── Contact page visitors (no submit, 7 dni)
```

---

## 5. Teksty reklam RSA — NI

> RSA = Responsive Search Ads | 15 nagłówków (max 30 znaków) + 4 opisy (max 90 znaków)  
> Zasada: minimum 3 nagłówki zawierające lokalizator `Belfast` lub `Northern Ireland`  
> Pinuj nagłówek 1 = główne słowo kluczowe; nagłówek 2 = lokalizacja; nagłówek 3 = USP

---

### 5.1 Kampania 1 — Web Design Belfast

**Nagłówki (15):**
```
1.  Web Design Belfast          [PIN 1 — exact match KW]
2.  Northern Ireland Web Agency [PIN 2 — lokalizacja]
3.  Fixed Price. No Surprises.  [PIN 3 — USP]
4.  Professional Website Design
5.  Websites from £{price:1500}
6.  Delivered in 2–6 Weeks
7.  Free Quote — Same Day
8.  10+ Years Experience
9.  Mobile-First & Fast Loading
10. Built for Lead Generation
11. SEO Included as Standard
12. Speak to a Real Developer
13. Small Business Specialists
14. Get More Customers Online
15. 200+ Projects Delivered
```

**Opisy (4):**
```
1. Belfast-based web design agency. Bespoke websites built to generate leads and rank in Google. Free quote in 24 hours.
2. We design and build fast, professional websites for Northern Ireland businesses. Fixed price, no hidden costs. Call us today.
3. From small business sites to full e-commerce platforms — delivered in 2–6 weeks. SEO and mobile optimisation included.
4. 10+ years building websites for NI companies. You work directly with the developer, not an account manager. Free consultation.
```

---

### 5.2 Kampania 2 — E-Commerce Belfast

**Nagłówki (15):**
```
1.  Ecommerce Website Belfast   [PIN 1]
2.  NI Online Shop Specialists  [PIN 2]
3.  Sell More Online — NI       [PIN 3]
4.  Shopify & WooCommerce Experts
5.  Ecommerce from £{price:2500}
6.  Optimised for Conversions
7.  Fast, Secure Online Stores
8.  Stripe & PayPal Integration
9.  Free Performance Audit
10. Launch in 4–8 Weeks
11. Mobile Commerce Ready
12. Inventory & CMS Included
13. Abandoned Cart & Analytics
14. Trusted by NI Retailers
15. Same-Day Quote Available
```

**Opisy (4):**
```
1. Ecommerce websites for NI businesses. Shopify, WooCommerce and custom builds — fast, secure and built to sell. Free quote today.
2. Turn your Belfast shop into an online business. We build conversion-optimised stores with payment integration, SEO and analytics.
3. Slow or outdated online store? We redesign and rebuild e-commerce sites for Northern Ireland retailers. Fixed price, fast delivery.
4. From product listings to checkout — we build ecommerce stores that work on every device. 200+ projects across the UK and NI.
```

---

### 5.3 Kampania 3 — SEO Belfast

**Nagłówki (15):**
```
1.  SEO Services Belfast        [PIN 1]
2.  Rank Higher in Google NI    [PIN 2]
3.  Free SEO Audit — Today      [PIN 3]
4.  Local SEO Belfast Experts
5.  First Page Google Results
6.  From £{price:400}/Month
7.  SEO + Content + Links
8.  Transparent Monthly Reports
9.  Technical & On-Page SEO
10. Google Business Profile
11. Beat Your NI Competitors
12. SEO for Small Businesses
13. Results in 3–6 Months
14. No Contract — Cancel Anytime
15. Get More Calls & Enquiries
```

**Opisy (4):**
```
1. SEO services for Belfast and Northern Ireland businesses. Get found on Google and drive more local customers. Free audit included.
2. We increase your Google rankings with technical SEO, content strategy and local optimisation. Monthly reporting. No long contracts.
3. Belfast SEO agency with 10+ years experience. From local cafés to professional services — we've ranked them all. Free consultation.
4. Stop losing customers to your competitors on Google. Our NI SEO team gets you to page one. Free SEO audit — results in first 60 days.
```

---

### 5.4 Kampania 4 — Google Ads Management Belfast

**Nagłówki (15):**
```
1.  Google Ads Management NI    [PIN 1]
2.  PPC Agency Belfast          [PIN 2]
3.  Stop Wasting Ad Budget      [PIN 3]
4.  Certified Google Ads Partner
5.  Meta & Google Ads Experts
6.  From £{price:350}/Month
7.  Free Ads Account Audit
8.  ROI-Focused Campaigns
9.  No Setup Fees — Start Now
10. Facebook & Instagram Ads
11. Call Tracking Included
12. Real-Time Dashboard Access
13. Leads, Not Just Clicks
14. NI & UK Businesses Welcome
15. 30-Day Free Trial Available
```

**Opisy (4):**
```
1. Google Ads and Meta Ads management for Northern Ireland businesses. Stop wasting budget — we optimise for real leads and sales.
2. Belfast PPC specialists. We manage Google Search, Shopping and Meta campaigns with full reporting and ROI tracking. Free audit.
3. Getting clicks but not conversions? We audit and fix your Google Ads campaigns. Real leads from real NI customers. No setup fee.
4. From £350/month — our Belfast ad managers handle your Google and Facebook campaigns so you can focus on running your business.
```

---

### 5.5 Kampania 5 — Reklamy branżowe (przykłady)

**Nagłówki: Restaurant Website Belfast**
```
1.  Restaurant Website Belfast  [PIN 1]
2.  Menus, Bookings & Delivery  [PIN 2]
3.  From £{price:1200} — Fast   [PIN 3]
4.  Online Menu & Table Booking
5.  Hospitality Web Experts NI
6.  OpenTable & Resy Integration
```
**Opis:**
```
We build restaurant websites for Belfast cafés, bars and restaurants. Online menu, table booking and delivery integration. Free quote.
```

---

**Nagłówki: Estate Agent Website Belfast**
```
1.  Estate Agent Web Design NI  [PIN 1]
2.  Property Search & Listings  [PIN 2]
3.  Rightmove / Zoopla Ready    [PIN 3]
4.  GDPR-Compliant Lead Forms
5.  Fast Load, Mobile-First
6.  From £{price:2000}
```
**Opis:**
```
Estate agent websites for Belfast and NI. Integrated property search, lead capture forms and Rightmove/Zoopla feeds. Free quote today.
```

---

## 6. Rozszerzenia reklam (Ad Extensions) — NI

### 6.1 Sitelinks (wszystkie kampanie)
| Tekst sitelinku | Opis 1 | Opis 2 | URL |
|-----------------|--------|--------|-----|
| Free Quote | No obligation — same day | Get your price in 24h | /contact |
| Our Services | Web, SEO, Ads & E-commerce | Everything your business needs online | /#services |
| Portfolio | See our latest Belfast projects | 200+ websites delivered | /#portfolio |
| Cost Calculator | Estimate your project price | Takes 2 minutes — no sign-up | /kalkulator |
| About Us | Belfast-based, 10+ years | Local team, global quality | /#about |
| SEO Services | Get to page 1 on Google | Free audit included | /#services |

### 6.2 Callouts (wyróżniki)
```
✓ Belfast-Based Team
✓ Fixed Price — No Hidden Costs
✓ Free Quote in 24 Hours
✓ SEO Included as Standard
✓ Mobile-First Design
✓ 10+ Years Experience
✓ 200+ Websites Delivered
✓ No Contract Tie-In
✓ GDPR Compliant
✓ UK-Based Support
```

### 6.3 Structured Snippets
```
Headline: Services
Values: Web Design, E-Commerce, SEO, Google Ads, Meta Ads, Website Audit, Hosting, Content

Headline: Brands / Platforms
Values: Shopify, WooCommerce, Laravel, React, WordPress, Figma, Google Analytics, GTM
```

### 6.4 Call Extension
```
Phone: [numer telefonu — do uzupełnienia]
Business name: Website Expert
Call reporting: enabled
Schedule: Mon–Fri 9:00–18:00, Sat 10:00–14:00
```

### 6.5 Location Extension
```
Google Business Profile: [założyć i zweryfikować dla Belfast]
Address: [po zakupie biura / virtualne biuro w Belfast]
```

> **WAŻNE:** Location Extension znacząco podnosi CTR w lokalnych kampaniach (+10–20%). Priorytet: założyć Google Business Profile z adresem Belfast natychmiast po uruchomieniu kampanii.

### 6.6 Image Extensions
```
Zalecane: zrzuty ekranu projektów portfolio (3–5 zdjęć)
Format: 1200×628px, jpg/png
Tekst alt: "Web design Belfast — [nazwa projektu]"
```

### 6.7 Price Extension
| Usługa | Od | URL |
|--------|----|-----|
| Business Website | From £1,500 | /contact |
| E-Commerce Store | From £2,500 | /contact |
| SEO Monthly | From £400/mo | /contact |
| Google Ads Mgmt | From £350/mo | /contact |
| Website Audit | From £250 | /contact |

---

## 7. Strony docelowe — NI (Landing Pages)

### 7.1 Mapa URL i priorytety

| Kampania | Strona docelowa | Priorytet | Status |
|----------|----------------|-----------|--------|
| Web Design Belfast | `website-expert.uk/` (homepage) | 🔴 Must have | ⚠️ Wymaga optymalizacji NI |
| Kalkulator | `website-expert.uk/kalkulator` | 🟡 Use it | ⚠️ Meta w PL — naprawić |
| Kontakt / wycena | `website-expert.uk/#contact` | 🔴 Must have | ✅ Formularz działa |
| SEO Belfast | `website-expert.uk/#services` | 🟡 OK na start | ➕ Docelowo osobna podstrona `/seo-belfast` |
| E-Commerce | `website-expert.uk/#services` | 🟡 OK na start | ➕ Docelowo osobna podstrona `/ecommerce-belfast` |
| Google Ads Mgmt | `website-expert.uk/#services` | 🟡 OK na start | ➕ Docelowo osobna podstrona `/google-ads-belfast` |

### 7.2 Wymagania homepage jako landing page NI

**Elementy krytyczne dla Quality Score i konwersji:**

- [ ] **H1 z lokalizacją:** `"Professional Web Design in Belfast, Northern Ireland"` (zamiast obecnego ogólnego)
- [ ] **Podtytuł:** `"Bespoke websites, SEO and digital marketing for NI businesses — fixed price, delivered in weeks."`
- [ ] **CTA above the fold:** "Get a Free Quote" → anchor do formularza kontaktowego (już istnieje)
- [ ] **Trust signals widoczne bez scrollowania:**
  - `10+ Years Experience` | `200+ Projects` | `Belfast-Based Team` | `Free Quote in 24h`
- [ ] **Sekcja About:** zmienić `"Based in Manchester"` → `"Based in Belfast, Northern Ireland"` *(obecny tekst w `About.jsx` mówi Manchester — **konieczna zmiana**)*
- [ ] **Formularz kontaktowy** widoczny i dostępny (istniejący `Contact` komponent — ✅)
- [ ] **Kalkulator wyceny** — istniejący `CostCalculatorV2` — zmienić meta title na EN
- [ ] **Portfolio** — 3 projekty (seeder istnieje, potrzebne prawdziwe screenshoty)
- [ ] **Testimonials** — 5–6 opinii (komponent istnieje) — warto dodać lokalizację klienta: "John Smith, Belfast"

### 7.3 Podstrony do stworzenia (priorytet po uruchomieniu kampanii)

Każda podstrona = osobna grupa reklam = lepszy Quality Score = niższy CPC.

```
website-expert.uk/web-design-belfast
website-expert.uk/ecommerce-belfast
website-expert.uk/seo-belfast
website-expert.uk/google-ads-belfast
website-expert.uk/website-audit
```

Każda musi zawierać:
- H1 z słowem kluczowym i lokalizacją
- Opis usługi (min. 300 słów)
- Cennik (widełki lub "from £X")
- CTA → formularz lub telefon
- Sekcja FAQ (schema FAQ — wpływa na jakość oceny strony przez Google)
- Testimonial z klientem NI (jeśli dostępny)

---

## 8. Targeting i ustawienia techniczne

### 8.1 Targeting geograficzny

| Ustawienie | Wartość |
|-----------|---------|
| **Kraj** | United Kingdom |
| **Region** | Northern Ireland |
| **Miasta (zwiększony bid +20%)** | Belfast, Derry/Londonderry, Lisburn, Newry, Antrim, Ballymena, Newtownabbey |
| **Metoda targetowania** | `People in or regularly in this location` |
| **Wykluczenie** | `Republic of Ireland` (Dublin, Cork, Galway — nie NI) |
| **Bid adjustment — mobile** | +25% (wyszukiwania lokalne dominująco mobile) |
| **Bid adjustment — desktop** | baseline |

### 8.2 Harmonogram reklam
```
Poniedziałek – Piątek:  08:00 – 19:00  (peak godziny pracy)
Sobota:                 09:00 – 15:00  (właściciele firm aktywni rano)
Niedziela:              10:00 – 13:00  (redukowane, niższy ruch B2B)

Bid adjustments:
- Wt–Czw 09:00–12:00: +15% (peak decyzyjny)
- Pon 08:00–09:00: +10% (planowanie tygodnia)
- Sob rano: +0% (bez zmian)
- Wieczory po 20:00: -50%
```

### 8.3 Budżety startowe — NI

> Rynek NI jest mniejszy niż Londyn — CPC niższe o ~35%. Budżety startowe odpowiednio mniejsze.

| Kampania | Dzienny budżet | Szacowany CPC | Szacowana liczba kliknięć/dzień |
|----------|---------------|--------------|-------------------------------|
| Web Design Belfast | £15/dzień | £1.80–3.50 | 5–8 kliknięć |
| E-Commerce Belfast | £10/dzień | £1.50–2.80 | 4–7 kliknięć |
| SEO Belfast | £10/dzień | £1.20–2.50 | 4–8 kliknięć |
| Digital Marketing Belfast | £10/dzień | £1.00–2.00 | 5–10 kliknięć |
| Niche / Branżowe | £8/dzień | £0.50–1.80 | 5–15 kliknięć |
| **RAZEM** | **£53/dzień** | | ~23–48 kliknięć/dzień |
| **Miesięcznie** | **~£1 600/mies.** | | ~700–1 400 kliknięć |

> **Uwaga:** Przy CTR formularza 3–5% → **21–70 leadów/mies.** przy budżecie £1 600/mies.  
> CAC (klient — nie lead) przy konwersji lead→klient 20% → 4–14 nowych klientów/mies.  
> Break-even: 1 projekt web design (min. £1 500) = zwrot z 1 miesiąca kampanii.

### 8.4 Strategia stawek

| Etap | Czas | Strategia |
|------|------|-----------|
| Faza nauki | Tydzień 1–3 | **Manual CPC + Enhanced CPC** — zbieranie danych, kontrola kosztów |
| Optymalizacja | Tydzień 4–8 | **Maximize Clicks** z max CPC cap — budowanie historii Quality Score |
| Skalowanie | Mies. 3+ | **Target CPA** gdy ≥30 konwersji/mies.; Target: £30–50/lead |

---

## 9. Śledzenie konwersji — NI

### 9.1 Konwersje do konfiguracji w GTM + Google Ads

Projekt ma GTM zainstalowany (`app.blade.php`) i `dataLayer.js` z istniejącymi zdarzeniami.

| Konwersja | Priorytet | Zdarzenie GTM | Wartość |
|-----------|-----------|--------------|---------|
| Wypełnienie formularza kontaktowego | 🔴 Główna | `contact_form_submit` | £150 (estymacja average lead value) |
| Wysłanie kalkulatora | 🔴 Główna | `calculator_lead` | £100 |
| Kliknięcie telefonu (call extension) | 🔴 Główna | `phone_call_click` | £80 |
| Kliknięcie e-mail | 🟡 Pomocnicza | `email_click` | — |
| Czas na stronie > 3 min | 🟢 Micro | `engaged_session` | — |
| Scroll > 75% strony | 🟢 Micro | `scroll_depth_75` | — |

### 9.2 Kod GTM do dodania (zdalny od istniejącego dataLayer)

```javascript
// Kliknięcie numeru telefonu (do dodania w GTM — trigger: Click URL contains "tel:")
dataLayer.push({
    event: 'phone_call_click',
    event_category: 'Contact',
    event_label: 'Header phone number'
});

// Kliknięcie email (trigger: Click URL contains "mailto:")
dataLayer.push({
    event: 'email_click',
    event_category: 'Contact',
    event_label: 'Footer email'
});

// Scroll depth 75% (GTM wbudowany trigger: Scroll Depth ≥ 75%)
dataLayer.push({
    event: 'scroll_depth_75',
    page_path: window.location.pathname
});
```

### 9.3 Import konwersji do Google Ads
1. Google Ads Admin → Tools → Conversions → New Conversion
2. Źródło: Google Analytics 4
3. Import zdarzeń: `contact_form_submit`, `calculator_lead`, `phone_call_click`
4. Attribution model: **Data-driven** (po zebraniu danych) lub **Last click** na start

---

## 10. Remarketing RLSA — NI

### 10.1 Listy remarketingowe

| Nazwa listy | Warunek GA4 | Czas | Zastosowanie |
|-------------|------------|------|-------------|
| `ni_homepage_no_convert` | Odwiedzili `website-expert.uk`, brak `contact_form_submit` | 14 dni | +30% bid na kampanie NI |
| `ni_calculator_no_submit` | Odwiedzili `/kalkulator`, brak `calculator_lead` | 7 dni | +40% bid — bardzo wysokia intencja |
| `ni_contact_page_no_submit` | Odwiedzili `/#contact`, brak submit | 7 dni | +50% bid |
| `ni_portfolio_visitors` | Scroll >75% na stronie głównej (sekcja portfolio) | 30 dni | +20% bid — research phase |

### 10.2 Teksty reklam remarketingowych NI

**Lista: `ni_calculator_no_submit` (porzucili kalkulator):**
```
Nagłówek 1: You Started a Quote — Finish It
Nagłówek 2: Your Website Price is Ready
Nagłówek 3: Free Quote — No Obligation
Opis: You used our cost calculator. Get your full quote in 24 hours — no obligation, no sign-up required.
```

**Lista: `ni_homepage_no_convert` (odwiedzili, nie zadzwonili):**
```
Nagłówek 1: Still Need a Website in Belfast?
Nagłówek 2: Free Consultation This Week
Nagłówek 3: Local Team. Fixed Price.
Opis: We noticed you visited Website Expert. Book a free 20-minute consultation — no pressure, just expert advice for NI businesses.
```

---

## 11. Google Business Profile — Northern Ireland

> **Priorytet: KRYTYCZNY** — GBP jest darmowym źródłem leadów i wpływa na Ad Rank reklam lokalnych.

### Checklisty GBP:
- [ ] Założyć/zweryfikować konto GBP dla `Website Expert`
- [ ] Kategoria główna: `Web Designer` + dodatkowe: `SEO Agency`, `Marketing Agency`, `E-commerce Service`
- [ ] Adres: Belfast, Northern Ireland (BT postcode) — nawet wirtualne biuro wystarczy
- [ ] Telefon: +44 (lokalny NI numer — sugestia: numer 028...)
- [ ] Godziny otwarcia: Pon–Pt 9–18, Sob 10–14
- [ ] Opis (750 znaków): zawrzeć `Belfast`, `Northern Ireland`, kluczowe usługi, "free quote"
- [ ] Zdjęcia: logo, zrzuty ekranu projektów, "team at work"
- [ ] Pierwsze opinie: poprosić pierwszych 3–5 klientów o recenzje Google

### Szablon opisu GBP:
```
Website Expert is a Belfast-based web design and digital marketing agency serving businesses 
across Northern Ireland. We specialise in bespoke website design, e-commerce development, 
local SEO, Google Ads management and Meta advertising.

With 10+ years of experience and 200+ projects delivered, we help NI businesses grow online 
through fast, professional websites and data-driven digital marketing.

Services: Web Design | E-Commerce | SEO | Google Ads | Meta Ads | Website Audit | Hosting

Free quote in 24 hours — call us or use our online calculator at website-expert.uk
```

---

## 12. Harmonogram wdrożenia — NI

```
TYDZIEŃ 1 (pilne — przed kampanią):
□ Zmiana APP_NAME="Website Expert" w .env
□ Naprawa meta description strony Kalkulator (zmiana PL → EN)
□ Dodanie OG tags i canonical do Welcome.jsx
□ Zmiana "Based in Manchester" → "Based in Belfast, Northern Ireland" w About.jsx
□ Dodanie noindex na trasy auth/portal w app.blade.php
□ Założenie Google Business Profile Belfast
□ Weryfikacja Google Search Console dla website-expert.uk
□ Połączenie GSC z Google Ads

TYDZIEŃ 2:
□ Konfiguracja konta Google Ads (kampania 1: Web Design Belfast)
□ Wgranie słów kluczowych, tekstów RSA, rozszerzeń
□ Konfiguracja konwersji GTM (contact_form_submit, calculator_lead, phone_call_click)
□ Import konwersji do Google Ads z GA4
□ Uruchomienie kampanii 1 (Web Design Belfast) — only

TYDZIEŃ 3:
□ Analiza pierwszych danych: Quality Score, Impression Share, Search Terms Report
□ Dodanie słów kluczowych negatywnych na podstawie Search Terms
□ Uruchomienie kampanii 2 (E-Commerce Belfast)
□ Uruchomienie kampanii 3 (SEO Belfast)

TYDZIEŃ 4:
□ Uruchomienie kampanii 4 (Digital Marketing Belfast)
□ Uruchomienie kampanii 5 (Niche/Branżowe)
□ Konfiguracja list remarketingowych w GA4
□ Pierwszy raport 30-dniowy

MIESIĄC 2:
□ Uruchomienie kampanii 6 (Remarketing RLSA)
□ Optymalizacja QS — dostosowanie tekstów reklam do wyników
□ A/B test 2 wariantów RSA (rotacja równa przez 4 tygodnie)
□ Sprawdzenie Impression Share — czy tracisz na budżet czy na ranking?
□ Rozważenie podstron dedykowanych (/web-design-belfast, /seo-belfast)

MIESIĄC 3:
□ Przejście na Target CPA (gdy ≥ 30 konwersji/mies.)
□ Pełny raport wyników: ROAS, CAC, leady per kampania
□ Decyzja o skalowaniu budżetu lub realokacji
□ Stworzenie podstron usługowych dla najlepiej konwertujących kampanii
```

---

## 13. KPI — rynek NI

| Metryka | Benchmark branżowy (UK local) | Cel po 3 mies. — NI |
|---------|-------------------------------|---------------------|
| CTR (Search) | 3–6% | ≥5% |
| Quality Score (główne KW) | 5–7 | ≥7 |
| Avg. CPC — Web Design | £2–4 (NI) | ≤£2.50 |
| Avg. CPC — SEO | £1.50–3 (NI) | ≤£2.00 |
| Avg. CPC — Niche long-tail | £0.50–1.80 | ≤£1.20 |
| Impression Share (top 3) | — | ≥40% |
| Conversion Rate (click → lead) | 3–6% | ≥4% |
| CAC (koszt leadu) | — | ≤£45/lead |
| Leads/mies. (przy £1 600 budżecie) | — | ≥25 |
| Lead → Klient konwersja | — | ≥20% |
| Nowi klienci/mies. | — | ≥5 |
| ROAS (revenue / ad spend) | — | ≥500% (5:1) |

---

## 14. Podsumowanie priorytetów

### ✅ Do zrobienia PRZED uruchomieniem kampanii (bez tego — kampania straci efektywność):

| # | Akcja | Gdzie |
|---|-------|-------|
| 1 | Zmienić `APP_NAME` w `.env` | `.env` |
| 2 | Zmienić meta title/description na stronie głównej na NI-focused | `Welcome.jsx` |
| 3 | Dodać OG tags + canonical do `Welcome.jsx` | `Welcome.jsx` |
| 4 | Zmienić "Based in Manchester" → Belfast w `About.jsx` | `About.jsx` defaults |
| 5 | Naprawić meta strony Kalkulator (PL → EN) | `Kalkulator.jsx` |
| 6 | Dodać noindex na trasy auth/portal | `app.blade.php` |
| 7 | Założyć Google Business Profile — Belfast | Google Business |
| 8 | Zweryfikować GSC dla website-expert.uk | Search Console |
| 9 | Skonfigurować konwersje GTM (telefon, formularz, kalkulator) | GTM |

### 🚀 Do zrobienia PODCZAS kampanii (optymalnie tydzień 2–4):

| # | Akcja |
|---|-------|
| 10 | Uruchomić kampanie w kolejności: Web Design → E-Commerce → SEO → Ads → Niche |
| 11 | Tygodniowo: Search Terms Report → negatywne słowa kluczowe |
| 12 | Po 30 dniach: analiza Quality Score, dostosowanie landing pages |
| 13 | Po 60 dniach: podstrony dedykowane `/web-design-belfast` itp. |

---

*Plan opracowany na podstawie analizy kodu źródłowego projektu website-expert.uk.*  
*Aktualizacja: po 30 dniach od uruchomienia kampanii na podstawie danych rzeczywistych.*
