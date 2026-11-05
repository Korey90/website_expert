# Current Task

**Zadanie:** UKFIN-405 — UI i domena uzgodnień exact/partial/split/many-to-many
**Status:** 🟢 Zakończone
**Start:** 2026-11-05
**Plan nadrzędny:** `docs/plans/uk-finance-tax-module-plan.md`

## Cel bieżącego przyrostu

Umożliwić bezpieczne przypisanie jednej lub wielu transakcji bankowych do jednej lub wielu istniejących wpłat i wypłat, z kontrolą pozostałych kwot, tenantów, kierunku i waluty.

## Delta Analysis: UKFIN-405

### Anchor files (reużywam)

- `app/Models/BankTransaction.php` — tenantowa transakcja oraz status reconciliation.
- `app/Services/Finance/BankTransactionMatchingService.php` — kandydaci i kwoty porównywalne z UKFIN-404.
- `app/Actions/Finance/FindBankTransactionMatchesAction.php` — membership, RBAC i dopuszczalne statusy.
- `app/Models/Payment.php`, `ExpensePayment.php`, `PurchaseCreditRefund.php` — źródła wpływów i wypływów.
- `app/Filament/Resources/BankAccountResource` — wzorzec UI Finance i importu CSV.
- `tests/Feature/Finance/BankTransactionMatchingTest.php` — fixture oraz przypadki kierunku, FX i izolacji.

### Do stworzenia

- [x] Tenantowe modele i tabele reconciliation batch + polymorphic allocations.
- [x] Action transakcyjna z blokadami i ochroną przed over-allocation.
- [x] Obsługa exact, partial, split i many-to-many wraz z pozostałymi kwotami.
- [x] `BankTransactionResource` z listą, szczegółami, filtrem statusu i modalem reconcile.
- [x] Policy i rejestr uprawnień `view_finance` / `manage_finance`.
- [x] Tłumaczenia PL/EN/PT.
- [x] Testy domenowe, UI, tenant isolation, RBAC, FX i współbieżnej integralności.

### Ryzyka i granice

- Kwoty są zapisywane dodatnio; kierunek wynika z transakcji oraz typu zdarzenia.
- Alokacja nie może przekroczyć reszty transakcji ani reszty dokumentu źródłowego.
- Dla banku GBP i źródła walutowego używany jest istniejący, niezmienny snapshot `amount_gbp`.
- Undo/reversal oraz tworzenie nowego expense/payment pozostają odpowiednio w UKFIN-407 i UKFIN-406.
- Wszystkie odczyty i zapisy muszą być ograniczone do jednego `business_id`.

## Walidacja planowana

- [x] Migracje na SQLite i MySQL-compatible schema.
- [x] PHPUnit: exact, partial, split, many-to-many, FX, over-allocation, RBAC i tenant isolation.
- [x] Test dostępu do Filament resource i widoku.
- [x] Pint, translation parity, `git diff --check` i regresja Finance.

## Wynik UKFIN-405

- [x] Uzgodnienia zapisują audytowalny batch i osobne alokacje do payment/refund.
- [x] Blokady transakcji i źródeł oraz ponowne przeliczenie reszt chronią przed over-allocation.
- [x] Statusy `unmatched`, `partially_reconciled` i `reconciled` odzwierciedlają pozostałą kwotę.
- [x] Filament `/admin/bank-transactions` udostępnia listę, filtry, szczegóły i modal split.
- [x] Audyt tenantów sprawdza oba nowe modele, transakcję batcha i źródło polymorficzne.
- [x] Nazwa firmy została ujednolicona w bazie, seederach, UI i dokumentacji do `Website Expert NI`.
- [x] Testy UKFIN-404/405: 15 passed, 92 assertions.
- [x] Finance regression: 188 passed, 1 skipped, 1162 assertions.
- [x] Full regression: 698 passed, 11 skipped, 9 znanych baseline failures, 2609 assertions.

## Następny krok

- [ ] `UKFIN-406` — utworzenie expense/payment z nieuzgodnionej transakcji bankowej.

---

# Poprzednie zadanie — pakiet demonstracyjnych PDF

**Zadanie:** Pakiet demonstracyjnych PDF dla księgowego UK
**Status:** 🟢 Zakończone
**Start:** 2026-11-05
**Plan nadrzędny:** `docs/plans/uk-finance-tax-module-plan.md`

## Cel bieżącego przyrostu

Przygotować odtwarzalny, anglojęzyczny pakiet demonstracyjnych dokumentów finansowych na rzeczywistych danych firmy zapisanych w `Legal Settings` oraz jednym spójnym zestawie fikcyjnych transakcji, aby księgowy UK mógł ocenić dokumenty istniejące oraz docelowe raporty przed dalszą implementacją modułu.

## Delta Analysis: pakiet demonstracyjny

### Anchor files (reużywam)

- `resources/views/pdf/invoice.blade.php` — aktualny produkcyjny PDF faktury.
- `resources/views/pdf/credit-note.blade.php` — aktualny produkcyjny PDF customer credit note.
- `app/Http/Controllers/InvoicePdfController.php` i `CreditNotePdfController.php` — konfiguracja DomPDF/A4.
- `tests/Feature/Finance/FinancialDocumentPdfTest.php` — wzorzec walidacji dokumentów finansowych.
- `composer.json` — istniejący `barryvdh/laravel-dompdf`.

### Do stworzenia

- [x] Action generująca pakiet bez zapisu danych demonstracyjnych do bazy.
- [x] Cienka komenda Artisan z konfigurowalnym katalogiem wyjściowym.
- [x] Wspólny szablon raportów demonstracyjnych z oznaczeniem statusu dokumentu.
- [x] Produkcyjne przykłady invoice i customer credit note z watermarkiem demo.
- [x] Docelowe przykłady reconciliation, VAT, sprawozdań, ageing, expenses i audit trail.
- [x] Przykłady zewnętrznych dokumentów zakupowych oznaczone jako przechowywane, a nie generowane.
- [x] Indeks, dataset JSON, manifest SHA-256, checklista review i ZIP.
- [x] Test spójności pakietu oraz walidacja wygenerowanych PDF-ów.

### Założenia danych

- Dane firmy pochodzą z systemowego `Legal Settings`; dane transakcyjne są fikcyjne.
- Generator używa `AccountingProfile` wyłącznie jako fallback i raportuje niespójność obu źródeł.
- Okres: 1 lipca–30 września 2026; waluta funkcjonalna GBP.
- Jeden przykład faktury EUR z niezmiennym snapshotem GBP.
- VAT Return: Box 1 £1,912.80, Box 4 £1,200.00, Box 5 £712.80, Box 6 £9,564, Box 7 £8,600.
- Trial Balance i Balance Sheet muszą bilansować się do pensa.

### Ryzyka i granice

- Każdy dokument: `DEMO / NOT FOR SUBMISSION`.
- Raporty fazy 5 są oznaczone jako `TARGET PREVIEW`, nie jako gotowa funkcja.
- Incoming supplier documents są przechowywane, ale nie wystawiane przez system.
- Pakiet nie jest poradą podatkową ani oficjalnym formularzem HMRC.
- Szczególne schematy VAT są poza zakresem przykładu.

## Walidacja planowana

- [x] Test generatora i matematycznej spójności danych.
- [x] Nagłówek `%PDF-`, rozmiar i SHA-256 każdego dokumentu.
- [x] Laravel Pint, `git diff --check` i regresja dokumentów finansowych.

## Wynik

- [x] Wygenerowano 18 PDF-ów oraz ZIP w `docs/demo/uk-accountant-pack/`.
- [x] Dokumenty używają danych `Website Expert NI` z `Legal Settings`; usunięto fikcyjne dane wystawcy.
- [x] Każdy dokument ma oznaczenie `DEMO / NOT FOR SUBMISSION` i spójne sumy kontrolne.
- [x] Finance regression: 180 passed, 1 skipped, 1116 assertions.
- [x] Full regression: 689 passed, 11 skipped, 9 znanych baseline failures, 2556 assertions.
- [x] Nazwa w `AccountingProfile` została zsynchronizowana do `Website Expert NI`; pozostałe brakujące dane profilu nadal wymagają synchronizacji przed użyciem produkcyjnym.

## Następny krok po ukończeniu

- [ ] Przekazać ZIP i checklistę księgowemu UK.
- [ ] Zapisać uwagi księgowego jako kryteria akceptacji przed UKFIN-501–510; UKFIN-405 został ukończony.

---

# Poprzednie zadanie — UKFIN-404

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

- [x] `UKFIN-405` — model i UI reconcile exact/partial/split/many-to-many (ukończone 2026-11-05).

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
