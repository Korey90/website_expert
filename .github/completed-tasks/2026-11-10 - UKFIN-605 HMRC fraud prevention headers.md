# UKFIN-605 — HMRC fraud prevention headers

**Status:** zakończone
**Data:** 2026-11-10
**Następny etap:** UKFIN-606 retrieve VAT obligations i synchronizacja periods

## Zakres

- Dodano builder wszystkich 16 wymaganych nagłówków dla `WEB_APP_VIA_SERVER`.
- Panel Filament zbiera nieautorytatywną telemetrię przeglądarki: trwały UUID urządzenia, JavaScript user agent, ekran, rozmiar okna i strefę czasową.
- Serwer dodaje i waliduje publiczny IP, timestamp pobrania, źródłowy port TCP, identyfikatory użytkownika, produkt, wersję, publiczny IP vendora i wielohopowy łańcuch TLS.
- Dla wdrożeń za reverse proxy/WAF dostępne są jawne nazwy nagłówków publicznego IP/portu; wolno ich użyć tylko wtedy, gdy zaufany edge usuwa wartości klienta i nadpisuje je danymi połączenia.
- Nagłówek MFA powstaje tylko po pomyślnej weryfikacji rzeczywistego kodu TOTP; stabilna referencja czynnika jest HMAC i nie ujawnia sekretu.
- Prawdziwy identyfikator licencji jest hashowany SHA-256. System nie generuje placeholderów.
- Wywołania VAT są blokowane przed HTTP, jeśli wymagany zestaw nagłówków jest niepełny.
- Jawny wyjątek brakującego nagłówka jest możliwy przez konfigurację wyłącznie po uzgodnieniu wyjątkowego przypadku z HMRC.
- Dodano Client Credentials Grant dla application-restricted API bez trwałego przechowywania application tokenu.
- Dodano sandboxowe wywołania `/test/fraud-prevention-headers/validate` i `/test/fraud-prevention-headers/vat-mtd/validation-feedback`; są blokowane w production.
- Panel `/admin/hmrc-connection` pozwala owner/admin uruchomić walidację po TOTP i pokazuje tylko bezpieczne podsumowanie bez wartości nagłówków.

## Walidacja

- Builder + validator + VAT fail-closed: 16 passed / 61 assertions.
- Targeted HMRC OAuth + fraud headers + VAT client + Filament permissions: 32 passed / 196 assertions.
- Finance: 295 passed / 1 skipped / 2159 assertions.
- Pełna regresja: 806 passed / 11 skipped / 3618 assertions; pozostało 9 znanych, niezwiązanych baseline failures.
- PL/EN/PT: identyczne 1106 kluczy liściowych.

## Zewnętrzny gate

Nie wykonano prawdziwego wywołania HMRC. Wymagane są subskrypcje API, sandbox Client ID/Secret, publiczne dane infrastruktury, rzeczywisty source port, wersja wdrożenia, MFA i decyzja w sprawie licencji. Jeśli instalacja nie ma prawdziwego identyfikatora licencyjnego, HMRC wymaga wcześniejszego kontaktu przed pominięciem nagłówka; placeholdery są niedozwolone.
