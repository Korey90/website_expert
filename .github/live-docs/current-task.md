# Current Task

**Status:** Plan gotowy - oczekuje na rozpoczęcie implementacji
**Zadanie:** UKFIN-402R1 - import rzeczywistego CSV Revolut PL
**Data rozpoczęcia:** 2026-11-20
**Plan:** `docs/plans/bank-statement-csv-real-bank-import-fix-plan.md`
**Ochrona równoległej pracy:** nie modyfikować `resources/js/Pages/Landing/PlStronyInternetowe.tsx`.

## Bieżąca diagnoza

- Automatyczne mapowanie nie rozpoznaje polskich nagłówków `Data zrealizowania`, `Kwota`, `Waluta` i `Opis`.
- Po ręcznym mapowaniu wszystkie wiersze odpadają, ponieważ bank używa timestampu `Y-m-d H:i:s`, a parser przyjmuje tylko datę.
- Prywatny plik ma 7 wierszy, poprawny separator/kodowanie/limity i diagnostycznie daje 7/7 poprawnych wierszy po naprawie mapowania i daty.
- Dodatkowo wymagane są bezpieczna deduplikacja z timestampem, kontrola statusu/opłaty/waluty oraz użyteczne komunikaty bez ujawniania danych.
- `docs/private/` musi zostać dodany do `.gitignore` przed pracą z fixture'ami.

## Plan wykonania

1. Ochronić `docs/private/` przed Git.
2. Dodać failing tests na fikcyjnych danych o strukturze Revolut PL.
3. Dodać profil mapowania, timestamp i bezpieczną source identity.
4. Dodać kontrolę statusu, opłaty i zgodności waluty rachunku.
5. Poprawić diagnostykę UI i tłumaczenia PL/EN/PT.
6. Uruchomić targeted i Finance regression.
7. Potwierdzić prywatnym dry run wynik 7 valid / 0 failed / 0 writes.

---

## Poprzednie zakończone zadanie - RBAC panelu administracyjnego

## Delta Analysis: RBAC i tenant isolation panelu `/admin/*`

### Anchor files

- `app/Filament/Support/FilamentPermissionRegistry.php` — centralna mapa kluczy Resources/Pages.
- `app/Filament/Resources/BaseResource.php` i `app/Filament/Pages/BasePage.php` — wspólne bramki Filament.
- `app/Traits/BelongsToTenant.php` + `app/Scopes/BusinessScope.php` — izolacja aktywnego workspace'u.
- `tests/Feature/Filament/FilamentPermissionAccessTest.php` — istniejący kontrakt ról i permissions.

### Wykryte luki

- [x] 7 Resources i 5 Pages nie miało wpisu w centralnym rejestrze uprawnień.
- [x] Brak wpisu w rejestrze działał fail-open w `BaseResource` i `BasePage`.
- [x] `SearchRankingCheckResource` omijał `BaseResource`, a model z `business_id` nie miał tenant scope.
- [x] Domain/DomainOrder zdejmowały tenant scope każdemu użytkownikowi panelu zamiast wyłącznie uprawnionemu operatorowi cross-tenant.
- [x] część własnych tras `/admin/*` miała tylko `auth`, bez jawnego klucza dostępu.
- [x] dashboard i topbar pokazywały widgety/akcje/dane bez dopasowania do permissions.
- [x] ręczne pobieranie szablonów briefingów i ofert po ID omijało `forBusiness()`.

### Plan wykonania

1. [x] Uzupełnić i zamknąć fail-closed rejestr Resources/Pages.
2. [x] Dodać brakujące klucze domen/kalendarza do seedera oraz grup edytora ról.
3. [x] Wymusić permission middleware na niestandardowych trasach admina.
4. [x] Naprawić izolację Search Ranking, domen, topbara i wyboru szablonów.
5. [x] Dopasować dashboard/widgety/quick actions do ról.
6. [x] Dodać automatyczny audyt pokrycia oraz testy cross-tenant i bezpośrednich URL-i.
7. [x] Uruchomić Pint, testy Filament/tenant oraz pełną regresję proporcjonalną do zmian.

### Wynik końcowy

- 56/56 Resources i 24/24 Pages ma jawny, istniejący klucz dostępu; brak mapowania kończy się odmową.
- Menu jest zgodne z rolami: panel-only nie widzi modułów, `admin` widzi komplet, a `manager` nie ma uprawnień operatora platformy.
- Tenant scope obejmuje listy, rekordy, ręczne selektory użytkowników, powiadomienia, widgety, topbar i niestandardowe trasy/eksporty.
- Migracja `2026_11_13_000033` aktualizuje istniejące role i dodaje klucze kalendarza/domen.
- Audyt kontraktowy: 8 testów / 1140 asercji; Filament targeted: 14 testów / 1185 asercji; eksporty i polityki: 44 testy / 74 asercje.
- Pełny przebieg wykrył 24 testy wymagające dostosowania do nowych bramek — wszystkie przechodzą po poprawie fixture'ów. Pozostało 6 potwierdzonych błędów baseline niezwiązanych z tym zadaniem: social OAuth redirect (1), homepage meta (3), portal billing currency (1), ServicePage Inertia shape (1).
- Wdrożono commit `ef15028` na `website-expert.uk`; migracja batch 11 wykonana, cache odbudowany, kolejka zrestartowana, role zweryfikowane i HTTP smoke test `200/302/302` zakończony poprawnie. Zweryfikowana kopia przedwdrożeniowa: `storage/app/deploy-backups/20261113-rbac-ef15028-v2`.

## Decyzja wykonawcza

Techniczny golden dataset UKFIN-511 jest gotowy, ale formalny sign-off księgowego zostaje świadomie przesunięty do czasu ukończenia pełnego HMRC sandbox end-to-end. Brak sign-off nie jest traktowany jako zgoda podatkowa i nie pozwala na produkcyjne wysyłanie deklaracji.

## Bieżący zakres

- OAuth sandbox został wykonany end-to-end przez publiczny callback zrok; po wdrożeniu na docelowy host połączenie musi zostać ponowione przez callback `https://website-expert.uk/admin/hmrc/oauth/callback`.
- Historyczny test przez zrok potwierdził 14/16 nagłówków. Aplikacja działa teraz bezpośrednio za Apache/PHP-FPM, więc Laravel może odczytać publiczny adres i źródłowy port TCP z połączenia; wymaga to potwierdzenia testem spoza lokalnej sieci. `Gov-Vendor-License-IDs` pozostaje ostrzeżeniem i nie wolno go uzupełniać fikcyjną wartością.
- Testowy VRN został wpisany przez użytkownika; system potwierdził wyłącznie obecność, maskę ostatnich 4 cyfr oraz metadane audytu bez ujawnienia pełnej wartości.
- ✅ Dodano tenantową akcję ustawienia sandboxowego VRN, szyfrowanie istniejącym castem, audyt aktora/czasu, formularz owner/admin oraz ochronę wartości przed wyzerowaniem podczas reconnect OAuth.
- ✅ Migracja `2026_11_11_000028` wykonana; tenant audit bez błędów; HMRC targeted 32/192, pełny Finance 310 passed + 1 skipped / 2233 assertions; EN/PL/PT po 1140 kluczy.
- ✅ `UKFIN-607`: dodano read-only VAT Return, liabilities i payments z auto-refresh tokenu, `read:vat`, tenant isolation, TOTP + FPH, ścisłą walidacją odpowiedzi i efemerycznym widokiem bez period key oraz surowych payloadów.
- ✅ Oficjalny kontrakt HMRC został ponownie sprawdzony; parser dopuszcza opcjonalne `outstandingAmount`, `due` i payment `received`, pozostając fail-closed dla błędnych typów/dat/kwot.
- ✅ UKFIN-607 validation: HMRC 50 passed / 284 assertions; Finance 317 passed + 1 skipped / 2261 assertions; EN/PL/PT po 1171 zgodnych kluczy; Blade cache i Pint poprawne.
- ✅ `UKFIN-608`: kontrolowany POST VAT Return wyłącznie z integralnego locked snapshotu i pasującego open obligation; dokładna deklaracja prawna HMRC, świeże TOTP, `submit_tax_returns`, pełne FPH i `write:vat` są wymagane przed wysyłką.
- ✅ Próba jest utrwalana przed POST z hashami snapshotu/payloadu i audytem; receipt HTTP 201 jest walidowany, identyfikatory są szyfrowane, a timeout/5xx/nieprawidłowa odpowiedź daje `outcome_unknown` bez automatycznego retry.
- ✅ Migracja `2026_11_11_000029` wykonana; tenant audit valid; HMRC 55/319, Finance 322 passed + 1 skipped / 2296 assertions; EN/PL/PT po 1197 zgodnych kluczy; Blade cache i Pint poprawne.
- ✅ `UKFIN-609`: próby `outcome_unknown` są uzgadniane wyłącznie przez GET obligations i GET return; open pozostaje pending, fulfilled wymaga zgodności wszystkich dziewięciu pól, a różnica daje trwały `outcome_conflict` bez ponownego POST.
- ✅ Każda kontrola zapisuje aktora, czas, licznik, HMRC received date, bezpieczne metadane błędu i correlation ID; snapshot/payload hash są ponownie sprawdzane przed połączeniem.
- ✅ Migracja `2026_11_11_000030` wykonana; tenant audit valid; HMRC 60/354, Finance 327 passed + 1 skipped / 2331 assertions; EN/PL/PT po 1219 zgodnych kluczy; Blade cache i Pint poprawne.
- ✅ `UKFIN-610`: append-only event log obejmuje prepared/submitted/outcome_unknown, reconciliation pending/matched/conflict/failed oraz receipt generation; zdarzenia tworzą weryfikowany łańcuch SHA-256 i nie przechowują tokenów, pełnego VRN, MFA, period key ani raw response.
- ✅ Prywatny PDF receipt jest dostępny tylko po bezpośrednim HTTP 201 z pełnym receipt i `submit_tax_returns`; weryfikuje snapshot, payload i audit chain, ma `no-store`, SHA-256 oraz jasne oznaczenie, że jest dowodem systemowym, a nie dokumentem wystawionym przez HMRC.
- ✅ Migracja `2026_11_11_000031` wykonana; tenant audit valid; HMRC 63/384, Finance 330 passed + 1 skipped / 2361 assertions; EN/PL/PT po 1240 zgodnych kluczy; Blade cache i Pint poprawne.
- ✅ `UKFIN-611`: deterministyczna macierz workflow obejmuje success, invalid VRN, expired token, duplicate, 5xx i timeout bez prawdziwego submission; submit scenario ma ścisły allow-list zgodny z endpointem.
- ✅ Jednoznaczne 4xx są audytowane jako `rejected`; duplikat udostępnia wyłącznie GET-only reconciliation, a 5xx/timeout pozostają `outcome_unknown`. Wygasły token jest rotowany atomowo przed dokładnie jednym POST.
- ✅ HMRC targeted 66/396; Finance 335 passed + 1 skipped / 2404 assertions; EN/PL/PT po 1242 zgodne klucze; live success/4xx pozostają celowo zablokowane do zamknięcia gate'u FPH source port/licence.
- ✅ `UKFIN-612` technical controls: exact official endpoint/callback guard, osobne fail-closed production access/submission switches, approval references i bezsekretowy `hmrc:production-readiness` z exit code 1 przy każdym niespełnionym warunku.
- ✅ PHP dependency audit oczyszczony z 11 advisory (`composer audit` = 0); tracked-secret scan bez trafień; checklista i evidence gate są w `docs/integrations/hmrc-production-readiness.md`.
- ✅ HMRC targeted 74/421; Finance 343 passed + 1 skipped / 2429 assertions; full regression 853 passed / 9 znanych baseline failures / 11 skipped / 3885 assertions, wykonany przed finalnym pozytywnym testem gate'u, który przeszedł osobno; EN/PL/PT po 1249 kluczy; tenant audit i Blade poprawne.
- ✅ Audyt JavaScript oczyszczony: DOMPurify `3.4.17`, postcss-selector-parser `6.1.5` oraz poprawione zależności narzędziowe; pełny `npm audit` = 0, czyste `npm ci`, frontend 16/16, lint i produkcyjny build przechodzą.
- ✅ Dodano tenantowy, append-only łańcuch dowodów sandbox dla walidacji/feedbacku FPH, obligations, view return, liabilities i payments. Zapisuje tylko statusy, czasy, bezpieczne correlation ID i ograniczone liczniki; tokeny, VRN, period key, nagłówki, telemetry i kwoty są wykluczone.
- ✅ `hmrc:export-sandbox-evidence` weryfikuje łańcuch dowodów i istniejące łańcuchy submission, po czym tworzy prywatny zredagowany JSON oraz plik `.sha256`. Migracja `000032` wykonana, tenant audit bez błędów; HMRC 82/489; Finance 348 passed + 1 skipped / 2462 assertions; PL/EN/PT po 1254 klucze; Blade i ESLint poprawne.
- ✅ Live evidence 2026-11-11: łańcuch 4/4 poprawny. Najnowszy preflight HMRC zwrócił `INVALID_HEADERS` według specyfikacji 3.3 (1 error: brak `Gov-Client-Public-Port`; 1 warning: brak `Gov-Vendor-License-IDs`). Dwa odczyty feedbacku zakończyły się poprawnie, ale HMRC zwrócił pustą listę (`request_count=0`). Zredagowany eksport: `hmrc-sandbox-evidence-20261111-200359.json`, file SHA-256 `529a113bb66eefec48a85c1ddd2dae58d69b7f9d104ea8a74ae18d397801dcec`.
- ✅ Release `c8a23a3` wdrożony 2026-11-11 do `/var/www/website-expert.uk`: 32 migracje finansowe/HMRC wykonane, tenant audit `invalid_count=0`, produkcyjny build klienta i SSR aktywne, queue worker działa, `APP_DEBUG=false`, Composer/NPM audit = 0. Utworzono oddzielne kopie kodu, bazy i `.env`; produkcyjne przełączniki HMRC pozostają wyłączone.
- ✅ Docelowa topologia FPH to bezpośredni Apache/PHP-FPM bez zrok i reverse proxy. `HMRC_VENDOR_PUBLIC_IP` oraz forwarding chain wskazują publiczny adres serwera, a `HMRC_PRODUCT_VERSION=c8a23a3`; publiczny port klienta jest pobierany z `REMOTE_PORT`.
- ✅ Ponowna walidacja przez hotspot 2026-11-11: `POTENTIALLY_INVALID_HEADERS`, spec 3.3, `errors=0`, `warnings=1`; publiczny IP, timestamp, source port i forwarding chain zostały zaakceptowane. Jedynym ostrzeżeniem jest brak `Gov-Vendor-License-IDs`; feedback został pobrany poprawnie.
- ✅ Dodano ograniczony przełącznik `HMRC_SANDBOX_ALLOW_MISSING_VENDOR_LICENSE`: pozwala wyłącznie sandboxowi pominąć ten jeden nagłówek po zaobserwowanym wyniku Test API. Test bezpieczeństwa potwierdza, że produkcja nadal odrzuca brak licencji przed HTTP.
- ✅ Rzeczywisty obligations sync: 2 odebrane (1 fulfilled, 1 open), pierwsza synchronizacja 2 created, ponowna idempotentnie 2 unchanged; evidence chain poprawny. Próba View dla przykładowego fulfilled okresu zwróciła oczekiwane 404, ponieważ sandbox zwraca wyłącznie deklaracje wcześniej przesłane. Dodano bezpieczny, lokalizowany komunikat bez ujawniania `periodKey`.
- ✅ Pierwszy odczyt liabilities/payments ujawnił kontraktowy limit `from >= 2017-12-01`; błędny zakres 2017 Q1 został odrzucony przez HMRC 400. Dodano lokalną walidację min/max/366 dni przed HTTP. Po drugim 400 ustalono, że modal zachował bieżące daty niepasujące do scenariuszy; wybrano kompatybilną parę `SINGLE_LIABILITY_2018_19` / `SINGLE_PAYMENT_2018_19`, a modal automatycznie ustawia wspólny zakres `2018-01-02`–`2018-02-02`.
- ✅ Live liabilities/payments zakończone sukcesem: pobrano jedną testową liability i jedną payment z osobnymi correlation IDs, bez tworzenia księgowań. Evidence chain 9/9 poprawny; prywatny eksport `hmrc-sandbox-evidence-20261111-214530.json`, file SHA-256 `ab210110fdc9900fbd39645d6f2e3743b08001177d58de74a6c7b01b4fc17d05`, payload SHA-256 `8bfc4c27a51873e7c137a6ccef524cae76a009babdd96da6e8c86df7e33c882f`.
- ⛔ UKFIN-612 pozostaje formalnie otwarte: wymagany jest niezależny authenticated pentest oraz zamknięcie FPH source-port/licence i procedury HMRC production access.
- ➡️ Następna akcja: przygotować izolowany profil/tenant HMRC sandbox z testowym VRN i zerowym snapshotem dla otwartego okresu. Nie wolno wpisywać testowego VRN ani sztucznej deklaracji do rzeczywistego profilu księgowego Website Expert NI.

- `UKFIN-601`: użytkownik posiada konto HMRC Developer Hub i pierwszą aplikację sandbox.
- Do aplikacji trzeba dodać: VAT (MTD) API v1.0, Create Test User API v1.0 i Test Fraud Prevention Headers API v1.0.
- Callback planowany przez system: `<APP_URL>/admin/hmrc/oauth/callback`.
- Sekrety będą podawane wyłącznie przez zmienne środowiskowe i nie mogą trafić do Git, logów ani chatu.
- `UKFIN-602`: ukończony adapter VAT API z klientami sandbox/production.
- `UKFIN-603/604`: ukończony OAuth connect/callback/refresh/local disconnect oraz szyfrowane przechowywanie połączenia.
- `UKFIN-605`: ukończony builder nagłówków Fraud Prevention i klient sandboxowego Test API.
- `UKFIN-606` jest zaimplementowany; rzeczywiste pobranie obligations oczekuje na testowy VRN oraz zaakceptowaną ścieżkę brakujących FPH.

## Zrealizowane w UKFIN-602

- ✅ `HmrcClientInterface` i jawne implementacje sandbox/production.
- ✅ Operacje: obligations, submit/view return, liabilities i payments.
- ✅ `Accept: application/vnd.hmrc.1.0+json`, OAuth Bearer oraz kontrolowane `Gov-*`/`Gov-Test-Scenario`.
- ✅ `Gov-Test-Scenario` jest blokowany w production.
- ✅ Brak automatycznego retry POST submission.
- ✅ Walidacja VRN, period key i zakresów dat przed requestem.
- ✅ Sanitizowane wyjątki nie ujawniają access tokenu, pełnego VRN ani surowego body HMRC.
- ✅ Konfiguracja środowiskowa i dokument `docs/integrations/hmrc-mtd-vat-sandbox.md`.
- ✅ Dedykowane: 7 passed / 28 assertions.
- ✅ Finance regression: 276 passed / 1 skipped / 2036 assertions.
- ✅ Full regression: 787 passed / 9 znanych baseline failures / 11 skipped / 3495 assertions; brak nowych awarii HMRC.

## Zrealizowane w UKFIN-603/604

- ✅ Authorization Code Grant z kryptograficznym `state` i PKCE S256; jednorazowy stan jest związany z tenantem, użytkownikiem i środowiskiem oraz ma ograniczony czas życia.
- ✅ Callback wymienia kod bez ujawniania Client Secret w URL; błędy są sanitizowane i nie zawierają kodu, tokenów, sekretu ani surowej odpowiedzi HMRC.
- ✅ Access token, rotowany single-use refresh token i VRN są szyfrowane przez model; żaden token ani pełny VRN nie jest prezentowany w panelu.
- ✅ Refresh jest serializowany blokadą rekordu; `invalid_grant` usuwa lokalne tokeny i wymusza ponowne połączenie.
- ✅ Panel `/admin/hmrc-connection` oraz endpointy zarządzania są tenantowe i dostępne do zarządzania tylko właścicielowi/adminowi aktywnego tenanta.
- ✅ „Disconnect” usuwa wyłącznie lokalne tokeny. Cofnięcie autoryzacji po stronie HMRC wymaga użycia strony HMRC „Manage authorised applications”.
- ✅ Migracja MySQL `2026_11_10_000026_create_hmrc_connections_table` została wykonana; tenant audit nie wykrył nieprawidłowości.
- ✅ OAuth: 10 passed / 88 assertions; końcowy pakiet HMRC OAuth + VAT client + Filament permissions: 23 passed / 161 assertions; Finance: 286 passed / 1 skipped / 2124 assertions.
- ✅ Full regression: 797 passed / 9 znanych baseline failures / 11 skipped / 3583 assertions; brak nowych awarii HMRC.

## Zrealizowane w UKFIN-605

- ✅ Builder kompletnego zestawu 16 nagłówków `WEB_APP_VIA_SERVER`, z kodowaniem struktur zgodnym ze specyfikacją HMRC.
- ✅ Dane przeglądarki są zbierane lokalnie: trwały UUID urządzenia, JavaScript user agent, ekran, rozmiar okna i strefa czasowa; cookie jest nieautorytatywną telemetrią, nie mechanizmem bezpieczeństwa.
- ✅ Publiczny IP, port źródłowy, timestamp, użytkownik, produkt, wersja, publiczny IP vendora i pełny łańcuch terminacji TLS są walidowane po stronie serwera.
- ✅ Nagłówek MFA powstaje wyłącznie po rzeczywistym sprawdzeniu kodu TOTP; sekret ani kod nie trafiają do nagłówków lub logów.
- ✅ Identyfikator licencji jest hashowany SHA-256. Brak nagłówka można dopuścić tylko przez jawny wyjątek uzgodniony wcześniej z HMRC — placeholdery są zabronione.
- ✅ Wszystkie wywołania VAT działają fail-closed i odrzucają niepełny zestaw nagłówków przed HTTP.
- ✅ Klient Test Fraud Prevention Headers API używa osobnego, nietrwałego tokenu `client_credentials`, obsługuje `/validate` i feedback `vat-mtd`, działa tylko w sandboxie i sanitizuje błędy.
- ✅ Panel `/admin/hmrc-connection` ma akcję walidacji z TOTP; pokazuje wyłącznie wynik, liczniki błędów/ostrzeżeń, wersję specyfikacji i correlation ID — bez wartości nagłówków.
- ✅ Targeted HMRC + Filament: 32 passed / 196 assertions; Finance: 295 passed / 1 skipped / 2159 assertions; PL/EN/PT po 1106 kluczy.
- ✅ Full regression: 806 passed / 9 znanych baseline failures / 11 skipped / 3618 assertions; brak nowych awarii HMRC.

## Gate

- UKFIN-601 jest zakończony: trzy API są aktywne, credentials skonfigurowane, a OAuth testowej organizacji przeszedł end-to-end.
- Rzeczywisty validator FPH przez bezpośredni Apache i hotspot zaakceptował wszystkie dane sieciowe: 0 błędów, 1 ostrzeżenie dotyczące wyłącznie braku prawdziwej licencji na urządzeniu użytkownika.
- Testowy VRN jest zapisany. Rzeczywiste obligations i operacje read-only oczekują na rozwiązanie/akceptację dwóch braków FPH.
- `php artisan hmrc:production-readiness` zwraca obecnie NOT READY/exit 1 zgodnie z projektem; dostęp i submission produkcyjne mają niezależne blokady.
- Audyty PHP i JavaScript są czyste; wymagany jest jeszcze niezależny authenticated pentest wdrożonego, produkcyjnie równoważnego środowiska.
- Żadne prawdziwe dane ani deklaracje nie są wysyłane na tym etapie.
- `HMRC_ENVIRONMENT` pozostaje `sandbox` do czasu przejścia OAuth, fraud headers, pełnych scenariuszy sandbox, security review i jawnej decyzji właściciela.
