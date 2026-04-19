# Debug Report — Landing Flow

Data: 2026-03-31
Środowisko: local
Zakres: AI generator -> zapis wariantu -> zapis landing page -> publikacja -> public routing -> formularz -> zapis leada -> CRM -> tenant isolation
Status: W trakcie — flow bazowy działa, ale wykryto kilka realnych bugów runtime i istotne luki testowe.

## 1. Przebieg flow

### AI generator
- Wejście: `POST /landing-pages/ai/generate`
- Kontroler: `AiLandingGeneratorController@generate`
- Serwis: `GenerateLandingService::generate()`
- Co się dzieje:
  - budowany jest kontekst z `BusinessProfileService`
  - tworzony jest rekord `LandingPageAiGeneration`
  - odpowiedź OpenAI przechodzi przez normalizer i validator JSON
  - zapisywany jest `LandingPageGenerationVariant`

Wniosek:
- Architektura jest poprawna: generacja ma osobny rekord audytowy, walidację i obsługę błędów domenowych.
- Nie znalazłem dedykowanych testów dla `AiLandingGeneratorController`, `GenerateLandingService` ani `LandingPageGenerationVariant`.

### Zapis wygenerowanej strony
- Wejście: `POST /landing-pages/ai/variants/{variant}/save`
- Serwis: `GenerateLandingService::saveAsLandingPage()`
- Finalny zapis: `LandingPageService::createFromGeneratedVariant()`
- Co się dzieje:
  - wariant przechodzi ownership check i status check (`is_saved`, `expires_at`)
  - payload jest ponownie walidowany
  - powstaje nowa `LandingPage` w statusie `draft`
  - sekcje z wariantu są zapisywane do `landing_page_sections`
  - wariant dostaje `is_saved = true`

Wniosek:
- Zapis draftu jest sensownie odseparowany od generacji.
- Brakuje testów dla edge case'ów zapisu: expired variant, second save, malformed sections, konflikt slugów przy save z AI.

### Publikacja
- Wejście: `POST /landing-pages/{landingPage}/publish`
- Serwis: `LandingPageService::publish()`
- Guard: `LandingPage::canBePublished()`
- Warunki publikacji:
  - strona musi mieć sekcje
  - strona musi mieć sekcję typu `form`

Wniosek:
- Publiczny runtime jest zabezpieczony minimalnym warunkiem publikacji.
- Testy publikacji i tenant access przechodzą.

### Public routing
- Runtime: `GET /lp/{slug}` -> `PublicLandingPageController@show`
- Public submit legacy: `POST /lp/{slug}/submit`
- Public submit aktualny: `POST /leads`
- Serwis wspólny: `PublicLeadCaptureService::captureBySlug()`

Wniosek:
- Publiczny routing jest spójny: oba endpointy submit schodzą do jednego serwisu.
- `PublicLandingPageService` poprawnie wpuszcza tylko `published` i ładuje tylko widoczne sekcje.

### Formularz
- Frontend: `resources/js/Components/LandingPage/PublicSection/FormSection.jsx`
- Aktualne zachowanie:
  - walidacja UI
  - loading state
  - success state
  - mapowanie błędów 422
  - submit do `POST /leads`

Wniosek:
- Podstawowy UX formularza działa poprawnie.
- W aktualnym runtime frontend nie forwarduje query string z publicznej strony do requestu leadowego.

### Zapis leada i CRM
- Orkiestracja: `PublicLeadCaptureService` -> `LeadService::createFromLandingPage()` -> `CreateLeadAction`
- Co jest robione:
  - deduplikacja per `email + landing_page + day`
  - zapis `Lead`, `Client`, `Contact`
  - zapis `LeadSource`
  - opcjonalnie `LeadConsent`
  - increment `conversions_count`
  - event `LeadCaptured`
  - downstream: `AutomationEventListener`, `NotifyLeadOwnerListener`

Wniosek:
- CRM pipeline po stronie backendu jest dobrze spięty i ma sensowne testy feature.

### Tenant isolation
- Middleware: `EnsureLandingPageTenantAccess`
- Dane przypisywane przez `business_id`
- Testy multi-tenant dla LP i leadów przechodzą

Wniosek:
- Tenant isolation w badanym flow jest generalnie poprawne.

## 2. Wynik uruchomionych testów

Uruchomione testy:
- `tests/Feature/LandingPage/LandingPagePublicationSystemTest.php`
- `tests/Feature/LandingPage/PublicLeadCaptureTest.php`
- `tests/Feature/Leads/PublicLeadCaptureEndpointTest.php`
- `tests/Feature/LandingPage/MultiTenantIsolationTest.php`
- `tests/Feature/LandingPage/LeadDeduplicationTest.php`

Wynik:
- `49 passed (86 assertions)`

Uwaga środowiskowa:
- przy uruchomieniu PHP pojawia się warning o `pdo_oci`; nie zablokował testów, ale zaśmieca output i utrudnia diagnozę realnych błędów.

## 3. Potwierdzone bugi

### BUG 1 — UTM z publicznego formularza nie są przenoszone do requestu `POST /leads`
Poziom: HIGH

Root cause:
- backend oczekuje UTM-ów w query stringu requestu POST
- frontend wysyła `axios.post('/leads', payload)` bez przekazania bieżącego query stringa strony publicznej

Dowody:
- `LeadCaptureController` czyta `utm_source`, `utm_medium`, `utm_campaign`, `utm_content`, `utm_term` z `$request->query(...)`
- `FormSection.jsx` wysyła request na sztywny URL `/leads` bez `window.location.search`

Efekt:
- użytkownik wchodzi na LP np. z `?utm_source=google&utm_campaign=spring`
- po submit formularza lead zapisuje się bez atrybucji kampanii
- testy backendowe tego nie łapią, bo UTM są podawane bezpośrednio w requestach testowych, a nie przez realny frontend

Wpływ:
- CRM traci source attribution dla realnego ruchu kampanijnego
- automations i reporting działają na niepełnych danych

### BUG 2 — Dodatkowe pola formularza nie są zapisywane do `lead.form_data`
Poziom: HIGH

Root cause:
- publiczny flow przekazuje do `CreateLeadAction` surowe dane leada, ale nigdzie nie ustawia klucza `form_data`
- `CreateLeadAction` zapisuje `form_data` tylko wtedy, gdy dostanie je jawnie w `$data['form_data']`

Dowody:
- `PublicLeadCaptureService` przekazuje `$validated` do `LeadService::createFromLandingPage()`
- `LeadService::createFromLandingPage()` buduje `leadData`, ale nie ustawia `form_data`
- `CreateLeadAction` zapisuje `form_data => $data['form_data'] ?? null`

Efekt:
- pola typu `company`, własne pola z przyszłych wariantów formularza albo rozszerzone payloady nie są utrwalane w leadzie
- obecny test `test_form_data_stored_when_extra_fields_present()` jest mylący, bo sprawdza tylko `notes`, a nie `form_data`

Wpływ:
- utrata części danych leada w CRM
- fałszywe poczucie bezpieczeństwa przez nazwę testu

### BUG 3 — `lp_captured` activity nie dostaje danych UTM nawet gdy `LeadSource` je zapisze
Poziom: MEDIUM

Root cause:
- `LeadService::createFromLandingPage()` przy logowaniu aktywności czyta UTM z `$validated`, a nie z `$sourceData`
- UTM w tym flow są budowane z request context, nie z walidowanego payloadu formularza

Efekt:
- `lead_sources` ma UTM-y, ale aktywność CRM `lp_captured` ma puste metadata `utm`

Wpływ:
- timeline leada pokazuje uboższy kontekst niż realnie zapisany w systemie

### BUG 4 — Preview formularza w AI generatorze jest interaktywne i może wysyłać realne requesty na nieistniejący slug
Poziom: MEDIUM

Root cause:
- `AiLandingPreview` renderuje ten sam `FormSection`, którego używa publiczny runtime
- preview przekazuje `slug={variant.slug_suggestion ?? 'ai-draft'}`
- komponent formularza ma aktywny submit do `POST /leads`

Efekt:
- w generatorze AI można kliknąć submit w preview
- request pójdzie na realny endpoint, ale dla draftowego / nieistniejącego sluga
- użytkownik dostanie error formularza w miejscu, które powinno być tylko preview

Wpływ:
- mylący UX w generatorze AI
- niepotrzebny noise w requestach i logach

## 4. Ryzyka

### RYZYKO 1 — Brak testów dla AI generatora i save flow
Poziom: HIGH

Nie ma dedykowanego coverage dla:
- `AiLandingGeneratorController@generate`
- `AiLandingGeneratorController@regenerateSection`
- `AiLandingGeneratorController@save`
- `GenerateLandingService`
- `LandingPageGenerationVariant`

To oznacza, że najbardziej złożona część flow jest praktycznie niechroniona regresyjnie.

### RYZYKO 2 — `views_count` i `conversion_rate` mogą być zawyżone
Poziom: MEDIUM

`PublicLandingPageController@show` robi `increment('views_count')` przy każdym GET bez filtrowania botów, bez session dedupe i bez ochrony przed odświeżaniem.

Efekt:
- conversion rate może być zafałszowany przy crawlerach, preloadach, testach QA i wielokrotnych refreshach.

### RYZYKO 3 — Publiczny endpoint `/leads` jest bezpieczny dla runtime tej aplikacji, ale nie nadaje się do cross-domain embedów
Poziom: MEDIUM

`/leads` siedzi w `web.php`, więc działa w modelu sesja + CSRF tego samego runtime.

Efekt:
- dla tego projektu publiczne LP działają
- dla przyszłych custom domains, iframe embedów lub headless hostingu ten endpoint będzie problematyczny bez dodatkowej strategii CSRF/CORS

### RYZYKO 4 — Consent flow jest gotowy w backendzie, ale nieobecny w publicznym formularzu
Poziom: MEDIUM

Backend obsługuje `consent`, `LeadConsent` i tekst zgody, ale aktualny formularz publiczny nie renderuje checkboxa consent.

Efekt:
- przepływ consent istnieje tylko częściowo
- łatwo założyć, że jest gotowy end-to-end, mimo że frontend go nie używa

## 5. API errors i edge cases

### API errors
- `POST /leads`
  - `201` dla nowego leada
  - `200` dla duplikatu
  - `422` dla błędów walidacji
  - `404` dla draft/unpublished/not-found LP
  - `500` dla błędu nieobsłużonego
- `POST /api/v1/leads`
  - `201` dla poprawnego requestu tokenowego
  - walidacja oparta o `ApiLeadRequest`

Ocena:
- kody odpowiedzi są sensowne i spójne z użyciem.

### Sprawdzone edge cases
- draft LP -> 404
- archived LP -> 404
- missing email -> 422
- invalid phone -> 422
- honeypot -> 422
- duplicate within 24h -> brak drugiego leada
- same email na innym tenant -> osobny lead i osobny client

### Niepokryte edge cases
- klik submit w AI preview
- UTM przenoszone przez realny frontend, nie syntetyczny request testowy
- expired / already-saved AI variant
- błędny lub niepełny JSON zwrócony z OpenAI w warstwie integration test
- public `GET /lp/{slug}`: increment views_count i render sekcji po publikacji
- zachowanie formularza po redirect_url z query/campaign context

## 6. Brakujące testy

### Krytyczne brakujące testy
1. Feature test dla `AiLandingGeneratorController@generate` z zamockowanym klientem OpenAI
2. Feature test dla `AiLandingGeneratorController@save` obejmujący zapis wariantu do draft LP
3. Test na `variant_expired` i `variant_already_saved`
4. Frontend/E2E test potwierdzający, że formularz publiczny przenosi UTM do backendu
5. Test integracyjny sprawdzający zapis dodatkowych pól do `lead.form_data`
6. Test, że preview AI nie wykonuje realnego submitu albo jest renderowane w trybie disabled

### Dodatkowe brakujące testy
1. Test publicznego `GET /lp/{slug}` dla increment `views_count`
2. Test renderu published LP z sekcjami ordered + visible
3. Test na fallback błędu 500 w `POST /leads`
4. Test na redirect po sukcesie formularza z `redirect_url`

## 7. Krótkie podsumowanie

Flow backendowy od publikacji landing page do CRM jest w większości poprawnie spięty i ma dobre coverage dla publikacji, lead capture, deduplikacji i tenant isolation. Najsłabszy obszar to AI generator oraz styki frontend-backend: realny formularz gubi UTM-y, dodatkowe pola nie trafiają do `lead.form_data`, a preview AI potrafi wykonywać prawdziwy submit na niesaved slug.

Najważniejsze rzeczy do naprawy w pierwszej kolejności:
1. przenoszenie UTM przez realny frontend submit
2. zapis `form_data` w CRM
3. zablokowanie aktywnego submitu w AI preview
4. dołożenie testów dla AI generator/save flow