# UKFIN-603/604 — HMRC OAuth and encrypted connection

**Status:** zakończone
**Data:** 2026-11-10
**Następny etap:** UKFIN-605 fraud-prevention headers builder + sandbox test endpoint

## Zakres

- Dodano HMRC OAuth 2.0 Authorization Code Grant z PKCE S256 i kryptograficznym `state`.
- Jednorazowy stan OAuth jest związany z aktywnym tenantem, użytkownikiem i środowiskiem oraz ma ograniczony czas życia.
- Callback bezpiecznie wymienia kod, a Client Secret nigdy nie trafia do authorization URL.
- Access token, rotowany single-use refresh token i VRN są szyfrowane w bazie oraz ukryte przez model.
- Refresh używa blokady rekordu, aby zapobiec równoległemu wykorzystaniu tego samego refresh tokenu.
- `invalid_grant` usuwa lokalne credentials i oznacza połączenie jako wymagające ponownej autoryzacji.
- Dodano tenantowy panel `/admin/hmrc-connection`; operacje zarządzania wymagają roli owner/admin aktywnego tenanta.
- Panel pokazuje tylko status, środowisko, zakresy, zamaskowany VRN i znaczniki czasu — nigdy tokeny ani pełny VRN.
- Błędy nie ujawniają sekretu klienta, authorization code, tokenów, pełnego VRN ani surowego body HMRC.
- Local disconnect czyści dane aplikacji. Zdalne cofnięcie zgody użytkownik wykonuje osobno w HMRC „Manage authorised applications”.

## Dane i migracja

- Dodano tenantową tabelę `hmrc_connections` z unikalnością per tenant/środowisko.
- Migracja `2026_11_10_000026_create_hmrc_connections_table` została wykonana na lokalnym MySQL.
- `finance:audit-tenants` obejmuje `hmrc_connections` i zakończył się bez nieprawidłowości.

## Walidacja

- OAuth flow: 10 passed / 88 assertions.
- Końcowy pakiet HMRC OAuth + VAT client + Filament permissions: 23 passed / 161 assertions.
- Finance: 286 passed / 1 skipped / 2124 assertions.
- Pełna regresja: 797 passed / 11 skipped / 3583 assertions.
- Pozostało 9 wcześniej znanych, niezwiązanych baseline failures; brak nowych awarii HMRC.
- PL/EN/PT: identyczne 1089 kluczy liściowych.

## Gate zewnętrzny

Nie wykonano rzeczywistej autoryzacji sandbox. UKFIN-601 pozostaje otwarty do czasu dodania trzech subskrypcji API, skonfigurowania credentials wyłącznie w środowisku oraz użycia organizacyjnego test usera. Produkcja pozostaje zablokowana.
