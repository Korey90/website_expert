# MVP Plan — Digital Growth OS
## Status: MVP V1 ZREALIZOWANY — Pracujemy nad MVP V1.1
**Data:** 2026-04-03 (aktualizacja)  
**Rola:** CTO  
**Bazuje na:** `docs/project-analysis.md`, `docs/architecture-plan.md`, `docs/refactor-plan.md`  
**Cel MVP (zlecony):** profil firmy → generowanie landing page → publikacja → przechwytywanie leadów → CRM/pipeline

### STATUS WDROŻENIA MVPv1 (2026-04-03):
| Cel MVP | Status |
|---|---|
| Rejestracja → rola `client` → Business autotworzony | ✅ DONE |
| Business Profile (brand, tone_of_voice, AI context) | ✅ DONE |
| LP CRUD + sekcje + publikacja | ✅ DONE |
| AI Generator (OpenAI gpt-4o-mini) | ✅ DONE |
| Publiczny widok `/lp/{slug}` + formularz lead capture | ✅ DONE |
| Lead trafia do CRM pipeline | ✅ DONE |
| Portal klienta z LP module | ✅ DONE |
| Google OAuth | ✅ DONE |
| Filament LP Resource (widok all-tenant dla admina) | ✅ DONE |

### POZOSTAŁE DO MVPv1.1 (aktualny sprint):
1. 🔧 **Fix 4 bugów LP→CRM** — UTM, form_data, lp_captured, AI preview submit
2. 🔧 **GlobalScope BusinessScope** — aktywacja tenant isolation
3. 🔧 **LP Analytics** — views_count / conversions_count tracking
4. 🔧 **Stripe Billing** — plany SaaS (free/pro/agency), trial, limity LP
5. 🔧 **Plan gates** — `PlanService::canCreateLandingPage()` etc.
6. 🔧 **AI rate-limit** per business per miesiąc

---

## 0. Kontekst decyzji

Projekt jest **działającym, produkcyjnym CRM agencji** (WebsiteExpert) z 33 modelami, 26 Filament Resources, portalu klienta i integracji Stripe/PayU/Twilio. Transformujemy go w SaaS.

**Kluczowa decyzja architektoniczna dla MVP:**  
Nie wdrażamy pełnego multi-tenancy (GlobalScope + IdentifyBusiness Middleware) w MVP. Budujemy model `Business` jako encję, dodajemy `business_id` na nowych tabelach, ale **wymuszanie izolacji** (GlobalScope) wchodzi w Sprint 2 razem z wielodostępem. Pozwala to uruchomić MVP szybciej bez ryzyka zepsucia istniejącego CRM.

**Ważna zasada MVP:** istniejące moduły (CRM, faktury, projekty, portal klienta, automatyzacje) **zamrażamy** — nie dotykamy ich w trakcie budowy MVP. Lead pipeline istnieje i działa — podłączamy do niego nowy przepływ leadów z landing pages.

---

## 1. Cel i metryki sukcesu MVP

### Cel MVP

> Umożliwić właścicielowi firmy (agencji, freelancerowi) **stworzenie landing page, opublikowanie jej i przechwytywanie leadów** — bez pomocy dewelopera, w czasie poniżej 15 minut.

### Persony

| Persona | Rola | Co chce osiągnąć |
|---|---|---|
| **Admin agencji** | Właściciel konta SaaS | Stworzyć LP dla swojej agencji, opublikować, zbierać leady |
| **Sprzedawca agencji** | Przeglądanie leadów | Widzieć nowe kontakty z LP w pipeline CRM |
| **Klient agencji** *(w MVP poza zakresem)* | Portal klienta | Już działa — zamrażamy, nie psujemy |

### Metryki sukcesu MVP

| Metryka | Cel |
|---|---|
| Czas od rejestracji do pierwszej opublikowanej landing page | < 15 minut |
| Liczba leadów przechwyconych przez LP (test z 1 użytkownikiem) | ≥ 1 lead per sesja |
| Landing page osiągalna publicznie pod URL `/lp/{slug}` | TAK, bez autentykacji |
| Lead widoczny w panelu Filament (Lead Inbox) po ≤ 30 sek | TAK |
| Lead widoczny w Pipeline Kanban | TAK (istniejąca kolumna „New") |
| Żadna istniejąca funkcja CRM nie przestaje działać po wdrożeniu | TAK (regresja zero) |

---

## 2. Pełna inwentaryzacja funkcji z perspektywy MVP

| # | Funkcja | Istnieje? | Wartość dla użytkownika | Złożoność budowy | Kategoria MVP |
|---|---|---|---|---|---|
| 1 | Rejestracja / Logowanie / Reset hasła | ✅ TAK | Krytyczna | Gotowe | **MUST HAVE** |
| 2 | Weryfikacja email | ✅ TAK | Wysoka | Gotowe | **MUST HAVE** |
| 3 | Tworzenie konta biznesowego (Business entity) | ❌ NIE | Krytyczna | Mała | **MUST HAVE** |
| 4 | Profil firmy — brand, logo, branża, ton komunikacji | ❌ NIE | Krytyczna | Mała–Średnia | **MUST HAVE** |
| 5 | Tworzenie Landing Page (szablon, tytuł, sekcje) | ❌ NIE | Krytyczna | Średnia | **MUST HAVE** |
| 6 | Edycja sekcji LP (hero, treść, CTA) | ❌ NIE | Krytyczna | Średnia | **MUST HAVE** |
| 7 | Formularz lead capture na LP | ❌ NIE | Krytyczna | Mała | **MUST HAVE** |
| 8 | Publikacja LP pod `/lp/{slug}` | ❌ NIE | Krytyczna | Mała | **MUST HAVE** |
| 9 | Publiczny widok landing page (bez autentykacji) | ❌ NIE | Krytyczna | Mała | **MUST HAVE** |
| 10 | Zapis leada z formularza LP | ❌ NIE | Krytyczna | Mała (reuse `CreateLeadAction`) | **MUST HAVE** |
| 11 | Lead Inbox w panelu Filament | ✅ CZĘŚCIOWE (`LeadResource` istnieje) | Krytyczna | Mała (konfiguracja) | **MUST HAVE** |
| 12 | Lead trafia do Pipeline Kanban | ✅ TAK (PipelinePage istnieje) | Krytyczna | Minimalna | **MUST HAVE** |
| 13 | Dashboard z licznikiem leadów i listą LP | ✅ CZĘŚCIOWE (Filament Widgets) | Wysoka | Mała | **MUST HAVE** |
| 14 | Email powiadomienie o nowym leadzie | ✅ CZĘŚCIOWE (AutomationEngine) | Wysoka | Mała (reguła automatyzacji) | **MUST HAVE** |
| 15 | AI generator treści LP na podstawie profilu firmy (OpenAI) | ❌ NIE | Wysoka | Duża | **NICE TO HAVE** |
| 16 | Edytor drag-and-drop LP (WYSIWYG) | ❌ NIE | Wysoka | Duża | **NICE TO HAVE** |
| 17 | Podstawowe statystyki LP (wyświetlenia, konwersja %) | ❌ NIE | Wysoka | Średnia | **NICE TO HAVE** |
| 18 | Integracja UTM tracking (utm_source, utm_medium, utm_campaign) | ❌ NIE | Wysoka | Mała–Średnia | **NICE TO HAVE** |
| 19 | SMS powiadomienie o nowym leadzie | ✅ TAK (SmsService + automation) | Średnia | Minimalna | **NICE TO HAVE** |
| 20 | Multi-tenancy enforcement (GlobalScope + IdentifyBusiness) | ❌ NIE | Krytyczna (v1.1) | Duża | **NICE TO HAVE** |
| 21 | Rejestracja SaaS (kilka firm na platformie) | ❌ NIE | Krytyczna (v1.1) | Średnia | **NICE TO HAVE** |
| 22 | Custom domain dla LP | ❌ NIE | Wysoka | Duża | **PÓŹNIEJ** |
| 23 | A/B testing LP | ❌ NIE | Średnia | Duża | **PÓŹNIEJ** |
| 24 | Wielojęzyczne LP (EN/PL/PT osobne wersje) | ❌ NIE | Średnia | Średnia | **PÓŹNIEJ** |
| 25 | Kampanie email/SMS do leadów | ❌ NIE | Wysoka | Duża | **PÓŹNIEJ** |
| 26 | Lead scoring AI | ❌ NIE | Średnia | Duża | **PÓŹNIEJ** |
| 27 | Plany subskrypcyjne + Stripe Cashier (SaaS billing) | ❌ NIE | Wysoka | Duża | **PÓŹNIEJ** |
| 28 | Panel Super-Admin `/superadmin` | ❌ NIE | Niska (wewnętrzne) | Średnia | **PÓŹNIEJ** |
| 29 | Integracje reklamowe (Meta Ads, Google Ads) | brak | Średnia | Duża | **PÓŹNIEJ** |
| 30 | White-label / własna nazwa platformy per tenant | brak | Niska | Duża | **PÓŹNIEJ** |
| 31 | API publiczne (webhooks outbound, REST API) | brak | Niska | Duża | **PÓŹNIEJ** |
| 32 | Kalkulator kosztów V2 (istniejący) | ✅ TAK | Średnia (marketing) | Gotowe | **ZAMROZIĆ** |
| 33 | Portal klienta (istniejący) | ✅ TAK | Wysoka (dla agencji) | Gotowe | **ZAMROZIĆ** |
| 34 | Finanse (Faktury, Oferty, Umowy) | ✅ TAK | Wysoka (dla agencji) | Gotowe | **ZAMROZIĆ** |
| 35 | Automatyzacje (istniejące) | ✅ TAK | Wysoka | Gotowe | **ZAMROZIĆ** |
| 36 | Projekty (istniejące) | ✅ TAK | Wysoka | Gotowe | **ZAMROZIĆ** |

---

## 3. MUST HAVE — Zakres MVP

> Zasada: bez tej funkcji produkt nie realizuje celu „landing page → lead → CRM".

---

### 3.1 Rejestracja i logowanie

**Opis:** Standardowy flow auth — rejestracja, login, reset hasła, weryfikacja email.  
**Wartość:** Wejście do produktu.  
**Stan obecny:** ✅ Istnieje (Laravel Breeze + Spatie).  
**Co trzeba zrobić:** Po udanej rejestracji → automatycznie tworzyć rekord `Business` powiązany z użytkownikiem (onboarding krok 1). Dodać krok wyboru nazwy firmy w rejestracji.  
**Estymata:** Mała (½ dnia)  
**Bounded Context:** IdentityAccess

---

### 3.2 Business Account (Konto biznesowe)

**Opis:** Encja `Business` jako korzeń danych firmy w systemie. Przy pierwszej rejestracji tworzone automatycznie. W MVP: jedno konto = jeden tenant (bez pełnego multi-tenancy enforcement).  
**Wartość:** Fundament dla profilu, LP i leadów — bez tego nic nie działa.  
**Stan obecny:** ❌ Brak tabeli `businesses`, brak modelu.  
**Co trzeba zrobić:**
- Migracja `businesses` (id, name, slug, locale, logo_path, created_at)
- Migracja `business_users` (business_id, user_id, role: owner|admin|member)
- Model `Business` + relacje
- Helper `currentBusiness()` (z session/auth — bez subdomeny w MVP)
- Middleware `EnsureHasBusiness` — redirect do onboardingu jeśli brak biznesu
- Filament: wyświetlać `business.name` w topbarze

**Estymata:** Średnia (2 dni)  
**Bounded Context:** IdentityAccess + BusinessProfile  
**Uwaga:** W MVP NIE wdrażamy GlobalScope / `BelongsToTenant` na istniejących modelach — to wchodzi w NICE TO HAVE jako „Multi-tenancy enforcement".

---

### 3.3 Profil firmy (Business Profile)

**Opis:** Formularz danych firmy: nazwa, logo, branża, strona www, tagline, ton komunikacji (professional/friendly/bold/minimalist), grupa docelowa, lista usług, kolory brand. Dane te będą kontekstem dla AI generatora w v1.1.  
**Wartość:** Personalizacja landing pages i AI context — bez profilu LP jest generyczne.  
**Stan obecny:** ❌ Brak tabeli `business_profiles`, brak formularza.  
**Co trzeba zrobić:**
- Migracja `business_profiles` (business_id FK, tagline, industry, tone_of_voice, target_audience JSON, services JSON, brand_colors JSON, website_url, description)
- Model `BusinessProfile` + relacja `Business hasOne BusinessProfile`
- Strona Filament lub Inertia React: formularz edycji profilu (wieloetapowy onboarding wizard)
- Upload logo (Laravel Storage)
- Uzupełnianie profilu jako krok 2 onboardingu

**Estymata:** Średnia (2–3 dni)  
**Bounded Context:** BusinessProfile

---

### 3.4 Landing Page Builder — tworzenie i edycja

**Opis:** Prosty edytor landing page oparty na predefiniowanych sekcjach (blokach). W MVP: wybierz szablon → wypełnij treść sekcji (tytuł, podtytuł, CTA, obraz) → zapisz.  
**Wartość:** Core feature — klient tworzy LP.  
**Stan obecny:** ❌ Brak. Brak tabel `landing_pages` i `landing_page_sections`.  
**Co trzeba zrobić:**
- Migracje: `landing_pages` (business_id, title, slug, status: draft|published|archived, language, meta_title, meta_description, published_at), `landing_page_sections` (landing_page_id, type: hero|features|cta|form|faq, order, content JSON, settings JSON)
- Model `LandingPage` + `LandingPageSection`
- Filament Resource: `LandingPageResource` z edytorem sekcji (Repeater + JSON fields)
- **Alternatywnie:** Inertia + React editor (bardziej intuicyjny dla użytkownika końcowego) — **zalecane** dla UX
- Typy sekcji w MVP: `hero` (tytuł + podtytuł + CTA + obraz), `features` (lista punków), `cta` (button + tekst), `form` (lead capture — patrz 3.5)
- Walidacja: unikalny slug per business
- Podgląd LP przed publikacją

**Estymata:** Duża (5–7 dni — złożona UI)  
**Bounded Context:** LandingPages  
**Uwaga dotycząca skrótu:** W Sprincie 1 można zacząć od Filament Repeater jako edytora (szybciej), a Inertia/React editor zbudować w v1.1.

---

### 3.5 Formularz Lead Capture na Landing Page

**Opis:** Każda LP posiada co najmniej jedną sekcję `form` — formularz zbierający dane kontaktowe (imię, email, telefon, opcjonalna wiadomość). Wysłanie formularza → zapis leada.  
**Wartość:** Core cell value — bez formularza nie ma leadów.  
**Stan obecny:** ❌ Brak na LP. Formularz kontaktowy (`ContactController`) i formularz kalkulatora (`CalculatorLeadController`) istnieją — wzorzec do reużycia.  
**Co trzeba zrobić:**
- Publiczny endpoint `POST /lp/{slug}/submit` — bez autentykacji, z CSRF tokenem
- Reużyć/rozszerzyć `CreateLeadAction` — dodać `landing_page_id`, `utm_source`, `utm_medium`, `utm_campaign` jako source tracking
- Dodać kolumny do `leads`: `landing_page_id` (FK, nullable), `utm_source`, `utm_medium`, `utm_campaign`
- Walidacja Form Request: email required, name required, phone optional
- Rate limiting: max 3 submisje/IP/godzinę (bezpieczeństwo przed spamem)
- Po wysyłce: response JSON `{success: true, redirect_url?: string}` + React state „Dziękujemy"
- Honeypot field (ochrona przed botami)

**Estymata:** Mała–Średnia (2–3 dni)  
**Bounded Context:** Leads  
**Zależności:** Wymaga LP (3.4) i Business Account (3.2)

---

### 3.6 Publikacja Landing Page

**Opis:** Zmiana statusu LP z `draft` na `published` → LP dostępna pod publicznym URL `/lp/{slug}` bez logowania. Cofnięcie publikacji → status `archived`.  
**Wartość:** Bez publikacji LP nie generuje leadów.  
**Stan obecny:** ❌ Brak.  
**Co trzeba zrobić:**
- Publiczna trasa: `GET /lp/{slug}` → `LandingPageController::show()` — bez middleware auth
- Render LP: Inertia + React lub Blade — **zalecana dedykowana strona React** (`Pages/LandingPage/Show.jsx`) z renderowaniem sekcji per `type`
- Logika publish/unpublish w Filament Resource (Action z potwierdzeniem)
- Walidacja: LP musi mieć min. 1 sekcję i min. 1 sekcję `form` przed publikacją
- SEO: `<title>` i `<meta description>` z LP settings
- Tracking: zapis `page_view` do tabeli / licznik `views` (opcjonalne w MVP — można pominąć)

**Estymata:** Mała–Średnia (2 dni)  
**Bounded Context:** LandingPages

---

### 3.7 Lead Inbox — widok przechwyconych leadów

**Opis:** Lista leadów z LP w panelu Filament — kolumny: imię, email, telefon, źródło (LP title), data, status pipeline.  
**Wartość:** Sprzedawca widzi nowe kontakty i może je obsługiwać.  
**Stan obecny:** ✅ `LeadResource` w Filament istnieje i działa. Zawiera wszystkie potrzebne pola.  
**Co trzeba zrobić:**
- Dodać filtr `source = landing_page` w `LeadResource`
- Dodać kolumnę „Źródło LP" (relacja `landing_page.title`) w tabeli leadów
- Dodać `landing_page_id` do Filament table columns oraz filter
- Upewnić się, że lead z LP trafia domyślnie na pierwszą kolumnę Pipeline (PipelineStage `order = 1`)

**Estymata:** Mała (½ dnia — konfiguracja istniejącego zasobu)  
**Bounded Context:** Leads + CRM

---

### 3.8 Lead → Pipeline CRM

**Opis:** Lead z formularza LP automatycznie pojawia się w Pipeline Kanban na pierwszej kolumnie. Sprzedawca może go przeciągać, dodawać notatki, zmieniać status.  
**Wartość:** Zamknięcie pętli: LP → lead → sprzedaż.  
**Stan obecny:** ✅ `PipelinePage` (Kanban) istnieje i działa. `PipelineStage` model istnieje.  
**Co trzeba zrobić:**
- W `CreateLeadAction` upewnić się, że nowy lead dostaje `pipeline_stage_id` pierwszego aktywnego stage'u (order = 1)
- Sprawdzić czy `LeadActivity::log('lead_created', ...)` jest wywoływane przy tworzeniu leada z LP
- Opcjonalnie: dodać badge „📋 Z Landing Page" w PipelinePage dla leadów z `landing_page_id IS NOT NULL`

**Estymata:** Minimalna (1–2 godziny)  
**Bounded Context:** CRM  
**Zależności:** Wymaga 3.5

---

### 3.9 Email powiadomienie o nowym leadzie

**Opis:** Automatyczne powiadomienie email do admina/managera gdy nowy lead trafi do systemu z LP.  
**Wartość:** Szybka reakcja sprzedawcy = wyższa konwersja.  
**Stan obecny:** ✅ `AutomationEngine` istnieje, `SendEmailAction` istnieje. Wystarczy skonfigurować regułę.  
**Co trzeba zrobić:**
- W `AdminSeeder` lub onboardingu: dodać domyślną regułę automatyzacji `trigger: lead_created` → `action: send_internal_email` do assignedTo/admin
- Alternatywnie: Stworzyć `LeadCreatedMail` i wywołać w `CreateLeadAction` bezpośrednio (prostsze)
- **Rekomendacja:** użyć istniejącego Automation Engine — bo jest już przetestowane

**Estymata:** Mała (1 dzień — konfiguracja + szablon email)  
**Bounded Context:** Automations (istniejące)

---

### 3.10 Dashboard MVP

**Opis:** Strona startowa po logowaniu — podsumowanie KPI: liczba LP (draft/published), liczba leadów (ostatnie 7 dni), przychód z pipeline (opcjonalnie).  
**Wartość:** Orientacja w sytuacji na pierwszy rzut oka.  
**Stan obecny:** ✅ Filament Widgets istnieją (StatsOverviewWidget, RecentLeadsWidget i inne 11 widgetów).  
**Co trzeba zrobić:**
- Dodać `LandingPageStatsWidget` — liczba LP draft/published/archived
- Dodać filtr `source = landing_page` do `RecentLeadsWidget`
- Upewnić się że dashboard renderuje się poprawnie po dodaniu Business entity

**Estymata:** Mała (1 dzień)  
**Bounded Context:** BusinessProfile

---

## 4. NICE TO HAVE — wersja v1.1

> Działa bez nich, ale znacząco zwiększą wartość, konwersję lub efektywność.

---

### 4.1 AI Generator treści LP (OpenAI)

**Opis:** Na podstawie Business Profile (tone_of_voice, industry, services, target_audience) generuje propozycję treści dla każdej sekcji LP: nagłówek, podtytuł, CTA text, lista punktów.  
**Wartość:** Redukcja czasu tworzenia LP z 15 min do 2 min.  
**Zależy od:** Business Profile (3.3) musi być wypełniony. LP Builder (3.4) musi działać.  
**Estymata:** Duża (5–8 dni — OpenAI integration + `GenerateLandingPageJob` + UI)  

---

### 4.2 Statystyki LP (wyświetlenia + konwersja)

**Opis:** Licznik unikalnych wyświetleń LP i licznik submisji formularza → obliczenie conversion rate (submisje / wyświetlenia).  
**Wartość:** Klient wie która LP działa najlepiej.  
**Zależy od:** LP musi być opublikowana i aktywna.  
**Estymata:** Średnia (3 dni — kolumny `views` + `conversions` na `landing_pages`, JS beacon lub server-side tracking)

---

### 4.3 UTM Tracking leadów

**Opis:** Zapis `utm_source`, `utm_medium`, `utm_campaign` z URL przy submisji formularza LP.  
**Wartość:** Klient wie z jakiej kampanii reklamowej przyszedł lead.  
**Zależy od:** Lead Capture (3.5) musi działać.  
**Estymata:** Mała (1 dzień — kolumny już zaplanowane w architekturze, odczyt z URL query string w React)

---

### 4.4 Multi-tenancy enforcement (GlobalScope + Middleware)

**Opis:** Pełna izolacja danych między firmami: `BelongsToTenant` trait na modelach, `BusinessScope` GlobalScope, `IdentifyBusiness` Middleware (subdomena lub session).  
**Wartość:** Krytyczne przy > 1 firmie na platformie.  
**Zależy od:** Business Account (3.2) musi działać, refaktor service layer (z refactor-plan.md Faza 3) powinien być gotowy.  
**Estymata:** Duża (5–7 dni, wysoki risk)  
**Uwaga:** Bez tego funkcji MVP **NIE można bezpiecznie udostępnić wielu firmom**. Jeden tenant = brak problemu. W chwili onboardingu drugiej firmy — blokujące.

---

### 4.5 SaaS Registration flow (wiele firm na platformie)

**Opis:** Publiczna strona rejestracji SaaS — dowolna firma może się zarejestrować i zacząć używać systemu (bez zaproszenia administratora).  
**Wartość:** Skalowalność produktu.  
**Zależy od:** Multi-tenancy (4.4) MUSI być gotowe wcześniej.  
**Estymata:** Średnia (3 dni)

---

### 4.6 Inertia/React editor LP (zamiast Filament Repeater)

**Opis:** Dedykowany, intuicyjny edytor LP w React z podglądem na żywo, zamiast formularza Filament.  
**Wartość:** Lepsze UX dla klientów SaaS — Filament Repeater jest funkcjonalny ale nie przyjazny.  
**Zależy od:** LP Builder (3.4) musi działać (Filament wersja jako fundament).  
**Estymata:** Duża (7–10 dni)

---

### 4.7 SMS powiadomienie o nowym leadzie

**Opis:** Powiadomienie SMS do admina/managera gdy nowy lead trafi z LP (przez Twilio).  
**Wartość:** Szybsza reakcja niż email.  
**Zależy od:** `SmsService` już działa — wystarczy reguła Automation lub bezpośrednie wywołanie.  
**Estymata:** Mała (½ dnia)

---

### 4.8 Wybór szablonu LP (template picker)

**Opis:** Galeria gotowych szablonów LP (4–6 wariantów: lead magnet, usługi, portfolio, sprzedaż, webinar, coming soon) — klient wybiera i dostosowuje.  
**Wartość:** Przyspiesza tworzenie LP, redukuje „blank page problem".  
**Zależy od:** LP Builder (3.4).  
**Estymata:** Średnia (3–4 dni per szablon)

---

## 5. PÓŹNIEJ — v2+

> Za duże, za niszowe, lub wymagają walidacji rynkowej przed budową.

| Funkcja | Powód przesunięcia |
|---|---|
| **A/B testing LP** | Wymaga traffic-splitting middleware, statystyk, selectora wariantów — duża złożoność przy niepewnej potrzebie w MVP |
| **Custom domains dla LP** (`lp.moja-agencja.pl`) | Wymaga DNS wildcard, SSL automation (Let's Encrypt), subdomain routing — infrastruktura DevOps |
| **Kampanie email/SMS** | Nowy bounded context, wymaga multi-tenancy, listy odbiorców, unsubscribe flow — GDPR complexity |
| **Lead scoring AI** | Wymaga danych historycznych do treningu/promptu; bez historii wynik będzie bezwartościowy |
| **Plany subskrypcyjne + Stripe Cashier** | Wymaga: multi-tenancy, BusinessModel Billable, plany + limity, upgrade/downgrade flow — blokujące inne nie-MVP |
| **Panel Super-Admin `/superadmin`** | Wewnętrzne narzędzie; nie dotyczy klienta SaaS |
| **Wielojęzyczne LP** (EN/PL/PT per strona) | Wymaga per-section translations, switcher na LP, i18n w edytorze |
| **Integracje reklamowe** (Meta Ads API, Google Ads API) | Wymaga OAuth per konto reklamowe, API quota management — wysoka złożoność |
| **White-label** | Wymaga własnych domen per tenant + konfiguracji brandingu per tenant |
| **API publiczne** | Wymaga auth tokenów per tenant, dokumentacji, wersjonowania |
| **Reverb WebSocket** (real-time notyfikacje) | Infrastruktura już skonfigurowana (`reverb.php`); aktualny polling jest wystarczający dla MVP |

---

## 6. DO ZAMROŻENIA lub USUNIĘCIA

---

**Element:** `app/Http/Controllers/PortalController.php` (447 linii)  
**Dlaczego:** Duplikat `Portal/*` kontrolerów; martwy lub duplikowany kod  
**Rekomendacja:** USUŃ przed wdrożeniem MVP (Refactor Faza 0)

---

**Element:** `resources/js/Components/Marketing/CostCalculator.jsx` (V1, 467 linii)  
**Dlaczego:** Legacy kalkulator; aktywna jest V2  
**Rekomendacja:** USUŃ (Refactor Faza 0)

---

**Element:** Kalkulator kosztów V2 — nowe funkcje  
**Dlaczego:** Działa produkcyjnie; brak wartości w rozwijaniu w kontekście transformacji SaaS  
**Rekomendacja:** ZAMROŹ — nie rozwijaj w trakcie MVP; zachowaj istniejące działanie

---

**Element:** Portal klienta (`Portal/` — 12 stron Inertia + 9 kontrolerów)  
**Dlaczego:** Działa dla istniejących klientów agencji; nie jest celem SaaS MVP  
**Rekomendacja:** ZAMROŹ — nie dotykaj; upewnij się że MUST HAVE nie psuje żadnej trasy `/portal/*`

---

**Element:** Moduł finansowy (Faktury, Oferty, Umowy, Płatności Stripe/PayU)  
**Dlaczego:** Działa produkcyjnie; nie jest w zakresie MVP SaaS  
**Rekomendacja:** ZAMROŹ — service layer (refactor-plan.md Faza 1) można odrożyć po MVP

---

**Element:** Szablony CMS (PageResource, SiteSectionResource) — nowe treści  
**Dlaczego:** CMS strony marketingowej (`/p/{slug}`) działa; nie jest priorytetem  
**Rekomendacja:** ZAMROŹ

---

**Element:** Moduł Kampanii (brak implementacji)  
**Dlaczego:** Nowy bounded context; nie blokuje MVP; wymaga multi-tenancy najpierw  
**Rekomendacja:** START dopiero po v1.1 (multi-tenancy)

---

## 7. Mapa drogowa MVP (Roadmap)

```
Tydzień 0 (Przygotowanie — 1-2 dni)
  ============================================================
  [x] Refactor Faza 0: usuń PortalController.php
  [x] Refactor Faza 0: usuń CostCalculator.jsx V1
  [x] Weryfikacja: uruchomić pełny zestaw testów Feature (15 testów) — zero regresji
  [ ] Weryfikacja: upewnić się że dev environment działa (artisan dev)


Sprint 1 (Tydzień 1–2): Fundamenty SaaS
  ============================================================
  [ ] Migracja + Model Business + BusinessUser (pivot)
  [ ] Helper currentBusiness() + Middleware EnsureHasBusiness
  [ ] Modyfikacja rejestracji: po signup → tworzenie Business entity
  [ ] Migracja + Model BusinessProfile
  [ ] Filament: formularz Business Profile (onboarding wizard krok 1 i 2)
  [ ] Upload logo (Laravel Storage + public URL)
  [ ] Migracja: dodać business_id do landing_pages (nowa tabela)
  [ ] Migracja: dodać landing_page_id + utm_* do leads (istniejąca tabela)
  [ ] Model LandingPage + LandingPageSection
  ---- Deliverable: użytkownik może się zarejestrować i uzupełnić profil firmy
  ---- Test regresji: portal klienta, faktury, CRM nadal działają


Sprint 2 (Tydzień 3–4): Landing Page Builder
  ============================================================
  [ ] Filament: LandingPageResource (CRUD LP)
  [ ] Filament: sekcje LP jako Repeater (typy: hero, features, cta, form)
  [ ] LP Builder — formularze sekcji per type (JSON content editor)
  [ ] Walidacja: unikalny slug, wymagane pola przed publikacją
  [ ] Podgląd LP (link otwierający /lp/{slug} w nowej karcie)
  [ ] Actions Filament: Publish / Unpublish z potwierdzeniem
  ---- Deliverable: użytkownik tworzy i edytuje LP w panelu


Sprint 3 (Tydzień 5–6): Publikacja + Lead Capture
  ============================================================
  [ ] Publiczna trasa GET /lp/{slug} (bez middleware auth)
  [ ] Strona React LP: Pages/LandingPage/Show.jsx — renderowanie sekcji
  [ ] Render sekcji Hero, Features, CTA, FAQtext, Form
  [ ] Formularz lead capture: POST /lp/{slug}/submit
  [ ] Endpoint: walidacja, rate limiting (3/IP/h), honeypot
  [ ] Reużycie CreateLeadAction: zapisz lead z landing_page_id + utm_*
  [ ] Lead → pierwsza kolumna Pipeline (pipeline_stage order=1)
  [ ] Strona podziękowania (success state komponentu formularza)
  ---- Deliverable: LP jest publiczna, formularz działa, lead trafia do CRM


Sprint 4 (Tydzień 7–8): Zamknięcie MVP
  ============================================================
  [ ] Email powiadomienie o nowym leadzie (automation rule lub LeadCreatedMail)
  [ ] LeadResource: kolumna „Źródło LP" + filtr po landing_page_id
  [ ] Dashboard Widget: LandingPageStatsWidget (LP draft/published/leads count)
  [ ] Pipeline: badge „Z Landing Page" dla leads z landing_page_id IS NOT NULL
  [ ] Testy E2E: zarejestruj → utwórz profil → utwórz LP → opublikuj → wyślij formularz → sprawdź lead w CRM
  [ ] Testy regresji: uruchom pełny zestaw 15 Feature Tests + nowe testy LP (min. 5)
  [ ] Bug fixing
  [ ] Deploy na staging
  [ ] Test z prawdziwym użytkownikiem (klient beta)
  ---- Deliverable: MVP gotowe do beta testów
```

---

## 8. Decyzje techniczne MVP

### 8.1 Edytor LP: Filament Repeater vs Inertia React

| Kryterium | Filament Repeater | Inertia/React Editor |
|---|---|---|
| Czas budowy | **2–3 dni** | 7–10 dni |
| UX dla użytkownika końcowego | Akceptowalny | Doskonały |
| Możliwość podglądu na żywo | Ograniczona | TAK |
| Drag & drop | NIE | TAK (v1.1) |
| Zgodność z istniejącym stackiem | ✅ Natywny | ✅ (już używany) |
| **Rekomendacja MVP** | ✅ **FILAMENT** | W v1.1 |

**Decyzja:** Sprint 2 buduje edytor w Filament Repeater. Inertia/React editor to v1.1 (Nice to Have 4.6).

### 8.2 Render Landing Page: Inertia vs Blade

| Kryterium | Inertia + React | Blade |
|---|---|---|
| Spójność ze stackiem | ✅ Inertia to główny stack | Mieszanie frameworków |
| Czas budowy pierwszej strony | 2 dni | 1 dzień |
| SEO (SSR) | ⚠️ Wymaga Inertia SSR (opcjonalnie) | ✅ Natywne |
| Reużycie komponentów | ✅ Marketing components gotowe | Nie |
| **Rekomendacja MVP** | ✅ **INERTIA + REACT** | — |

**Decyzja:** `/lp/{slug}` renderuje przez Inertia → `Pages/LandingPage/Show.jsx`. SEO met-tagi przez Laravel View helper `<head>` (server-side Inertia partial).

### 8.3 Multi-tenancy w MVP: NOT enforced

**Decyzja:** `businesses` tabela jest od razu tworzona, `business_id` jest na nowych tabelach (`landing_pages`, `leads` rozszerzony), ale `BelongsToTenant` GlobalScope NIE jest aktywowany. System działa jako single-tenant dla pierwszego użytkownika. Ta decyzja MUSI być odwrócona przed onboardingiem drugiego tenanta (NICE TO HAVE 4.4).

**Ryzyko:** Jeżeli dwa konta zarejestrują się przed wdrożeniem GlobalScope — dane będą widoczne cross-tenant. **Mitygacja:** Kontrolowany beta (zaproszenie do pierwszego konta), lub feature flag blokujący rejestrację.

---

## 9. Ryzyka MVP

| Ryzyko | Prawdopodobieństwo | Wpływ | Mitygacja |
|---|---|---|---|
| LP Builder (Filament Repeater) okaże się zbyt trudny do wypełnienia przez użytkownika | MEDIUM | WYSOKI | Dodać preset content dla każdej sekcji per industry; Inertia/React editor w v1.1 |
| Multi-tenancy pominięte w MVP — wyciek danych jeśli 2+ firm | HIGH (przy otwartej rejestracji) | KRYTYCZNY | Zablokować rejestrację (`REGISTRATION_CLOSED=true` w .env) — beta tylko przez zaproszenie |
| Istniejące Feature Tests padną po dodaniu `business_id` do `leads` | MEDIUM | ŚREDNI | Uruchamiać testy po każdej migracji; fixtures w testach muszą mieć `business_id` |
| SEO LP nie będzie działać (Inertia SPA bez SSR) | LOW-MEDIUM | ŚREDNI | Dla MVP akceptowalne; dodać `<og:*>` meta przez Inertia partial; SSR opcjonalnie w v1.1 |
| Istniejący `CreateLeadAction` wymaga zmian w schemacie — może zepsuć `CalculatorLeadController` | MEDIUM | ŚREDNI | `landing_page_id` i `utm_*` jako nullable — nie zepsują istniejących wywołań |
| Rate limiting na `/lp/{slug}/submit` może blokować testy | LOW | NISKI | Wyłączyć rate limiting w `APP_ENV=testing` |

---

## 10. Zależności między sprintami (Critical Path)

```
[S1] Business Account + Business Profile
         ↓
[S2] Landing Page Builder (wymaga Business entity)
         ↓
[S3] LP Publication + Lead Capture (wymaga LP + sekcja form)
         ↓              ↓
    Pipeline CRM    Lead Inbox
    (już istnieje)  (konfiguracja LeadResource)
         ↓
[S4] MVP complete (email notify + dashboard widget + testy)
```

**Nie ma możliwości równoległej pracy** na Sprint 2 i 3 — LP Builder musi istnieć zanim Lead Capture ma sens. Sprint 1 (Business Account) jest prereqiem dla wszystkiego.

---

## 11. Szacowanie całościowe

| Sprint | Zakres | Estymata |
|---|---|---|
| Tydzień 0 | Refactor Faza 0 + testy regresji | 1–2 dni |
| Sprint 1 | Business Account + Business Profile | 5–7 dni |
| Sprint 2 | Landing Page Builder (Filament) | 5–7 dni |
| Sprint 3 | LP Publication + Lead Capture + CRM connect | 4–6 dni |
| Sprint 4 | Dashboard + notyfikacje + testy + deploy | 3–5 dni |
| **SUMA** | **MVP kompletny** | **~4–5 tygodni (1 developer)** |

---

*Plan zatwierdza CTO/Product Owner. Po zatwierdzeniu → uruchamiamy `saas-feature-design` dla Sprint 1 (Business Account + Business Profile) jako następny krok.*
