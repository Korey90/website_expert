# Raport treści strony pod kampanię NI

Data analizy: 2026-04-15  
Analizowana wersja: lokalny render `http://127.0.0.1:8000/` po przełączeniu na język `en`  
Punkt odniesienia: [docs/plan-kampanii-ni.md](./plan-kampanii-ni.md)

## Cel

Celem analizy było zebranie aktualnej treści strony głównej i ocena, czy obecny przekaz wspiera planowaną kampanię Google Search na rynek `Northern Ireland`, ze szczególnym naciskiem na:

- dopasowanie do intencji wyszukiwań lokalnych,
- spójność komunikatów reklamowych z landing page,
- siłę argumentów sprzedażowych i trust signals,
- gotowość strony do konwersji z ruchu płatnego.

## Źródła

Analiza została wykonana na podstawie:

- wyrenderowanej strony EN w headless Chrome,
- aktualnego DOM po wykonaniu JS,
- komponentów marketingowych w `resources/js/Components/Marketing/*`,
- planu kampanii w `docs/plan-kampanii-ni.md`.

## 1. Co obecnie komunikuje strona

### Hero / above the fold

Strona główna komunikuje bardzo jasno lokalny kontekst i główną ofertę:

- H1: `Professional Web Design in Belfast, Northern Ireland`
- podtytuł: `Bespoke websites, SEO and digital marketing for NI businesses — fixed price, delivered in weeks.`
- badge: `5-star rated on Google`
- CTA 1: `Get a Free Quote`
- CTA 2: `View Our Work`
- statystyki: `200+ Websites Delivered`, `98% Client Satisfaction`, `10+ Years Experience`
- dodatkowy sygnał szybkości: `Delivery time from 2 weeks`

To jest bardzo mocna warstwa wejściowa pod kampanię `Web Design Belfast`.

### About

Sekcja `About` jest już dostosowana do Belfast/NI i nie trzyma starego komunikatu o Manchesterze.

Aktualny przekaz:

- tytuł: `A Belfast Digital Agency That Treats You Like a Partner`
- podtytuł: `Based in Belfast, Northern Ireland. Working with businesses across the UK.`
- body podkreśla:
  - lokalny zespół z Belfastu,
  - bezpośredni kontakt z wykonawcami,
  - `Since 2014`,
  - `200+ projects`,
  - brak podejścia szablonowego.

To dobrze wzmacnia persony NI, które szukają lokalnego partnera i zaufania.

### Trust signals

Na stronie obecne są czytelne sygnały zaufania:

- `Trusted by Belfast & NI Businesses`
- `5.0 Google Reviews`
- `GDPR Compliant`
- `Belfast-Based Team`
- `PageSpeed 95+`

Dodatkowo jest sekcja testimoniali i lista marek/klientów:

- `Hargreaves Solicitors`
- `NTS Direct`
- `Oakfield Dental`
- `Pinnacle Recruitment`
- `Coastal Escapes`
- `Bloom & Grow`

Jest też cytowany testimonial z oceną 5/5.

### Oferta / usługi

Strona komunikuje szeroką ofertę agencyjną zgodną z planem kampanii:

- `Brochure Websites`
- `E-Commerce Stores`
- `Web Applications`
- `Google Ads (PPC)`
- `Meta / Pixel Ads`
- `Website Maintenance`

Ważne są też widoczne anchory cenowe:

- `Brochure Websites` od `£799`
- `E-Commerce Stores` od `£2,999`
- `Google Ads (PPC)` od `£399/mo`
- `Meta / Pixel Ads` od `£349/mo`
- `Website Maintenance` od `£149/mo`

To wspiera komunikację `fixed price`, `from £X`, `no surprises`, która w planie jest kluczowa dla rynku NI.

### CTA banner

Dodatkowa sekcja CTA wzmacnia intencję:

- tytuł: `Ready to Get Started?`
- obietnica: `Get a free, no-obligation quote in 48 hours.`
- CTA główne: `Get My Free Quote`
- CTA wtórne: `Book a Discovery Call`

### Portfolio

Sekcja portfolio buduje wiarygodność dla ruchu researchowego:

- tytuł: `Recent Work We're Proud Of`
- 3 case'y widoczne na stronie głównej,
- osobna podstrona `/portfolio`,
- przykłady branż zgodne z wartościowymi segmentami:
  - solicitors,
  - e-commerce B2B,
  - dental.

### FAQ

FAQ dobrze odpowiada na obawy przed zakupem:

- czas realizacji,
- koszt strony,
- praca zdalna,
- mobile-first,
- płatności etapowe,
- samodzielna edycja treści.

W FAQ pada też mocny pricing anchor:

- `Brochure websites start from £799. E-commerce from £2,999. We always provide a fixed-price quote — no surprises.`

### Kontakt

Sekcja kontaktowa zawiera:

- deklarację odpowiedzi: `we'll reply within 24 business hours`
- adres e-mail: `hello@website-expert.uk`
- telefon z prefiksem NI: `+44 28 0000 0000`
- pełny formularz kontaktowy,
- wybór typu projektu,
- zgoda GDPR,
- tracking leada po submit.

### Kalkulator

Kalkulator jest mocnym elementem do ruchu wysokiej intencji:

- tytuł: `How Much Will Your Project Cost?`
- obietnica: `No registration required`
- wieloetapowy flow z widełkami cenowymi,
- lead capture po estymacji,
- success message: `We'll get back to you within 1 business day.`

To bardzo dobrze wspiera kampanie skierowane na `free quote`, `website cost`, `affordable website`, remarketing oraz użytkowników porównujących oferty.

## 2. Ocena dopasowania do planu kampanii

### Kampania 1 — Web Design Belfast

Ocena: `wysokie dopasowanie`

Strona bardzo dobrze wspiera tę kampanię, bo ma:

- lokalizację w H1,
- ofertę web design jako główny motyw hero,
- pricing anchor,
- social proof,
- Belfast/NI w komunikacji,
- formularz + kalkulator.

To jest dziś najmocniej dopasowany obszar strony do planu kampanii.

### Kampania 2 — E-Commerce Belfast

Ocena: `średnie do wysokiego dopasowania`

Plusy:

- e-commerce jest widoczną usługą,
- jest widełka cenowa,
- portfolio zawiera projekt B2B e-commerce,
- kalkulator wspiera wycenę projektu.

Minus:

- brak osobnej, mocno dopasowanej sekcji/landing page pod e-commerce Belfast,
- komunikat hero nadal jest głównie o web design, nie o sklepie online.

### Kampania 3 — SEO Belfast

Ocena: `średnie dopasowanie`

Plusy:

- SEO jest obecne w hero i usługach,
- FAQ i About wzmacniają kompetencje,
- strona ma lokalny kontekst Belfast/NI.

Minusy:

- brak osobnej sekcji z mocnym rozwinięciem oferty SEO,
- na stronie nie jest mocno eksponowana obietnica `free SEO audit`, która pojawia się w planie reklam,
- brak dedykowanego landing page typu `/seo-belfast`.

### Kampania 4 — Google Ads / Digital Marketing Belfast

Ocena: `średnie dopasowanie`

Plusy:

- `Google Ads (PPC)` i `Meta / Pixel Ads` są widoczne,
- są ceny `from`,
- w hero pada `digital marketing`.

Minusy:

- brak silnej sekcji z case'em / procesem / audytem pod PPC,
- brak wyraźnego komunikatu typu `free ads audit`,
- dla tej kampanii homepage jest raczej stroną ogólną niż precyzyjnym landingiem.

## 3. Mocne strony strony pod reklamę

### 3.1 Lokalizacja i message match

Największa poprawa względem wcześniejszych założeń planu jest taka, że strona już realnie mówi językiem kampanii NI:

- `Belfast`
- `Northern Ireland`
- `NI businesses`
- `Belfast-based team`

To bezpośrednio wspiera Quality Score i CTR dla kampanii lokalnych.

### 3.2 Dobre USP dla rynku NI

Strona dobrze eksponuje argumenty ważne dla lokalnych SME:

- fixed price,
- szybka realizacja,
- bezpośredni kontakt z zespołem,
- doświadczenie,
- mobile-first,
- GDPR compliance,
- pricing anchors.

### 3.3 Dużo ścieżek konwersji

Użytkownik z reklamy może:

- przejść do formularza,
- wypełnić kalkulator,
- kliknąć telefon,
- kliknąć e-mail,
- przejrzeć portfolio,
- umówić discovery call.

To jest dobre pod różne poziomy gotowości zakupowej.

### 3.4 Kalkulator jako przewaga

Kalkulator jest realnym wyróżnikiem na tle wielu małych agencji i freelancerów. Dla person cenowo wrażliwych z NI może bardzo dobrze działać jako soft-conversion i źródło remarketingu.

## 4. Ryzyka i luki

### 4.1 Niespójne obietnice czasu odpowiedzi

Na stronie występują równolegle trzy różne obietnice:

- meta / plan komunikacyjny: `Free quote in 24 hours`
- kontakt: `reply within 24 business hours`
- CTA banner: `quote in 48 hours`
- kalkulator po sukcesie: `within 1 business day`

To osłabia wiarygodność reklam i rozmywa główne USP. W kampanii Search warto mieć jedną spójną obietnicę.

### 4.2 Problem z CTA do kalkulatora

W aktualnej treści CTA kierują do `#calculator`, a sekcja kalkulatora ma `id="calculate"`.

To oznacza ryzyko, że:

- przycisk `Get a Free Quote` nie scrolluje tam, gdzie powinien,
- baner `Get My Free Quote` też może prowadzić do nieistniejącego anchora,
- ruch z reklam może trafiać na stronę z niesprawnym lub mylącym CTA.

To jest wysoki priorytet, bo dotyczy głównej ścieżki konwersji.

### 4.3 CTA w hero nie jest idealnie spójne z planem

Plan zakładał:

- `Get a Free Quote` jako anchor do formularza kontaktowego.

Obecnie CTA kieruje do kalkulatora, a nie do formularza. Sam kalkulator jest wartościowy, ale:

- dla części ruchu high-intent z Google Search formularz może być szybszą ścieżką,
- komunikat `Free Quote` sugeruje kontakt z człowiekiem, a nie przejście przez estymator.

To raczej temat do testu A/B niż do natychmiastowego usunięcia kalkulatora.

### 4.4 Lokalny social proof jest jeszcze zbyt mało konkretny

Strona deklaruje:

- `Trusted by Belfast & NI Businesses`
- `Belfast-Based Team`

ale dowody są jeszcze zbyt mało lokalne wprost:

- testimoniale nie pokazują lokalizacji klienta w stylu `John Smith, Belfast`,
- portfolio nie komunikuje jasno `Belfast project` / `NI project`,
- marki klientów nie zawsze brzmią jednoznacznie lokalnie dla odbiorcy z reklamy.

Plan kampanii słusznie zakładał, że lokalne zaufanie będzie bardzo ważne.

### 4.5 Above the fold nie pokazuje wszystkich trust signals z planu

Above the fold widzimy:

- `200+`
- `98%`
- `10+`

ale plan zakładał też widoczne bez scrolla:

- `Belfast-Based Team`
- `Free Quote in 24h`

Te elementy są niżej lub w innych sekcjach, więc na pierwszym ekranie message match mógłby być jeszcze mocniejszy.

### 4.6 Część formularza nadal ma polskie artefakty

W wersji EN w renderze nadal widać:

- placeholder email: `jan@firma.pl`
- etykieta: `VAT / NIP`
- placeholder pola VAT: `000-000-00-00`

To są drobne elementy, ale dla ruchu z NI obniżają lokalną wiarygodność i mogą niepotrzebnie wprowadzać tarcie.

### 4.7 Rozjazd z obietnicą reklam SEO i PPC

W planie reklam dla SEO i Google Ads pojawia się mocno:

- `Free audit`
- `Free SEO audit`
- `Free Ads audit`

Na stronie głównej taka oferta nie jest jasno eksponowana. W efekcie:

- reklama może obiecać coś, czego landing page nie rozwija,
- spada spójność `ad -> landing`.

### 4.8 Homepage nadal jest szeroką stroną agencyjną

To działa na start, ale dla kampanii:

- `SEO Belfast`
- `E-Commerce Belfast`
- `Google Ads Belfast`

homepage będzie zawsze mniej dopasowana niż osobne landing pages. Plan kampanii dobrze to przewiduje i nadal warto to traktować jako kolejny etap.

## 5. Rekomendacje priorytetowe

### Priorytet 1

1. Naprawić anchory CTA:
   - ujednolicić `#calculate` vs `#calculator`
   - sprawdzić hero, CTA banner i wszystkie przyciski typu `Free Quote`

2. Ujednolicić obietnicę czasową w całym serwisie:
   - wybrać jedną wersję: `24 hours`, `24 business hours` albo `1 business day`
   - zastosować ją w hero, CTA bannerze, kontakcie, kalkulatorze i reklamach

3. Oczyścić formularz z polskich artefaktów:
   - zmienić `jan@firma.pl`
   - dopasować `VAT / NIP`
   - usunąć placeholder w polskim formacie

4. Wzmocnić lokalny proof:
   - dodać lokalizacje klientów do testimoniali,
   - oznaczyć case studies jako `Belfast`, `NI`, `UK`,
   - jeśli są dostępne lokalne realizacje, pokazać je wyżej.

### Priorytet 2

5. Wystawić `Free Quote in 24h` i `Belfast-Based Team` jeszcze mocniej above the fold.

6. Dodać sekcję lub micro-CTA pod:
   - `Free SEO Audit`
   - `Free Google Ads Audit`

7. Przetestować dwa warianty głównego CTA z reklam Search:
   - wariant A: `Get a Free Quote` -> `#contact`
   - wariant B: `Get a Free Quote` -> `#calculate`

### Priorytet 3

8. Budować dedykowane landing pages:
   - `/seo-belfast`
   - `/ecommerce-belfast`
   - `/google-ads-belfast`

9. Rozwinąć portfolio o bardziej lokalne case studies i screenshoty pod image extensions / sitelinks.

## 6. Wniosek końcowy

Aktualna strona główna jest już mocną bazą pod kampanię `Web Design Belfast` i znacznie lepiej wspiera rynek NI niż sugerowały starsze założenia planu. Najważniejsze elementy są na miejscu:

- lokalizacja w H1,
- Belfast/NI w treści,
- oferta zgodna z kampaniami,
- pricing anchors,
- trust signals,
- portfolio,
- FAQ,
- formularz,
- kalkulator.

Największe ryzyka nie dotyczą dziś braku treści, tylko spójności i detali konwersyjnych:

- niespójny czas odpowiedzi,
- błędny lub niejednoznaczny anchor CTA,
- zbyt mało twardego lokalnego proof,
- słabszy message match dla kampanii SEO i PPC,
- drobne polskie artefakty w formularzu.

W praktyce oznacza to, że homepage nadaje się do startu kampanii, ale przed skalowaniem budżetu warto dopracować powyższe elementy, bo właśnie one będą wpływać na CTR, Quality Score i conversion rate bardziej niż sama obecność treści.
