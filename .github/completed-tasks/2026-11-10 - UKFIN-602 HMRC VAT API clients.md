# UKFIN-602 — HMRC VAT API clients

**Status:** zakończone
**Data:** 2026-11-10
**Następny etap:** UKFIN-603 OAuth connect/callback/refresh/revoke

## Zakres

- Dodano `HmrcClientInterface` oraz osobne implementacje dla sandbox i production.
- Obsłużono VAT obligations, view/submit return, liabilities i payments.
- Dodano wersjonowany nagłówek `Accept`, OAuth Bearer i kontrolowane nagłówki `Gov-*`.
- `Gov-Test-Scenario` jest dostępny wyłącznie w sandboxie.
- Submission nie ma automatycznego retry, aby nie ryzykować podwójnej wysyłki.
- VRN, period key i zakresy dat są walidowane przed requestem.
- Wyjątki są sanitizowane i nie ujawniają tokenów, pełnego VRN ani surowego body HMRC.
- Konfiguracja nie zawiera sekretów; wartości uwierzytelniające pochodzą wyłącznie ze środowiska.

## Konfiguracja zewnętrzna

UKFIN-601 pozostaje otwarty. Aplikacja w HMRC Developer Hub wymaga subskrypcji:

1. VAT (MTD) API v1.0,
2. Create Test User API v1.0,
3. Test Fraud Prevention Headers API v1.0.

Callback: `<APP_URL>/admin/hmrc/oauth/callback`.

## Walidacja

- HMRC client: 7 passed / 28 assertions.
- Finance: 276 passed / 1 skipped / 2036 assertions.
- Pełna regresja: 787 passed / 11 skipped / 3495 assertions.
- Pozostało 9 wcześniej znanych, niezwiązanych baseline failures; brak nowych awarii HMRC.

## Gate bezpieczeństwa

- Środowisko pozostaje `sandbox`.
- Brak prawdziwych danych i wysyłek deklaracji.
- Produkcja wymaga ukończenia OAuth, fraud-prevention headers, scenariuszy sandbox, security review i jawnej decyzji właściciela.
