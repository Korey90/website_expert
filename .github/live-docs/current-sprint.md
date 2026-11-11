# Current Sprint

**Nazwa:** UK Finance & Tax — HMRC MTD VAT sandbox
**Data rozpoczęcia:** 2026-11-10
**Status:** 🟡 IN PROGRESS
**Źródło:** `docs/plans/uk-finance-tax-module-plan.md`

**Cel:** doprowadzić bezpieczny przepływ HMRC VAT MTD od OAuth sandbox przez obligations i kontrolowany submission do receipt oraz walidacji fraud-prevention headers.

## Backlog

### In Progress

- [ ] `UKFIN-611` [M] — sandbox scenarios: success, invalid VRN, expired token, duplicate, 5xx i timeout.

### To Do

- [ ] Formalny gate księgowego UK po sandbox end-to-end, przed produkcyjnym submission.

### Done

- [x] `UKFIN-610` [M] — append-only SHA-256 event chain oraz prywatny, integrity-checked PDF receipt po bezpośrednim HTTP 201.
- [x] `UKFIN-609` [M] — bezpieczne GET-only reconciliation `outcome_unknown`: pending open, nine-box match albo trwały conflict, z audytem każdej kontroli i bez ponownego POST.
- [x] `UKFIN-608` [M] — kontrolowany submit z locked snapshotu, dokładną deklaracją HMRC, świeżym TOTP, FPH, trwałą próbą przed POST, walidowanym szyfrowanym receipt oraz `outcome_unknown` bez automatycznego retry.
- [x] `UKFIN-607` [M] — bezpieczny read-only view VAT Return, liabilities i payments z walidacją odpowiedzi, tenant isolation, TOTP i FPH.
- [x] `UKFIN-601` [M] — aktywne API, credentials poza repo, publiczny callback i udany OAuth sandbox testowej organizacji.
- [x] `UKFIN-606` [M] — retrieve VAT obligations i tenantowa synchronizacja periods; rzeczywisty call oczekuje na testowy VRN oraz gate FPH.
- [x] `UKFIN-602` [M] — HmrcClientInterface + sandbox/production VAT API clients.
- [x] `UKFIN-603` [M] — OAuth connect/callback/refresh/local disconnect z PKCE S256, jednorazowym `state` i blokadą refresh race.
- [x] `UKFIN-604` [M] — szyfrowane tokeny/VRN, sanitizowane błędy i maskowany panel połączenia HMRC.
- [x] `UKFIN-605` [M] — 16 nagłówków `WEB_APP_VIA_SERVER`, fail-closed VAT client, TOTP, trusted-edge IP/port i sandbox Test Fraud Prevention Headers API.
- [x] `UKFIN-510` [M] — Accountant pack ZIP.
- [x] `UKFIN-509` [M] — VAT return review/lock/snapshot workflow.
- [x] `UKFIN-508` [M] — VAT adjustments z reason, attachment i approval.
- [x] `UKFIN-507` [M] — VatReturnCalculationAction dla 9 pól.
- [x] `UKFIN-506` [M] — VAT Detail: output/input VAT, tax codes i dokumenty źródłowe.
- [x] `UKFIN-505` [M] — Aged Receivables i Aged Payables.
- [x] `UKFIN-504` [M] — Cash Flow w wersji zarządczej.
- [x] `UKFIN-503` [M] — Balance Sheet.
- [x] `UKFIN-502` [M] — Profit & Loss.
- [x] `UKFIN-501` [M] — Trial Balance per period i comparison.
- [x] Faza 4 / `UKFIN-401–409` — bank reconciliation zamknięte testami przekrojowymi.

## Definition of Done

- Sandbox i production mają jawnie rozdzielone URL-e oraz credentials.
- OAuth ma `state`, PKCE, szyfrowane tokeny, single-use refresh locking i bezpieczne revoke.
- Wszystkie VAT API calls zawierają wymagane fraud-prevention headers i zachowują correlation ID.
- Submission odbywa się tylko z locked VAT Return snapshotu, po deklaracji i step-up confirmation.
- POST nie ma ślepego retry, a timeout/niejednoznaczny wynik prowadzi do reconciliation.
- Testy sandbox obejmują success, invalid VRN, expired token, duplicate, 5xx i timeout.
- Produkcja pozostaje zablokowana do security review, zgody właściciela i wymaganych zewnętrznych gate'ów.

## Poza zakresem tego sprintu

- Payroll/PAYE, Corporation Tax, Self Assessment i CIS.
- VAT Assist (MTD) i obsługa biur rachunkowych przez Agent Authorisation API.
- Automatyczne składanie deklaracji bez świadomego potwierdzenia użytkownika.
