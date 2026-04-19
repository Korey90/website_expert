# Feature Design: Lead Capture Form
**Data:** 2026-03-31
**Status:** AWAITING APPROVAL — nie implementuj bez zatwierdzenia
**Bazuje na:** `docs/project-analysis.md`, `docs/architecture-plan.md`, `docs/mvp-plan.md`, `docs/feature-crm-lead-integration.md`

---

## Definicja modułu: LeadCapture

**Cel**: Umożliwić anonimowemu odwiedzającemu wysłanie formularza na publicznej landing page, zapisanie leada w istniejącym CRM i uruchomienie dalszego flow sprzedażowego bez ręcznego przepisywania danych.

**Bounded Context**: `Leads` z integracją do `LandingPages` i `CRM`

**Priorytet MVP**: MUST HAVE

**Zależności**:
- `Business` i `currentBusiness()` po stronie panelu
- `LandingPage` oraz publiczna publikacja pod `/lp/{slug}`
- `Lead`, `Client`, `Contact`, `PipelineStage`, `LeadActivity`
- `CreateLeadAction`, `LeadService`, `LeadSourceService`, `LeadConsentService`
- `LeadCaptured`, `NotifyLeadOwnerListener`, `AutomationEventListener`, `ProcessAutomationJob`

**Użytkownik**:
- anonimowy visitor landing page
- admin / manager w panelu CRM
- zespół sprzedaży otrzymujący powiadomienia o nowym leadzie

---

## 1. Kontekst i stan obecny

Lead capture dla landing pages jest w repozytorium częściowo zaimplementowany i działa end-to-end. Obecnie istnieją:

- publiczny endpoint `POST /lp/{slug}/submit`
- walidacja przez `LeadCaptureRequest`
- zapis do `leads`, `clients`, `contacts`, `lead_sources`, `lead_consents`
- powiązanie leada z `landing_page_id` i `business_id`
- deduplikacja per `email + landing_page + dzień`
- zabezpieczenia: honeypot i route throttle
- event `LeadCaptured`
- powiadomienia i automatyzacje CRM po zapisie

Ten dokument nie projektuje modułu od zera. Jego celem jest ustalenie docelowej, spójnej specyfikacji formularza LP na bazie istniejącego kodu i wskazanie minimalnych rozszerzeń UX / domenowych, które warto utrzymać jako standard dla dalszych prac.

---

## 2. Cel biznesowy formularza

Formularz lead capture zamyka podstawowy lejek MVP:

`Business Profile -> Landing Page -> Public Submission -> Lead w CRM -> Powiadomienie / Automatyzacja`

Użytkownik końcowy ma wykonać jedną prostą akcję: zostawić dane kontaktowe. System ma następnie:

- potwierdzić sukces bez przeładowania strony,
- utworzyć albo zaktualizować kontekst CRM w sposób bezpieczny dla tenantów,
- przypisać źródło leada do konkretnej landing page,
- zapisać atrybucję marketingową i zgodę,
- uruchomić dalsze procesy sprzedażowe.

---

## 3. Zakres modułu

### Wchodzi w zakres

- formularz w sekcji `form` publicznej landing page,
- pola podstawowe: `name`, `email`, `phone`, `message`,
- opcjonalna zgoda `consent`,
- walidacja frontend + backend,
- zapis submission do istniejącego CRM,
- relacja submission -> landing page,
- relacja submission -> tenant (`business_id`),
- podstawowe anti-spam,
- success/error UX,
- uruchomienie istniejących eventów, notyfikacji i automatyzacji.

### Nie wchodzi w zakres

- rozbudowany builder niestandardowych pól formularza,
- scoring AI leada,
- reCAPTCHA / Cloudflare Turnstile,
- wieloetapowe formularze,
- double opt-in,
- routing leadów round-robin,
- osobny panel analityczny lead capture.

---

## 4. Model danych

W MVP nie jest wymagana nowa tabela dedykowana tylko formularzowi LP. Projekt opiera się na już istniejących encjach i relacjach.

### TABELA: `leads`
Cel: główny rekord CRM dla przechwyconego kontaktu.

Wykorzystywane pola:
- `id`
- `business_id` — tenant owner leada
- `client_id`
- `contact_id`
- `pipeline_stage_id`
- `assigned_to`
- `source` = `landing_page`
- `notes` — mapowane z `message`
- `landing_page_id`
- `utm_source`
- `utm_medium`
- `utm_campaign`
- `utm_content`
- `utm_term`
- `created_at`

Relacje:
- belongs to: `business`
- belongs to: `landingPage`
- belongs to: `client`
- belongs to: `contact`
- belongs to: `stage`
- has many: `activities`
- has many: `notes`

### TABELA: `lead_sources`
Cel: pełna atrybucja źródła pozyskania.

Wykorzystywane pola:
- `lead_id`
- `business_id`
- `type` = `landing_page`
- `landing_page_id`
- `utm_*`
- `referrer_url`
- `page_url`
- `ip_address`
- `ip_hash`
- `user_agent`
- `device_type`
- `country_code`
- `created_at`

Uwagi:
- tabela jest immutable po utworzeniu,
- `ip_address` podlega polityce retencji z `config/leads.php`,
- `ip_hash` pozostaje jako mniej wrażliwy ślad analityczny.

### TABELA: `lead_consents`
Cel: audit trail zgody na kontakt.

Wykorzystywane pola:
- `lead_id`
- `given`
- `consent_text`
- `consent_version`
- `collected_at`
- `source_url`
- `ip_hash`
- `locale`

Uwagi:
- rekord tworzony tylko wtedy, gdy `consent = true`,
- treść zgody pochodzi z plików `lang/*/gdpr.php`,
- wersja zgody domyślnie z `config/leads.php`.

### TABELA: `landing_pages`
Cel: źródło publicznego formularza i kontekstu tenantowego.

Wykorzystywane pola:
- `id`
- `business_id`
- `title`
- `slug`
- `status`
- `default_assignee_id`

Formularz może działać tylko dla landing page o statusie `published`.

---

## 5. Kontrakt formularza

### Pola formularza MVP

1. `name`
   - typ: `text`
   - wymagane: nie
   - cel: nazwa kontaktu wyświetlana w CRM i pomoc przy tworzeniu `Contact`

2. `email`
   - typ: `email`
   - wymagane: tak
   - cel: główny identyfikator leada i deduplikacji

3. `phone`
   - typ: `tel`
   - wymagane: nie
   - cel: dodatkowy kanał kontaktu dla sprzedaży

4. `message`
   - typ: `textarea`
   - wymagane: nie
   - cel: treść zapytania, mapowana do `leads.notes`

5. `consent`
   - typ: `checkbox`
   - wymagane: nie na poziomie technicznym MVP
   - cel: zapis zgody do `lead_consents` jeśli user ją zaznaczy

6. `website`
   - typ: hidden honeypot
   - wymagane: musi pozostać puste
   - cel: blokada prostych botów

### Minimalny payload MVP

```json
{
  "name": "Jan Kowalski",
  "email": "jan@example.com",
  "phone": "+48123123123",
  "message": "Chcę poznać ofertę",
  "consent": true,
  "website": ""
}
```

### Endpoint

`POST /lp/{slug}/submit`

Parametry trackingowe są pobierane z query string i nagłówków requestu:

- `utm_source`
- `utm_medium`
- `utm_campaign`
- `utm_content`
- `utm_term`
- `Referer`
- `User-Agent`
- adres IP

---

## 6. Walidacja backendowa

### REQUEST: `app/Http/Requests/LandingPage/LeadCaptureRequest.php`

Obecny kontrakt backendowy jest poprawną bazą dla MVP i powinien zostać zachowany jako standard:

- `name`: `nullable|string|max:255`
- `email`: `required|string|max:255|email:rfc`
- `phone`: `nullable|string|max:50|regex:/^[\+\d\s\-\(\)]{6,50}$/`
- `company`: `nullable|string|max:255`
- `message`: `nullable|string|max:2000`
- `website`: `sometimes|size:0`

### Docelowe reguły produktu

- `email` pozostaje jedynym polem obowiązkowym dla MVP,
- `name` pozostaje opcjonalne, żeby nie obniżać konwersji,
- `phone` opcjonalne, ale jeżeli podane, musi mieć poprawny format,
- `message` opcjonalne z limitem 2000 znaków,
- `website` musi być puste, inaczej request jest odrzucany jako spam,
- brak dostępu do draft / archived LP musi skutkować `404`, nie `403`.

### Odpowiedzi błędów

- `422` dla błędów walidacji,
- `404` gdy slug nie istnieje lub LP nie jest opublikowana,
- `429` gdy zadziała throttle,
- `500` tylko dla nieobsłużonego błędu zapisu.

---

## 7. Anti-spam i bezpieczeństwo

### Obowiązkowe mechanizmy MVP

1. Honeypot
   - ukryte pole `website`
   - bot, który je wypełni, dostaje `422`

2. Rate limiting
   - route middleware: `throttle:3,60`
   - maksymalnie 3 submission z jednego IP w ciągu 60 minut

3. Deduplikacja biznesowa
   - fingerprint: `md5(lowercase(email) + landing_page_id + date)`
   - zakres: ten sam email, ta sama landing page, ten sam dzień
   - cel: zablokować wielokrotne tworzenie leadów przy odświeżeniu / spam clicku

4. Public page status guard
   - formularz działa tylko dla opublikowanej strony

### Rekomendowane rozszerzenia v1.1

- czasowa blokada submitu zbyt szybko po załadowaniu strony,
- soft blacklist dla powtarzających się IP hash,
- Turnstile lub reCAPTCHA dla tenantów z wysokim poziomem spamu,
- jawne logowanie prób spamowych do osobnej tabeli lub kanału monitoringu.

---

## 8. Tenant isolation i relacje domenowe

### Relacja z landing page

Każdy submission musi być powiązany z dokładnie jedną `LandingPage` przez `landing_page_id`.

Zasady:
- lookup odbywa się po publicznym `slug`,
- resolve musi zwracać wyłącznie LP ze statusem `published`,
- formularz dziedziczy kontekst biznesowy z `LandingPage::business_id`,
- `default_assignee_id` z LP może zostać użyty jako domyślny owner leada.

### Relacja z tenantem

Każdy lead przechwycony przez LP musi mieć ustawione `business_id` identyczne z `landing_pages.business_id`.

Zasady:
- tenant nie jest przekazywany z requestu publicznego,
- tenant wynika wyłącznie z opublikowanej landing page,
- `LeadSource` musi dziedziczyć ten sam `business_id`,
- `Client` tworzony lub wyszukiwany jest w zakresie tego samego `business_id`,
- dzięki temu ten sam email może istnieć w różnych tenantach bez kolizji.

---

## 9. Backend Laravel

### KONTROLER: `app/Http/Controllers/LandingPage/PublicLandingPageController.php`

Odpowiedzialność:
- rozwiązać publiczną LP po slugu,
- zebrać kontekst źródła i zgody,
- delegować zapis do `LeadService`,
- zwrócić prostą odpowiedź JSON dla UI.

### SERWIS: `app/Services/Leads/LeadService.php`

Odpowiedzialność:
- obsłużyć pełny flow `Landing Page -> CRM`.

Metoda publiczna:

`createFromLandingPage(array $validated, array $sourceData, array $consentData, LandingPage $lp): array`

Logika:
- oblicza fingerprint deduplikacyjny,
- blokuje duplikat w 24h oknie biznesowym,
- tworzy `Lead` przez `CreateLeadAction`,
- tworzy `LeadSource`,
- warunkowo tworzy `LeadConsent`,
- zapisuje aktywność `lp_captured`,
- emituje `LeadCaptured`,
- zwraca status `created` albo `duplicate`.

### ACTION: `app/Actions/CreateLeadAction.php`

Odpowiedzialność:
- utworzyć lub odzyskać `Client` w zakresie `business_id`,
- utworzyć lub odzyskać `Contact`,
- przypisać pierwszy etap pipeline,
- ustawić `assigned_to` z LP,
- utworzyć rekord `Lead`.

### SERWISY wspierające

`LeadSourceService`
- zapisuje kanał wejścia, UTM, IP, user-agent i device type.

`LeadConsentService`
- zapisuje audit trail zgody,
- pobiera treść zgody na podstawie locale.

### EVENTY i kolejki

Po poprawnym submitcie formularza musi zostać wyemitowany `LeadCaptured`.

Downstream:
- `NotifyLeadOwnerListener` — notyfikacja do ownera / assignee,
- `AutomationEventListener` — dispatch `lead.created` z kontekstem LP,
- `ProcessAutomationJob` — dalsze automatyzacje,
- mail / in-app notifications zgodnie z istniejącą konfiguracją.

---

## 10. Frontend i UX

### Komponent formularza publicznego

Formularz renderowany w sekcji `form` landing page powinien być prosty i szybki do wypełnienia.

Układ MVP:
- pole `name`
- pole `email`
- pole `phone`
- pole `message`
- checkbox zgody
- przycisk CTA

### Stany UI

1. Idle
   - pola aktywne
   - CTA dostępne

2. Submitting
   - przycisk z loading state
   - zablokowanie wielokrotnego klikania
   - zachowanie wpisanych danych

3. Success
   - komunikat sukcesu z sekcji formularza albo fallback tłumaczeń
   - opcjonalny redirect do `redirect_url`, jeśli ustawiony na LP
   - wyczyszczenie formularza tylko po rzeczywistym powodzeniu

4. Validation Error
   - komunikaty pod polami
   - brak resetu wartości
   - fokus na pierwsze pole z błędem

5. Server Error
   - ogólny komunikat błędu
   - możliwość ponowienia bez utraty danych

### Copy i i18n

Formularz musi korzystać z istniejących tłumaczeń i locale publicznej strony.

Wymagania:
- success message z konfiguracji sekcji `form` lub fallback tłumaczeń,
- treść checkboxa zgody z `lang/en|pl|pt/gdpr.php`,
- komunikaty walidacyjne spójne z backendem.

### Dostępność

- każdy input musi mieć `label`,
- błędy walidacyjne muszą być czytelne dla screen readera,
- loading state nie może ukrywać focusa,
- przycisk submit musi mieć jednoznaczną etykietę CTA.

---

## 11. Odpowiedź API

### Sukces

```json
{
  "success": true,
  "message": "Dziękujemy, odezwiemy się wkrótce.",
  "redirect_url": null
}
```

### Błąd walidacji

```json
{
  "message": "The given data was invalid.",
  "errors": {
    "email": ["Pole email jest wymagane."]
  }
}
```

### Błąd systemowy

```json
{
  "success": false,
  "message": "Nie udało się wysłać formularza. Spróbuj ponownie."
}
```

Uwaga produktowa:
- duplikat submission powinien pozostać UX-owo traktowany jak sukces,
- użytkownik nie powinien widzieć technicznego rozróżnienia `created` vs `duplicate`.

---

## 12. Workflow użytkownika

1. Visitor otwiera opublikowaną landing page pod `/lp/{slug}`.
2. Wypełnia formularz i klika CTA.
3. Frontend wysyła `POST /lp/{slug}/submit`.
4. Backend waliduje dane i zabezpieczenia anti-spam.
5. System rozwiązuje `LandingPage` i dziedziczy jej `business_id`.
6. `LeadService` zapisuje lead w CRM.
7. Powstają powiązane rekordy `LeadSource` i opcjonalnie `LeadConsent`.
8. Emitowany jest `LeadCaptured`.
9. Zespół sprzedaży dostaje notyfikację / automation trigger.
10. Visitor widzi stan sukcesu albo redirect thank-you.

---

## 13. Decyzje projektowe

1. `email` jest jedynym polem obowiązkowym w MVP.
   Uzasadnienie: niższy próg wejścia i zgodność z obecnym kodem.

2. `name` pozostaje opcjonalne.
   Uzasadnienie: obecny `CreateLeadAction` ma fallback do email / company.

3. Zgoda nie blokuje zapisu leada w MVP.
   Uzasadnienie: obecny flow zapisuje `LeadConsent` tylko przy `consent = true`; to odzwierciedla stan repo i minimalny zakres zmian.

4. Tenant jest dziedziczony wyłącznie z landing page.
   Uzasadnienie: request publiczny nie może ufać polom `business_id` z klienta.

5. Deduplikacja ma być miękka, nie blokująca UX.
   Uzasadnienie: kolejne kliknięcie po sukcesie nie powinno kończyć się błędem dla visitora.

---

## 14. Luki i rekomendowane rozszerzenia po MVP

### HIGH

- ujednolicić źródło `page_url`: dziś część flow używa `Referer`, część `Origin`; warto przyjąć jeden standard,
- rozważyć maskowanie albo czyszczenie `ip_address` jobem retencyjnym, bo tabela nadal przechowuje raw IP,
- dopisać jawne testy dla `redirect_url` i success copy z sekcji formularza.

### MEDIUM

- dopisać frontendowy fokus na pierwsze pole z błędem,
- dodać obsługę `consent_required` per landing page,
- wystawić bardziej czytelny analytics event po submitcie formularza.

### LOW

- dodać opcjonalne pole `company` do publicznego UI,
- dodać konfigurację ukrywania `phone` / `message` per LP,
- dodać anti-bot timestamp challenge.

---

## 15. Checklist implementacji / weryfikacji

### Backend

- zachować endpoint `POST /lp/{slug}/submit`
- zachować `LeadCaptureRequest` jako kontrakt walidacji MVP
- zachować resolve wyłącznie dla `published` LP
- zachować propagację `business_id` i `landing_page_id`
- zachować `LeadCaptured` i downstream listeners
- zachować deduplikację i throttle

### Frontend

- renderować formularz w sekcji `form`
- obsłużyć states: idle / loading / success / error
- pokazywać błędy pod właściwymi polami
- obsłużyć `redirect_url` po sukcesie
- pobierać copy z ustawień sekcji lub tłumaczeń

### Testy akceptacyjne

- valid submit tworzy `Lead`, `Client`, `Contact`, `LeadSource`
- consent tworzy `LeadConsent`
- draft / archived page zwraca `404`
- honeypot blokuje request
- 4. request w oknie throttlingu zwraca `429`
- duplikat nie tworzy drugiego leada i nie podnosi `conversions_count`
- `LeadCaptured` odpala notyfikacje i automation context

---

## 16. Rekomendacja końcowa

Formularz lead capture dla landing pages nie wymaga obecnie osobnego, dużego redesignu backendu. Fundament domenowy jest już wdrożony i przetestowany. Najwłaściwszy kierunek to traktować ten moduł jako:

- stabilny publiczny entrypoint do CRM,
- cienką warstwę UX nad istniejącym `LeadService`,
- punkt integracji między `LandingPages`, `Leads` i `CRM`.

Jeżeli ten feature design zostanie zaakceptowany, kolejny etap powinien dotyczyć wyłącznie dopięcia braków produktowych i UX, a nie przepisywania podstawowego flow zapisu leada.