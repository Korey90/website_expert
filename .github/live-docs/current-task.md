# Current Task

**Status:** W toku — sandbox VRN gotowy, oczekiwanie na wpisanie testowego numeru; następnie UKFIN-607
**Zadanie:** HMRC MTD VAT sandbox end-to-end
**Data rozpoczęcia:** 2026-11-10
**Plan nadrzędny:** `docs/plans/uk-finance-tax-module-plan.md`
**Ochrona równoległej pracy:** nie modyfikować `resources/js/Pages/Landing/PlStronyInternetowe.tsx` ani `package*.json`.

## Decyzja wykonawcza

Techniczny golden dataset UKFIN-511 jest gotowy, ale formalny sign-off księgowego zostaje świadomie przesunięty do czasu ukończenia pełnego HMRC sandbox end-to-end. Brak sign-off nie jest traktowany jako zgoda podatkowa i nie pozwala na produkcyjne wysyłanie deklaracji.

## Bieżący zakres

- OAuth sandbox został wykonany end-to-end przez publiczny callback zrok; połączenie i tokeny są zapisane.
- Test Fraud Prevention Headers API potwierdził 14/16 nagłówków; blokujący `Gov-Client-Public-Port` nie jest udostępniany przez zrok, a `Gov-Vendor-License-IDs` pozostaje ostrzeżeniem wymagającym prawdziwej licencji albo uzgodnienia z HMRC.
- Bieżące zadanie implementacyjne: bezpieczna konfiguracja testowego VRN per tenant/environment, audyt zmiany i ochrona wartości przed wyzerowaniem przy ponownym OAuth.
- Po zakończeniu użytkownik wpisze wyłącznie 9-cyfrowy VRN wygenerowanego testowego użytkownika organizacji; login i hasło HMRC nie trafiają do aplikacji ani chatu.
- ✅ Dodano tenantową akcję ustawienia sandboxowego VRN, szyfrowanie istniejącym castem, audyt aktora/czasu, formularz owner/admin oraz ochronę wartości przed wyzerowaniem podczas reconnect OAuth.
- ✅ Migracja `2026_11_11_000028` wykonana; tenant audit bez błędów; HMRC targeted 32/192, pełny Finance 310 passed + 1 skipped / 2233 assertions; EN/PL/PT po 1140 kluczy.
- Następny etap implementacyjny: `UKFIN-607` — bezpieczny odczyt VAT return, liabilities i payments oraz prezentacja w panelu.

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
- Rzeczywisty validator FPH działa i akceptuje 14/16 nagłówków; `Gov-Client-Public-Port` wymaga infrastruktury zachowującej source port, a `Gov-Vendor-License-IDs` prawdziwego modelu licencji albo uzgodnienia z HMRC.
- Rzeczywiste obligations oczekują na wpisanie wygenerowanego testowego VRN i rozwiązanie/akceptację dwóch braków FPH.
- Żadne prawdziwe dane ani deklaracje nie są wysyłane na tym etapie.
- `HMRC_ENVIRONMENT` pozostaje `sandbox` do czasu przejścia OAuth, fraud headers, pełnych scenariuszy sandbox, security review i jawnej decyzji właściciela.
