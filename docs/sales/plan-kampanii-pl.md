# Plan kampanii Google Search — Polscy przedsiębiorcy w UK
> Data: 2026-09-20 (wymagania Google Ads zweryfikowane tego dnia)
> Landing page: https://website-expert.uk/pl/strony-internetowe-uk
> Rynek: Polscy właściciele firm w całej Wielkiej Brytanii — język polski
> Model: Strony internetowe dla polskich firm w UK — pakiet od £799
> Bazuje na: treści `resources/js/Pages/Landing/PlStronyInternetowe.tsx`, konwencji `docs/sales/plan-kampanii-ni.md`, audycie `docs/reports/seo-and-performence-raport.md` (P0/P1 wdrożone 2026-09-17)

---

## Status landing page przed startem kampanii

Ten endpoint przeszedł audyt SEO i dwie rundy poprawek (P0 + P1) — poniżej stan faktyczny, zweryfikowany w kodzie.

| Element | Stan | Szczegóły |
|---------|------|-----------|
| `<title>` | ✅ | „Strony internetowe UK dla polskich firm” (bez duplikatu brandingu) |
| `<meta name="description">` | ✅ | Zoptymalizowana wersja z audytu, zawiera cenę i USP |
| Canonical | ✅ | Server-side, per-route, bez query stringów |
| Hreflang | ⚠️ celowo brak | Usunięty względem EN LP — różne rynki (cała UK vs Irlandia Płn.), nic do zrobienia |
| JSON-LD | ✅ | `Service` + `FAQPage` + `BreadcrumbList` |
| Open Graph / Twitter | ✅ | Pełny zestaw gotowy, w tym `og:image`/`twitter:image` (`public/images/og/strony-internetowe-uk-pl.png`, 1200×630) |
| Formularz kontaktowy | ✅ | 6 pól + zgoda RODO, throttle 5/60s, e-mail potwierdzający PL |
| `tel:` w sticky CTA | ✅ | `+447882799050` |
| Tracking zdarzeń (dataLayer) | ✅ | `landing_page_view`, `form_start`, `lead_created`, `primary_cta_click`, `phone_click` — gotowe do importu jako konwersje |
| GTM + Consent Mode v2 | ✅ | Odroczony do `load`, `analytics_storage`/`ad_storage` domyślnie `denied` |
| reCAPTCHA v3 | ✅ | Na formularzu publicznym |
| SSR / prerendering | ❌ | Brak (P2, świadomie odłożone) — nie blokuje startu kampanii, ale ogranicza organiczny SEO równolegle do płatnego ruchu |

**Wniosek:** strona jest gotowa do testu płatnego ruchu po skonfigurowaniu i przetestowaniu konwersji w GTM/GA4/Google Ads. Brak grafiki `og:image` nie blokuje kampanii Search.

---

## 1. Grupa docelowa

Landing page kieruje się do **polskich (i szerzej: polskojęzycznych) właścicieli firm działających na terenie całej Wielkiej Brytanii** — nie tylko Irlandii Północnej (to osobna kampania EN, patrz `plan-kampanii-ni.md`). Formularz ma pole `audience_type` z opcjami: `english_clients` / `polish_clients` / `both` / `unknown` — czyli klient końcowy właściciela strony bywa różny, ale **decydent (nasz klient) zawsze mówi po polsku**.

### Segmenty branżowe (z sekcji „Dla kogo” na LP)
```
Firmy budowlane i remontowe
Hydraulicy i elektrycy
Gabinety kosmetyczne
Kliniki i dentyści
Firmy sprzątające
Firmy transportowe
Mechanicy samochodowi
Instruktorzy i szkolenia
Restauracje i catering
Księgowi i doradcy
```

### Persona główna — „Polski przedsiębiorca w UK”
- **Kim jest:** prowadzi jednoosobową działalność lub małą firmę (budowlanka, beauty, transport, gastronomia, usługi) na terenie Anglii, Szkocji, Walii lub Irlandii Płn.
- **Problem:** nie ma strony albo ma coś zrobionego „po znajomości” — nie wygląda profesjonalnie dla brytyjskich klientów, nie generuje zapytań
- **Bariera:** niepewność językowa przy rozmowie z angielskojęzyczną agencją, obawa przed ukrytymi kosztami
- **Czego szuka:** kogoś, kto **mówi po polsku**, poda **jasną cenę z góry** i zrozumie specyfikę jego branży
- **Wrażliwość cenowa:** średnia-wysoka — porównuje oferty, ale docenia brak niespodzianek cenowych bardziej niż najniższą cenę

---

## 2. Struktura kampanii

```
Konto: Website Expert UK (website-expert.uk)
Waluta: GBP
Strefa czasowa: Europe/London
│
└── Kampania: [PL] Strony Internetowe UK — Polskie Firmy
    ├── Grupa reklam 1: Strony Internetowe UK (ogólna intencja)
    ├── Grupa reklam 2: Polska Obsługa / Polski Developer (język jako USP)
    ├── Grupa reklam 3: Branżowe long-tail (10 branż z LP)
    └── Grupa reklam 4: Cena / koszt (intencja porównawcza)
```

Jedna kampania, jeden landing page — celowo. Rozszerzenie do osobnych podstron per branża (np. `/pl/strony-internetowe-budowlanka-uk`) ma sens dopiero po zebraniu danych z Search Terms Report i potwierdzeniu, które branże faktycznie konwertują.

---

## 3. Słowa kluczowe

### Grupa reklam 1 — Strony Internetowe UK
```
[strony internetowe uk]
[strona internetowa dla firmy uk]
"strony internetowe wielka brytania"
[tworzenie stron internetowych uk]
[projektowanie stron internetowych uk]
"strona internetowa dla polskiej firmy"
[strona www dla firmy uk]
"strony internetowe dla polskich firm w uk"
```

### Grupa reklam 2 — Polska Obsługa / Polski Developer
```
[polski web developer uk]
[polski programista stron uk]
"strona internetowa po polsku uk"
[polska firma tworzenie stron www]
"obsługa po polsku strona internetowa"
[web developer mówiący po polsku]
```

### Grupa reklam 3 — Branżowe (long-tail, hipoteza wysokiej intencji)
```
[strona internetowa dla firmy budowlanej uk]
[strona internetowa dla hydraulika uk]
[strona internetowa dla elektryka uk]
[strona internetowa dla gabinetu kosmetycznego uk]
[strona internetowa dla kliniki dentystycznej uk]
[strona internetowa dla firmy sprzątającej uk]
[strona internetowa dla firmy transportowej uk]
[strona internetowa dla warsztatu samochodowego uk]
[strona internetowa dla restauracji uk]
[strona internetowa dla księgowego uk]
```
> To lista startowa, a nie dane o wolumenie. Przed uruchomieniem sprawdzić każdą frazę w Keyword Plannerze dla lokalizacji United Kingdom i języka polskiego. Frazy bez wolumenu wstrzymać, a nowe warianty dodawać na podstawie raportu wyszukiwanych haseł.

### Grupa reklam 4 — Cena / koszt
```
[ile kosztuje strona internetowa uk]
"ile kosztuje strona internetowa w uk"
[cena strony internetowej uk]
"strona internetowa cena wielka brytania"
[strona internetowa od 799 uk]
```

### Wykluczenia startowe (lista na poziomie kampanii)
```
"praca"
"oferty pracy"
"zatrudnię"
"jak zrobić stronę"
"kurs tworzenia stron"
"szkolenie web developer"
"darmowy kreator stron"
"za darmo"
"wix"
"squarespace"
"wordpress kurs"
"template"
"freelancer.pl"
"praca zdalna"
```
> Wykluczenia zapisano jako dopasowanie do wyrażenia. Nie dodawać ogólnego wykluczenia „Polska” lub „w Polsce” bez sprawdzenia raportu wyszukiwanych haseł — mogłoby odciąć wartościowe zapytania polskich przedsiębiorców porównujących wykonawców.

---

## 4. Teksty reklam RSA

> RSA = Responsive Search Ads | do 15 nagłówków (max 30 znaków) + do 4 opisów (max 90 znaków). Liczby w nawiasach to liczba znaków. Teksty są zgodne z ofertą widoczną na LP.

**Nagłówki (15):**
```
1.  Strony Internetowe UK (21)
2.  Strona Firmowa w UK (19)
3.  Dla Polskich Firm w UK (22)
4.  Obsługa po Polsku (17)
5.  Ceny od £799 (12)
6.  Realizacja 3–5 Tygodni (22)
7.  Bez Ukrytych Kosztów (20)
8.  Rozmawiasz z Deweloperem (24)
9.  Bezpłatna Wycena (16)
10. Projekt Pod Rynek UK (20)
11. SEO Techniczne w Cenie (22)
12. CMS do Edycji Treści (20)
13. 10+ Lat Doświadczenia (21)
14. Dopasowana do Branży (20)
15. Zdobądź Klientów w UK (21)
```

**Opisy (4):**
```
1. Strony dla polskich firm w UK od £799. Obsługa po polsku i projekt pod rynek UK. (80)
2. Omówimy projekt po polsku. Wycena bez zobowiązań, odpowiedź w 1 dzień roboczy. (78)
3. Do 5 podstron, CMS, formularz i podstawowe SEO techniczne. Jasny zakres i cena. (79)
4. Rozmawiasz bezpośrednio z deweloperem. Typowa realizacja zajmuje 3–5 tygodni. (77)
```

**Dopasowanie RSA do grup reklam:**

| Grupa | Nagłówki, które powinny znaleźć się w pierwszej piątce assetów |
|---|---|
| Strony Internetowe UK | Strony Internetowe UK; Strona Firmowa w UK; Dla Polskich Firm w UK |
| Polska Obsługa | Obsługa po Polsku; Rozmawiasz z Deweloperem; Projekt Pod Rynek UK |
| Branżowe | Dopasowana do Branży; Dla Polskich Firm w UK; Zdobądź Klientów w UK |
| Cena / koszt | Ceny od £799; Bez Ukrytych Kosztów; Bezpłatna Wycena |

**Pinning:** na starcie nie przypinać wszystkich pozycji 1–3. RSA powinien swobodnie testować kombinacje. Jeżeli test wymaga stałej głównej frazy, przypiąć tylko 1–2 pasujące nagłówki do pozycji H1; pozostałe pozostawić bez przypięcia.

**URL wyświetlany:** `website-expert.uk/strony/dla-firm` (oba pola ścieżki mieszczą się w limicie 15 znaków).

---

## 5. Komponenty reklam (Assets)

### 5.1 Sitelinks
> LP jest jednostronicowa (single-page scroll) — sitelinki kierują do sekcji tej samej strony (`#portfolio`, `#contact-form`) oraz do powiązanych stron ogólnych.

| Tekst sitelinku | Opis 1 | Opis 2 | URL |
|-----------------|--------|--------|-----|
| Zobacz Realizacje | Przykłady wdrożonych projektów | Zobacz, jak wygląda gotowa strona | /pl/strony-internetowe-uk#portfolio |
| Bezpłatna Wycena | Odpowiedź w 1 dzień roboczy | Bez zobowiązań, po polsku | /pl/strony-internetowe-uk#contact-form |
| Kalkulator Kosztów | Oszacuj koszt swojego projektu | Wstępna wycena online | /calculate |
| Wszystkie Realizacje | Pełne portfolio projektów | Różne branże i rozwiązania | /portfolio |

### 5.2 Callouts
```
✓ Obsługa po Polsku
✓ Ceny od £799
✓ Bez Ukrytych Kosztów
✓ Realizacja 3–5 Tygodni
✓ Rozmowa z Deweloperem
✓ CMS w Cenie
✓ SEO Techniczne Wliczone
✓ 10+ Lat Doświadczenia
✓ Odpowiedź w 1 Dzień
✓ Dla Firm w Całym UK
```

### 5.3 Structured Snippets
```
Nagłówek: Usługi
Wartości: Projekt strony, SEO techniczne, Panel CMS, Formularz kontaktowy, Analityka, Szkolenie z CMS
```

### 5.4 Komponent połączenia (Call asset)
```
Telefon: +44 7882 799050
Nazwa firmy: Website Expert
Raportowanie połączeń: włączone (Google Forwarding Number)
Harmonogram: Pon–Pt 9:00–18:00
Konwersja: połączenie odebrane trwające min. 60 sekund
```

### 5.5 Komponent lokalizacji (Location asset)
> **Pominięte celowo.** Kampania jest ogólnokrajowa (cała UK), nie lokalna — w przeciwieństwie do kampanii NI/Belfast. Google Business Profile z jednym adresem miałby tu ograniczoną wartość i mógłby sugerować fałszywie lokalny zasięg. Do rozważenia dopiero przy ewentualnych podstronach regionalnych.

---

## 6. Targeting i ustawienia techniczne

| Ustawienie | Wartość |
|-----------|---------|
| **Kraj** | United Kingdom (cały kraj — Anglia, Szkocja, Walia, Irlandia Płn.) |
| **Język** | Treść reklamy i LP: polski. Od września 2026 Google usuwa ustawienie języka na poziomie kampanii Search; jeżeli opcja jest jeszcze widoczna w czasie rollout, ustawić polski + angielski, aby nie wykluczyć polskojęzycznych użytkowników z interfejsem Google po angielsku. |
| **Metoda targetowania** | `People in or regularly in this location` |
| **Sieci** | Google Search: włączona; Display: wyłączona; Search Partners: wyłączone na pierwszy test, później osobna ocena jakości leadów |
| **Urządzenia** | Bez korekt stawek na starcie; decyzja dopiero po danych o konwersjach i jakości leadów |
| **Harmonogram reklam** | Formularz: 24/7. Komponent połączenia: tylko w godzinach odbierania telefonu |
| **Strategia stawek — start** | Maximize Clicks z limitem max CPC podczas walidacji ruchu i pomiaru → Maximize Conversions po potwierdzeniu, że główne konwersje są poprawnie mierzone. Target CPA dopiero na podstawie stabilnego, rzeczywistego CPL. |

> Uwaga: część polskich przedsiębiorców może też szukać frazami mieszanymi ("polish web developer uk") — to osobna, mniejsza grupa w języku angielskim; do rozważenia jako test dopiero po ustabilizowaniu kampanii głównej (patrz sekcja 8).

---

## 7. Budżet startowy i scenariusze

Budżet ustawia się na poziomie kampanii, nie grup reklam. Proponowany test: **£20 średniego budżetu dziennego**, czyli maksymalnie około **£608 w przeciętnym miesiącu** (`£20 × 30,4`). Nie przypisywać fikcyjnych subbudżetów grupom; kontrolować ich udział przez raporty i ewentualny podział na osobne kampanie dopiero po zebraniu danych.

| Scenariusz planistyczny | Śr. CPC | Kliknięcia / mies. | Konwersja klik → lead | Leady / mies. | CPL |
|---|---:|---:|---:|---:|---:|
| Korzystny | £1.00 | ok. 608 | 5% | ok. 30 | ok. £20 |
| Bazowy | £2.00 | ok. 304 | 4% | ok. 12 | ok. £51 |
| Ostrożny | £3.00 | ok. 203 | 2% | ok. 4 | ok. £152 |

> To matematyczne scenariusze, nie prognoza. Przed startem zastąpić założenia danymi z Keyword Plannera. Break-even liczyć z marży brutto projektu i odsetka leadów zamienionych w klientów, a nie z samego przychodu £799.

---

## 8. Śledzenie konwersji

Landing page wysyła zdarzenia do `dataLayer`, ale przed startem trzeba utworzyć odpowiednie triggery i tagi w GTM, oznaczyć właściwe zdarzenia jako key events w GA4, zaimportować je do Google Ads i przetestować w trybie Preview/DebugView oraz Tag Assistant.

| Konwersja | Priorytet | Zdarzenie (już istnieje w kodzie) | Wartość |
|---|---|---|---|
| Lead utworzony (formularz wysłany) | 🔴 Primary | `lead_created` | Bez wartości na start; później rzeczywista wartość oczekiwana leada |
| Połączenie z reklamy ≥60 s | 🔴 Primary | Call reporting w Google Ads | Bez wartości na start; później na podstawie jakości połączeń |
| Kliknięcie numeru na LP | 🟡 Secondary | `phone_click` | — |
| Rozpoczęcie formularza | 🟡 Secondary | `form_start` | — |
| Kliknięcie CTA | 🟡 Secondary | `primary_cta_click` | — |
| Wejście na landing page | 🟢 Diagnostyczna | `landing_page_view` | — |

**Kroki konfiguracji:**
1. W GTM utworzyć tagi GA4 Event dla zdarzeń `lead_created`, `phone_click`, `form_start` i `primary_cta_click`, uruchamiane przez Custom Event triggers o identycznych nazwach.
2. W GA4 oznaczyć `lead_created` jako key event. Pozostałe zdarzenia zachować diagnostycznie; `phone_click` importować jako Secondary.
3. Połączyć GA4 z Google Ads, włączyć auto-tagging i zaimportować zdarzenia. Importowane konwersje GA4 są domyślnie Secondary — `lead_created` trzeba zmienić w Google Ads na Primary.
4. Pozostawić model Data-driven attribution, domyślny dla większości działań konwersji. Nie trzeba czekać na 30 konwersji, choć większy wolumen poprawia model.
5. Włączyć call reporting dla komponentu połączenia i liczyć jako Primary tylko połączenia spełniające próg jakości (startowo 60 sekund).
6. Upewnić się, że `ad_storage`/`analytics_storage` aktualizują się po zgodzie użytkownika, a tagi są widoczne w Consent Mode diagnostics.
7. Wykonać test end-to-end po kliknięciu testowej reklamy lub przez Tag Assistant: zachowanie `gclid`, jedno wysłanie formularza, obecność zdarzenia w GA4 DebugView i później w Google Ads.

---

## 9. Harmonogram wdrożenia

```
TYDZIEŃ 1:
✅ Landing page gotowy technicznie (title, meta, JSON-LD, tel:, tracking — zweryfikowane w kodzie 2026-09-17)
⬜ Grafika og:image 1200×630 (opcjonalne, nie blokuje kampanii)
⬜ Założenie/weryfikacja konta Google Ads
⬜ Konfiguracja i test tagów GTM/GA4; import `lead_created` jako Primary
⬜ Konfiguracja call reporting i konwersji połączenia ≥60 s
⬜ Wgranie kampanii: słowa kluczowe, RSA, rozszerzenia z tego dokumentu
⬜ Start wszystkich grup z potwierdzonym wolumenem z Keyword Plannera; przy bardzo małym wolumenie scalić grupy

TYDZIEŃ 2:
⬜ Analiza Search Terms Report — dopisanie wykluczeń
⬜ Sprawdzenie Quality Score per grupa reklam

TYDZIEŃ 3–4:
⬜ Pierwszy raport miesięczny: CTR, CPC, konwersje, CAC
⬜ Decyzja o teście grupy anglojęzycznej ("polish web developer uk")

MIESIĄC 2:
⬜ Remarketing RLSA — lista „odwiedzili LP, brak lead_created" (14 dni)
⬜ Test drugiego wariantu RSA tylko przy wolumenie pozwalającym odróżnić wynik od szumu
⬜ Ocena, czy najlepiej konwertujące branże zasługują na dedykowaną podstronę
```

---

## 10. KPI i kryteria decyzji

| Metryka | Hipoteza / sposób oceny po 6–8 tygodniach |
|---|---|
| CTR (Search) | ≥5% |
| Quality Score | ≥7 |
| Avg. CPC | Porównać z prognozą Keyword Plannera; bez arbitralnego limitu przed startem |
| Conversion Rate (klik → lead) | Hipoteza startowa ≥4%; rozdzielić formularze i kwalifikowane połączenia |
| CPL | Docelowy CPL wyliczyć z marży brutto, close rate i akceptowalnego CAC |
| Jakość leadów | Udział leadów spełniających kryteria sprzedażowe, raportowany z CRM |
| Lead → klient | Mierzyć z CRM i importować offline do Google Ads po uzyskaniu wolumenu |

---

## Do zrobienia zewnętrznie (poza kodem)
- Założenie/dostęp do konta Google Ads i połączenie z GA4/GTM
- Konfiguracja GTM/GA4, import `lead_created` jako Primary i walidacja Tag Assistant
- Włączenie auto-tagging oraz sprawdzenie zachowania `gclid`
- Weryfikacja numeru `+44 7882 799050`, godzin odbierania i call reportingu w komponencie połączenia
- Keyword Planner: wolumen, prognozowany CPC i odrzucenie fraz o znikomym ruchu

---

## Źródła Google Ads (stan na 2026-09-20)

- [Responsive Search Ads — limity i pinning](https://support.google.com/google-ads/answer/7684791?hl=en)
- [Enhanced CPC — wycofane dla Search od marca 2025](https://support.google.com/google-ads/answer/2464964?hl=en)
- [Budżet kampanii i strategie stawek](https://support.google.com/google-ads/answer/2375454?hl=en)
- [Zaawansowane opcje targetowania lokalizacji](https://support.google.com/google-ads/answer/9376662?hl=en)
- [Zmiana targetowania językowego Search od września 2026](https://support.google.com/google-ads/answer/1722078?hl=en-GB)
- [Konwersje Primary i Secondary](https://support.google.com/google-ads/answer/11461796?hl=en)
- [Import zdarzeń GA4 do Google Ads](https://support.google.com/google-ads/answer/2375435?hl=en)
- [Data-driven attribution](https://support.google.com/google-ads/answer/6394265?hl=en)
- [Call assets i call reporting](https://support.google.com/google-ads/answer/2453991?hl=en)
