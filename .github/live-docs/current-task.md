# Current Task

**Zadanie:** UK Finance & Tax — fundament tenantów i konfiguracja księgowa
**Status:** 🟡 W realizacji
**Start:** 2026-11-04
**Plan:** `docs/plans/uk-finance-tax-module-plan.md`

## Cel bieżącego przyrostu

Zabezpieczyć istniejące rekordy finansowe przed wyciekiem cross-tenant i dostarczyć fundament konfiguracji UK accounting bez wykonywania jeszcze deklaracji ani wysyłki do HMRC.

## Wykonane

- [x] `business_id` + FK + indeks na contacts, projects, quotes, invoices i payments.
- [x] Backfill przez relacje klient/faktura bez przypisywania rekordów do przypadkowej firmy.
- [x] `BelongsToTenant` i relacja `business()` na pięciu modelach.
- [x] Jawne przekazanie tenanta w Actions, Filament, webhookach Stripe/PayU i jobie domenowym.
- [x] Komenda kontrolna `php artisan finance:audit-tenants` (`--json` dla CI/deploy).
- [x] Testy automatycznego przypisania oraz izolacji pełnego łańcucha finansowego.
- [x] Rejestr uprawnień finance: view/manage/approve/post/close/submit.
- [x] `AccountingProfile` z encrypted casts dla UTR/PAYE/accounts office reference.
- [x] Tenantowy `TaxCode`, zakres dat obowiązywania i sześć startowych kodów UK w PL/EN/PT.
- [x] Idempotentna `InitializeAccountingSetupAction` dla istniejących i nowych firm.
- [x] `business_id` oraz izolacja tenantów także na invoice/quote items.
- [x] Tax code i snapshot netto/stawka/VAT/brutto na pozycjach dokumentów.
- [x] Wspólny kalkulator mixed VAT dla quote/invoice z podziałem rabatu w pensach.
- [x] Tenant-scoped tax code select w formularzach Invoice/Quote (PL/EN/PT).
- [x] Threat model zapisany w `docs/security/uk-finance-threat-model.md`.
- [x] Accounting Profile dostępny w portalu dla aktywnego owner/admin.
- [x] Walidacja entity type, Companies House number, UTR, roku finansowego i danych VAT.
- [x] Poufne UTR/PAYE/accounts office refs są edytowalne bez odsyłania zapisanej wartości do przeglądarki.
- [x] Widok Accounting Profile i nawigacja przetłumaczone na PL/EN/PT.
- [x] Tax point/supply date oraz snapshot kursu do GBP ze źródłem i datą.
- [x] Kwoty netto/VAT/brutto w GBP na pozycjach oraz sumy GBP na invoice/quote.
- [x] VAT w GBP i tax point na PDF faktury walutowej.
- [x] Transakcyjne sekwencje invoice/quote per tenant, typ i rok z `lockForUpdate`.
- [x] Numer jest przydzielany przy zapisie; usunięto wszystkie ścieżki `count()+1` dla invoice/quote.
- [x] CreditNote/CreditNoteItem ze snapshotami VAT/GBP oraz numeracją `CN-*`.
- [x] Lifecycle credit note: draft → issued → partially allocated/allocated albo void.
- [x] Kontrola sumy korygowanej ilości i trwałe uwzględnienie alokacji w saldzie faktury.

## Walidacja wykonana

- `TenantIsolationTest`: zielony.
- Regresja workflow/portal/reports/currency/automation: 54 testy, 152 asercje — zielone.
- Finance foundation: 30 testów, 132 asercje — zielone.
- Pełna regresja: 532 passed, 9 znanych baseline failures, 10 skipped — brak nowych awarii (przed ostatnim przyrostem CreditNote; ten ma osobny zielony pakiet regresji).
- Frontend: Vitest 16/16 — zielone.
- Pint na zmienionych plikach — zielony.
- Accounting Profile: 8 testów, 44 asercje — zielone.
- Produkcyjny build Vite z nową stroną — zielony.

## Następne kroki

- [x] Uruchomić pełną regresję i `migrate:fresh --seed`.
- [x] Rozszerzyć invoice/quote items o tax code i niezmienny snapshot VAT.
- [x] Przenieść kalkulację VAT z nagłówka dokumentu na pozycje.
- [x] Dodać konfigurację Accounting Profile w UI z walidacją zależną od entity/VAT status.
- [x] Dodać VAT amount in GBP i snapshot kursu walutowego.
- [x] Dodać bezpieczne, współbieżne sekwencje numeracji dokumentów per tenant.
- [x] Dodać CreditNote + CreditNoteItem i lifecycle.
- [ ] Dodać panel i PDF credit notes oraz uzupełnić PDF invoice o pełne dane sprzedawcy/VAT.
- [ ] Wymusić NOT NULL dla tenantów dopiero po czystym audycie danych produkcyjnych.

## Blokady domenowe

- Wymagane potwierdzenie obsługiwanych entity types oraz VAT schemes.
- Domyślne mapowania tax codes muszą zostać zaakceptowane przez księgowego UK przed użyciem do deklaracji.
- HMRC submission pozostaje poza zakresem bieżącego przyrostu.
