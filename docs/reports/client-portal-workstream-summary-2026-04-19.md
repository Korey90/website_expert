# Podsumowanie prac — klient, portal i rozdzielenie modelu agencyjnego vs SaaS

> Data: 2026-04-19
> Zakres: pełny workstream dotyczący sposobu obsługi klienta w Website Expert, rozdzielenia klienta agencyjnego od klienta SaaS/self-service oraz wdrożenia zmian w portalu i provisioning.

---

## 1. Cel prac

Celem tego workstreamu było uporządkowanie tego, jak Website Expert obsługuje klienta na styku CRM, client portal i workspace SaaS.

W praktyce oznaczało to cztery etapy:

1. audyt obecnego modelu klienta i całego lifecycle,
2. opisanie docelowego modelu klienta agencyjnego i klienta SaaS/self-service,
3. przygotowanie planu refaktoryzacji i wdrożenia,
4. implementację pierwszych dwóch slice'ów zmian w kodzie wraz z walidacją.

---

## 2. Prace analityczne

### 2.1 Audyt obsługi klienta

Wykonano pełną analizę tego, jak projekt:

- pozyskuje leady i tworzy rekordy `Client`,
- prowadzi klienta przez CRM, pipeline, quotes, contracts i invoices,
- udostępnia client portal,
- łączy login `User` z rekordem `Client`,
- miesza klienta agencyjnego z klientem SaaS/self-service.

Najważniejsze ustalenia z audytu:

- `Client` pełnił jednocześnie rolę prospecta, customer record i punktu wejścia do portalu,
- `Business` był faktycznym rootem workspace SaaS,
- `User` był wspólną tożsamością logowania,
- klient agencyjny i klient SaaS trafiali do częściowo tego samego UX, ale bez domkniętych zasad dostępu,
- zaproszenia portalowe i provisioning nie były jeszcze spójne z modelem workspace access.

Artefakt analityczny:

- `docs/analysis/client-handling-analysis.md`

Poboczne artefakty wygenerowane do tego raportu:

- `docs/analysis/client-handling-analysis.html`
- `docs/analysis/client-handling-analysis.pdf`

### 2.2 Identyfikacja głównych ryzyk

Zidentyfikowano następujące ryzyka architektoniczne i produktowe:

- brak twardego rozdziału między klientem agencyjnym i SaaS,
- błędny URL w invite flow do portalu,
- brak jasnej reguły, kiedy portalowy użytkownik ma dostać `BusinessUser`,
- uzależnienie części portalu od `currentBusiness()` bez rozróżnienia trybu dostępu,
- ryzyko pokazywania growth tools użytkownikom portal-only,
- brak centralnej warstwy provisioning dla dostępu portalowego.

---

## 3. Prace architektoniczne

### 3.1 Rozpisanie modelu klienta agencyjnego i SaaS/self-service

Zdefiniowano dwa rozłączne modele klienta:

- **klient agencyjny**: obsługiwany CRM-first, z `Client` jako rootem relacji handlowej i delivery,
- **klient SaaS/self-service**: obsługiwany workspace-first, z `Business` jako rootem tenant/workspace.

Najważniejsze zasady docelowe:

- `Business` pozostaje jedynym tenant/workspace rootem,
- `Client` pozostaje customer master recordem dla CRM i delivery,
- `User` jest wspólną tożsamością logowania,
- `portal_user_id` jest tylko przejściowym modelem single-contact access,
- membership w `business_users` ma być nadawany jawnie, a nie jako automatyczny skutek invite do portalu.

### 3.2 Ujęcie decyzji w dokumentacji architektonicznej

Zaktualizowano dokument architektury o:

- model klienta agencyjnego,
- model klienta SaaS/self-service,
- reguły współistnienia obu modeli,
- ADR potwierdzający, że `Client` nie jest tenant rootem, a `Business` nie jest CRM customer recordem.

Artefakt architektoniczny:

- `docs/architecture/architecture-plan.md`

Najważniejsza decyzja ADR:

- `Client` i `Business` mają różne odpowiedzialności domenowe i nie mogą być dalej traktowane jako ten sam account root.

---

## 4. Plan wdrożenia i refaktoryzacji

Na podstawie audytu i decyzji architektonicznych przygotowano plan działania obejmujący:

- quick wins w invite flow i feature gating,
- rozdzielenie zasad dostępu `client portal` vs `workspace portal`,
- uporządkowanie membership i provisioning,
- dalszy kierunek dla multi-contact access i pełnego lifecycle klienta.

Artefakt planistyczny:

- `docs/plans/refactor-plan.md`

Plan został podzielony na cztery fazy:

1. stabilizacja wejścia i quick wins,
2. rozdzielenie zasad dostępu,
3. membership i provisioning,
4. docelowy portal i lifecycle.

---

## 5. Zaimplementowane zmiany w kodzie

### 5.1 Slice 1 — quick wins i stabilizacja portalu

W pierwszym wdrożonym slice'ie zrealizowano:

- naprawę invite/login flow do portalu,
- wprowadzenie shared `portal_capabilities` do Inertia props,
- ukrycie growth tools i sekcji business dla użytkowników bez workspace access,
- dodanie guardów dla ekranów wymagających `currentBusiness()`.

Najważniejsze zmiany techniczne:

- `app/Http/Middleware/HandleInertiaRequests.php`
- `resources/js/Layouts/PortalLayout.jsx`
- `app/Http/Controllers/Portal/BillingController.php`
- `app/Http/Controllers/Portal/BasePortalController.php`
- `app/Http/Controllers/Portal/LeadController.php`

Efekt:

- użytkownik portal-only nie widzi już sekcji workspace, które wcześniej prowadziły do błędnego lub pustego UX,
- billing i widok leadów zostały zabezpieczone przed wejściem użytkownika bez aktywnego workspace.

### 5.2 Slice 2 — rozdzielenie policy portalu

W drugim wdrożonym slice'ie rozdzielono polityki dostępu do client portal i workspace portal.

Dodane elementy:

- middleware `portal.client`,
- middleware `portal.workspace`,
- osobne zasady dostępu dla tras delivery i tras workspace.

Nowe pliki:

- `app/Http/Middleware/EnsurePortalClientAccess.php`
- `app/Http/Middleware/EnsurePortalWorkspaceAccess.php`

Zmiany integracyjne:

- `bootstrap/app.php`
- `routes/web.php`

Efekt:

- trasy client portal działają tylko dla użytkownika powiązanego z `Client` przez `portal_user_id`,
- trasy workspace działają tylko dla użytkownika mającego aktywny `currentBusiness()`,
- dashboard portalu pozostał wspólnym entry pointem, ale kolejne sekcje są już pilnowane na poziomie route/middleware.

### 5.3 Slice 3 — centralizacja provisioning i jawne membership rules

Najważniejszą zmianą backendową było wprowadzenie jednej centralnej warstwy provisioning:

- `app/Services/Account/PortalAccessService.php`

Serwis ten odpowiada za:

- utworzenie lub ponowne użycie `User`,
- przypisanie roli `client`,
- spięcie `Client` z `portal_user_id`,
- opcjonalne nadanie membership w `business_users`,
- blokadę konfliktu, gdy użytkownik ma już inny aktywny workspace,
- wysyłkę invite maila przez ujednolicony flow.

Zrefaktoryzowane wejścia do provisioning:

- `app/Automation/Actions/CreatePortalAccessAction.php`
- `app/Console/Commands/CreatePortalUser.php`
- `app/Filament/Resources/ClientResource/Pages/ViewClient.php`
- `app/Filament/Resources/AutomationRuleResource.php`

Nowa zasada domenowa wdrożona w kodzie:

- **portal access jest domyślnie portal-only**,
- **workspace access jest zawsze jawny i opcjonalny**.

To rozwiązało najważniejszy problem wcześniejszego modelu, w którym nie było jednoznaczne, czy invite do portalu ma też oznaczać membership w workspace.

### 5.4 Usprawnienia UX i komunikatów w portalu

Dodano obsługę flash komunikatów w dashboardzie portalu, tak aby użytkownik widział powód redirectu lub blokady dostępu.

Zmodyfikowany plik:

- `resources/js/Pages/Portal/Dashboard.jsx`

Efekt:

- użytkownik widzi komunikaty typu `error`, `warning` i `success`,
- redirecty z middleware przestały wyglądać jak ciche, niezrozumiałe cofnięcie do dashboardu.

---

## 6. Zakres zmienionych obszarów

### 6.1 Dokumentacja

- `docs/analysis/client-handling-analysis.md`
- `docs/analysis/client-handling-analysis.html`
- `docs/analysis/client-handling-analysis.pdf`
- `docs/architecture/architecture-plan.md`
- `docs/plans/refactor-plan.md`

### 6.2 Backend Laravel

- `app/Http/Middleware/HandleInertiaRequests.php`
- `app/Http/Middleware/EnsurePortalClientAccess.php`
- `app/Http/Middleware/EnsurePortalWorkspaceAccess.php`
- `app/Http/Controllers/Portal/BasePortalController.php`
- `app/Http/Controllers/Portal/BillingController.php`
- `app/Http/Controllers/Portal/LeadController.php`
- `app/Services/Account/PortalAccessService.php`
- `app/Automation/Actions/CreatePortalAccessAction.php`
- `app/Console/Commands/CreatePortalUser.php`
- `app/Filament/Resources/ClientResource/Pages/ViewClient.php`
- `app/Filament/Resources/AutomationRuleResource.php`
- `bootstrap/app.php`
- `routes/web.php`

### 6.3 Frontend React/Inertia

- `resources/js/Layouts/PortalLayout.jsx`
- `resources/js/Pages/Portal/Dashboard.jsx`

### 6.4 Testy regresyjne

Dodane testy:

- `tests/Feature/Portal/PortalBillingAccessTest.php`
- `tests/Feature/Portal/PortalLeadAccessTest.php`
- `tests/Feature/Portal/PortalClientAccessMiddlewareTest.php`
- `tests/Feature/Account/PortalAccessServiceTest.php`

Zakres pokrycia testami:

- blokada billing dla użytkownika portal-only,
- poprawny dostęp do billing dla workspace member,
- blokada workspace lead view dla użytkownika bez workspace,
- poprawny dostęp do client portal routes tylko dla użytkownika z `portal_user_id`,
- brak automatycznego `BusinessUser` dla portal-only,
- poprawne jawne nadanie membership w workspace,
- odrzucenie workspace access bez `client.business_id`,
- odrzucenie konfliktu dla użytkownika z innym aktywnym workspace.

---

## 7. Wyniki walidacji

Po wdrożeniu zmian wykonano walidację techniczną.

### 7.1 Testy backendowe

Wynik:

- **10 testów przeszło poprawnie**,
- **56 asercji przeszło poprawnie**.

W trakcie prac wystąpił jeden lokalny problem testowy:

- świeża baza testowa nie miała roli `client`,
- problem został naprawiony w setupie testu `PortalAccessServiceTest` przez jawne utworzenie roli.

### 7.2 Build frontendu

Wynik:

- `npm run build` zakończył się powodzeniem,
- build został wykonany ponownie po drobnym cleanupie klas Tailwind w dashboardzie,
- oba buildy zakończyły się poprawnie.

### 7.3 Kontrola problemów edytora

Wynik:

- brak istotnych błędów backendowych po wdrożeniu,
- wykryto jedynie drobne sugestie Tailwind, które zostały poprawione.

---

## 8. Efekt końcowy biznesowo-techniczny

Po zakończeniu tych prac Website Expert ma już znacznie czytelniejszy i bezpieczniejszy model dostępu do klienta.

Najważniejsze efekty:

- klient agencyjny i klient SaaS przestali być traktowani jak ten sam account root,
- portal-only nie oznacza już automatycznie workspace membership,
- workspace access jest nadawany świadomie i kontrolowanie,
- routing `/portal` został rozdzielony zgodnie z typem dostępu,
- provisioning został zcentralizowany i przestał być rozproszony po kilku entry pointach,
- frontend portalu przestał pokazywać sekcje nieadekwatne dla użytkownika bez workspace,
- invite i onboarding portalowy stały się spójniejsze z architekturą produktu.

---

## 9. Co pozostaje poza zakresem zamkniętym w tym workstreamie

Te prace przygotowały fundament, ale nie zamknęły jeszcze całego tematu.

Otwarte obszary na kolejne etapy:

1. audyt i ujednolicenie zasad dostępu poza `/portal`, szczególnie w innych obszarach workspace,
2. zaprojektowanie realnego workspace switchera dla użytkowników hybrydowych,
3. docelowy model multi-contact client portal zamiast pojedynczego `portal_user_id`,
4. dalsze rozdzielenie UX delivery portal vs self-service workspace,
5. rozbudowa lifecycle klienta o onboarding, customer success i account management.

---

## 10. Podsumowanie wykonawcze

Workstream został przeprowadzony od audytu do wdrożenia.

Zrealizowano:

- analizę obecnego modelu klienta,
- doprecyzowanie architektury domenowej,
- przygotowanie planu działania,
- wdrożenie quick wins,
- wdrożenie rozdzielenia policy portalu,
- wdrożenie centralnego provisioning service,
- testy regresyjne i walidację buildów.

Na tym etapie Website Expert ma już solidny fundament pod dalszy rozwój jako SaaS dla agencji i freelancerów, bez utrwalania błędnego założenia, że `Client` i `Business` są tym samym rootem domenowym.