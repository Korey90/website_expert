# Current Task

**Status:** W toku — UKFIN-612 wdrożone na serwerze produkcyjnie równoważnym; oczekuje na ponowną walidację FPH i sandbox E2E
**Zadanie:** HMRC MTD VAT sandbox end-to-end
**Data rozpoczęcia:** 2026-11-10
**Plan nadrzędny:** `docs/plans/uk-finance-tax-module-plan.md`
**Ochrona równoległej pracy:** nie modyfikować `resources/js/Pages/Landing/PlStronyInternetowe.tsx`.

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
- ⛔ UKFIN-612 pozostaje formalnie otwarte: wymagany jest niezależny authenticated pentest oraz zamknięcie FPH source-port/licence i procedury HMRC production access.
- ➡️ Następna akcja zewnętrzna: ustawić w aplikacji HMRC sandbox callback `https://website-expert.uk/admin/hmrc/oauth/callback`, połączyć OAuth ponownie i uruchomić walidację FPH z urządzenia korzystającego z internetu mobilnego/hotspotu. Test z domowego Wi-Fi może przejść przez NAT hairpin i pokazać prywatny adres zamiast rzeczywistego klienta.

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
- Rzeczywisty validator FPH przez zrok zaakceptował 14/16 nagłówków. Bezpośrednie wdrożenie Apache/PHP-FPM powinno udostępnić `REMOTE_PORT`, ale musi to potwierdzić ponowny test z zewnętrznej sieci; `Gov-Vendor-License-IDs` może powstać wyłącznie z prawdziwego modelu licencji.
- Testowy VRN jest zapisany. Rzeczywiste obligations i operacje read-only oczekują na rozwiązanie/akceptację dwóch braków FPH.
- `php artisan hmrc:production-readiness` zwraca obecnie NOT READY/exit 1 zgodnie z projektem; dostęp i submission produkcyjne mają niezależne blokady.
- Audyty PHP i JavaScript są czyste; wymagany jest jeszcze niezależny authenticated pentest wdrożonego, produkcyjnie równoważnego środowiska.
- Żadne prawdziwe dane ani deklaracje nie są wysyłane na tym etapie.
- `HMRC_ENVIRONMENT` pozostaje `sandbox` do czasu przejścia OAuth, fraud headers, pełnych scenariuszy sandbox, security review i jawnej decyzji właściciela.
