# Current Task

**Zadanie:** UKFIN-404 — reguły dopasowania transakcji bankowych
**Status:** 🟢 Zakończone
**Start:** 2026-11-04
**Plan:** `docs/plans/uk-finance-tax-module-plan.md`

## Cel bieżącego przyrostu

Dostarczyć deterministyczny, tenantowy scoring kandydatów dla transakcji bankowych na podstawie kierunku przepływu, kwoty, waluty, daty, referencji i kontrahenta, bez zapisywania uzgodnienia przed `UKFIN-405`.

## Delta Analysis: UKFIN-404

### Anchor files (reużywam)

- `app/Models/BankTransaction.php` — transakcja wejściowa, znak kwoty, waluta i status uzgodnienia.
- `app/Models/Payment.php` — wpłata klienta oraz pełny zwrot klientowi.
- `app/Models/ExpensePayment.php` — wypływ do dostawcy.
- `app/Models/PurchaseCreditRefund.php` — wpływ zwrotu od dostawcy.
- `app/Models/Invoice.php`, `Client.php`, `Expense.php`, `Supplier.php` — numery dokumentów i nazwy kontrahentów.
- `app/Support/PermissionHelper.php` — kontrola `view_finance`.

### Do stworzenia

- [x] Typowany kandydat z wynikiem 0–100, confidence i rozbiciem punktów.
- [x] Reguły kwoty, daty, referencji i kontrahenta z deterministycznym sortowaniem.
- [x] Obsługa wpłat, wypłat, zwrotów oraz porównania `amount_gbp` dla rachunku GBP.
- [x] Autoryzowana akcja wyszukiwania ograniczona do tenanta i statusu `unmatched`.
- [x] Testy exact/partial, kierunku, FX, progów, uprawnień i izolacji tenantów.

### Ryzyka i zależności

- Reguły nie mogą zwracać rekordów innego `business_id`, nawet przy identycznej kwocie i referencji.
- Kandydaci o przeciwnym kierunku przepływu są odrzucani przed scoringiem.
- Dla transakcji GBP można porównać zapisane `amount_gbp`; silnik nie pobiera ani nie przelicza nowych kursów.
- `UKFIN-404` tylko proponuje kandydatów; zapis split/partial/many-to-many i wykluczenie już zaalokowanych kwot należy do `UKFIN-405`.

## Wynik UKFIN-404

- [x] Cztery komponenty scoringu: amount 50, date 20, reference 20, counterparty 10.
- [x] Okno 30 dni, próg 40 i limit 50 kandydatów są konfigurowalne.
- [x] Kierunek inflow/outflow i brak nowego przeliczania FX są wymuszane przed scoringiem.
- [x] Testy UKFIN-404: 7 passed, 46 assertions.
- [x] Finance regression: 179 passed, 1 skipped, 984 assertions.
- [x] Full regression: 688 passed, 11 skipped, 9 znanych baseline failures, 2428 assertions.

## Następny krok

- [ ] `UKFIN-405` — model i UI reconcile exact/partial/split/many-to-many.

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
