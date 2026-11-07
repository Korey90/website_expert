# Current Task

# UKFIN-408 — raport salda i uzgodnienia bankowego

**Zadanie:** UKFIN-408 — Bank balance/reconciliation report
**Status:** 🟢 Zakończone
**Start:** 2026-11-07
**Plan nadrzędny:** `docs/plans/uk-finance-tax-module-plan.md`

## Cel bieżącego przyrostu

Udostępnić księgowemu i użytkownikowi z `view_finance` sprawdzalny raport per rachunek bankowy i okres: saldo otwarcia, wpływy, wypływy, saldo zamknięcia oraz pokrycie uzgodnieniami, z możliwością przejścia do szczegółów transakcji i eksportu CSV.

## Delta Analysis: UKFIN-408

### Anchor files (reużywam)

- `ExpenseReportService`, `ExpenseReportPage` i `ExpenseReportExportController` — wzorzec service → Filament page → tenantowy eksport CSV.
- `BankAccount` i `BankTransaction` — saldo otwarcia, waluta, importowane przepływy oraz statusy reconciliation.
- `BankReconciliation` i `BankReconciliationAllocation` — aktywne oraz cofnięte batche i kwoty alokacji.
- `BankTransactionMatchingService` — reguła liczenia dostępnej kwoty wyłącznie z aktywnych alokacji.
- `FilamentPermissionRegistry` i `PermissionHelper` — ochrona strony i eksportu przez `view_finance`.

### Do stworzenia

- [x] Tenantowy serwis raportowy z walidacją okresu i opcjonalnego rachunku.
- [x] Podsumowanie per rachunek: saldo otwarcia, wpływy, wypływy, saldo zamknięcia, kwota uzgodniona i pozostała.
- [x] Szczegóły transakcji: data, referencja, kontrahent, kwota, aktywna alokacja, reszta i status.
- [x] Poprawne wykluczenie alokacji z cofniętych batchy oraz przeliczenie stanu na moment generowania raportu.
- [x] Filament page z filtrem okresu/rachunku i czytelną obsługą wielu walut bez sumowania ich do jednej kwoty.
- [x] Eksport CSV chroniony przez `view_finance`, tenant i `Cache-Control: private, no-store`.
- [x] Tłumaczenia PL/EN/PT.
- [x] Testy obliczeń, partial/reversal, opening balance, wielu walut, filtrów, tenant isolation, RBAC, strony i CSV.

### Założenia i granice

- Saldo rachunku jest saldem bankowym wyliczonym z zapisanego `opening_balance` oraz importowanych transakcji; nie jest jeszcze porównaniem z Trial Balance/General Ledger z fazy 5.
- `opening_balance` jest stanem bazowym na `opening_balance_date`, a do kolejnych sald wchodzą transakcje od tej daty włącznie.
- Kwoty pozostają w walucie rachunku; raport wielu rachunków nie tworzy mylącej sumy pieniężnej między GBP/EUR/USD.
- Tylko alokacje należące do batchy `active` zmniejszają kwotę nieuzgodnioną; historia `reversed` pozostaje audytem, ale nie pokryciem.
- Rachunki archiwalne są widoczne w raporcie historycznym i filtrze.

## Walidacja planowana

- [x] Dedykowany PHPUnit dla serwisu, strony i eksportu: 5 passed / 34 assertions.
- [x] Regresja UKFIN-404–408: 33 passed / 206 assertions; Finance: 209 passed / 1 skipped / 1285 assertions.
- [x] Pint na zmienionych plikach, parity tłumaczeń 555/555/555 kluczy liściowych i `git diff --check`.

## Wynik UKFIN-408

- [x] Raport pokazuje saldo otwarcia, przepływy i saldo końcowe z `opening_balance` oraz importowanych transakcji.
- [x] Pokrycie uzgodnieniem jest liczone z aktywnych alokacji; cofnięte batche pozostają historią i nie zmniejszają reszty.
- [x] Statusy szczegółów są przeliczane z kwot aktywnych alokacji, dzięki czemu raport pokazuje stan faktyczny `unmatched` / `partially_reconciled` / `reconciled`.
- [x] Rachunki walutowe pozostają rozdzielone w walucie natywnej; brak mylącej sumy GBP/EUR/USD.
- [x] Strona Filament i eksport CSV są chronione przez `view_finance`, aktywny tenant i tenantowy filtr rachunku.
- [x] Pełna regresja: 718 passed / 9 znanych baseline failures / 11 skipped / 2729 assertions.

## Następny krok

- [ ] `UKFIN-409` — testy duplicates, split, FX, reversal i tenant isolation dla całej fazy bank reconciliation.

---

# UKFIN-407 — cofnięcie reconciliation z audit trail

**Zadanie:** UKFIN-407 — Undo reconciliation z audit trail
**Status:** 🟢 Zakończone
**Start:** 2026-11-07
**Plan nadrzędny:** `docs/plans/uk-finance-tax-module-plan.md`

## Cel bieżącego przyrostu

Pozwolić uprawnionemu użytkownikowi cofnąć wybrany aktywny batch uzgodnienia bez usuwania historii, zwolnić jego alokacje dla kolejnego dopasowania i atomowo przeliczyć status transakcji bankowej na `unmatched`, `partially_reconciled` albo `reconciled`.

## Delta Analysis: UKFIN-407

### Anchor files (reużywam)

- `ReconcileBankTransactionAction` — blokada transakcji, walidacja źródeł, active allocations i retry deadlocków.
- `ReverseJournalEntryAction` — wymagane uzasadnienie, idempotencja i audyt autora/czasu bez kasowania historii.
- `BankTransactionMatchingService` — dostępne kwoty liczą wyłącznie alokacje aktywnych batchy.
- `BankTransactionResource` i `ViewBankTransaction` — szczegóły alokacji oraz akcje nagłówkowe Filament.
- `finance:audit-tenants` — kontrola spójności reconciliation, transakcji i źródeł polymorficznych.

### Do stworzenia

- [x] Migracja pól `reversed_by`, `reversed_at`, `reversal_reason` i statusu `reversed` dla reconciliation batch.
- [x] `UndoBankReconciliationAction` z tenant/RBAC, blokadami transakcji, batcha i źródeł oraz idempotencją dla tego samego uzasadnienia.
- [x] Ponowne wyliczenie statusu transakcji z sumy wyłącznie aktywnych alokacji.
- [x] Niezmienność historycznych batchy i alokacji: brak edycji oraz usuwania.
- [x] Akcja Filament wyboru aktywnego batcha, wymagane uzasadnienie i widoczny audit trail.
- [x] Rozszerzenie tenant audit o kompletność metadanych reversal.
- [x] Tłumaczenia PL/EN/PT.
- [x] Testy exact/partial/split, ponownej dostępności źródła, idempotencji, RBAC, tenant isolation, immutability, audytu i UI.

### Ryzyka i granice

- Cofnięcie odłącza wyłącznie batch reconciliation; nie usuwa ani nie odwraca payment, expense lub journal entry.
- Alokacje odwróconego batcha pozostają w bazie jako niezmienny dowód historyczny, ale nie zmniejszają już dostępnych kwot.
- Status transakcji musi wynikać z pozostałych aktywnych batchy; cofnięcie jednego z kilku batchy nie może naruszyć pozostałych.
- Operacja nie może przekroczyć granic `business_id`, a wszystkie zmiany muszą zostać wycofane razem przy błędzie integralności.

## Walidacja planowana

- [x] Migracja SQLite/MySQL i `finance:audit-tenants`.
- [x] PHPUnit dla nowej Action, audit command oraz widoczności/wykonania akcji Filament.
- [x] Regresja UKFIN-404–407 i pełna domena Finance.
- [x] Pint na zmienionych plikach, parity tłumaczeń i `git diff --check`.

## Wynik UKFIN-407

- [x] Cofnięcie zmienia batch z `active` na `reversed` i zapisuje autora, czas oraz wymagane uzasadnienie bez kasowania alokacji.
- [x] Blokady transakcji, batcha, alokacji i źródeł oraz retry chronią przed wyścigami z nowym reconciliation.
- [x] Operacja jest idempotentna dla tego samego powodu, odrzuca payload drift i wykonuje pełny rollback przy niespójnym audycie.
- [x] Status bank transaction jest przeliczany wyłącznie z aktywnych batchy, a zwolnione kwoty ponownie trafiają do matching candidates.
- [x] Modele batcha i alokacji blokują edycję oraz usuwanie historycznych rekordów.
- [x] Filament pozwala wybrać aktywny batch, wymaga powodu i pokazuje status oraz pełne metadane reversal.
- [x] Dedykowane testy UKFIN-407: 7 passed, 43 assertions; reconciliation + undo + audit: 24 passed, 112 assertions.
- [x] Finance regression: 204 passed, 1 skipped, 1251 assertions.
- [x] Full regression: 713 passed, 11 skipped, 9 znanych baseline failures, 2695 assertions.

## Następny krok

- [x] `UKFIN-408` — bank balance/reconciliation report (ukończone 2026-11-07).

---

# Poprzednie zadanie — UKFIN-406: utworzenie expense/payment z transakcji bankowej

**Zadanie:** UKFIN-406 — Create expense/payment from unmatched transaction
**Status:** 🟢 Zakończone
**Start:** 2026-11-05
**Plan nadrzędny:** `docs/plans/uk-finance-tax-module-plan.md`

## Cel bieżącego przyrostu

Pozwolić użytkownikowi z `manage_finance` i `post_finance` zamienić nieuzgodniony wpływ w płatność do istniejącej wystawionej faktury albo nieuzgodniony wypływ w kompletny wydatek dostawcy, a następnie atomowo uzgodnić utworzony zapis z właściwą transakcją i kontem bankowym.

## Delta Analysis: UKFIN-406

### Anchor files (reużywam)

- `CreateExpenseAction`, `ApproveExpenseAction`, `PostExpenseAction` — kalkulacja VAT i księgowanie dokumentu zakupu.
- `RecordInvoicePaymentAction`, `RecordExpensePaymentAction` — saldo dokumentów, snapshot FX i posting płatności.
- `ReconcileBankTransactionAction` — audytowalny batch oraz ochrona przed over-allocation.
- `PaymentJournalPayloadFactory` — linie bank/receivable/payable i realised FX.
- `BankTransactionResource/Pages/ViewBankTransaction` — modal oraz RBAC workflow bankowego.

### Do stworzenia

- [x] Migracja opcjonalnego `bank_account_id` na customer/supplier payments, aby posting trafiał na ledger rachunku źródłowego zamiast zawsze na konto `1100`.
- [x] Tenantowe relacje i audit dla rachunku płatności.
- [x] Atomowa Action dla wpływu: wystawiona faktura → `Payment` → posting → reconciliation.
- [x] Atomowa Action dla wypływu: wydatek z jedną pozycją gross/VAT → approve/post → `ExpensePayment` → reconciliation.
- [x] Walidacja kierunku, pełnego statusu `unmatched`, waluty, FX, daty, tax code, ledger account, membership oraz `manage_finance` + `post_finance`.
- [x] Modal Filament z formularzem zależnym od kierunku transakcji.
- [x] Tłumaczenia PL/EN/PT.
- [x] Testy flow, księgowania na właściwy ledger, VAT gross-to-net, FX, rollbacku, tenant isolation, RBAC i UI.

### Ryzyka i granice

- Workflow tworzy zapis wyłącznie dla całej, jeszcze niealokowanej transakcji; częściowe transakcje nadal obsługuje ręczny split z UKFIN-405.
- Wpływ wymaga istniejącej, zaksięgowanej faktury z dostępnym saldem.
- Wypływ tworzy i od razu księguje wydatek oraz płatność, dlatego wymaga poprawnego tax code i konta kosztowego.
- Waluta dokumentu musi odpowiadać walucie rachunku albo dokument może być walutowy przy rachunku GBP; snapshot FX nie może być domyślnie zgadywany.
- Wszystkie operacje muszą zostać wycofane razem, jeśli posting lub reconciliation zawiedzie.

## Walidacja planowana

- [x] Migracja SQLite/MySQL i `finance:audit-tenants`.
- [x] PHPUnit dla nowej Action oraz widoczności/wykonania akcji Filament.
- [x] Regresja UKFIN-404–406 i pełna domena Finance.
- [x] Pint na zmienionych plikach, parity tłumaczeń i `git diff --check`.

## Wynik UKFIN-406

- [x] Wpływ tworzy, księguje i uzgadnia customer payment do istniejącej faktury w jednej transakcji bazodanowej.
- [x] Wypływ tworzy wydatek z kalkulacją gross-to-net VAT, zatwierdza go, księguje, opłaca i uzgadnia atomowo.
- [x] Customer i supplier payments zachowują `bank_account_id`, a posting używa dokładnego konta cash ledger przypisanego do rachunku.
- [x] Obsłużone są przepływy same-currency, GBP ↔ foreign ze snapshotem FX oraz jawny kurs dla rachunku walutowego.
- [x] Workflow wymaga aktywnego membership oraz obu uprawnień `manage_finance` i `post_finance`; tenant audit obejmuje nowe relacje.
- [x] Filament pokazuje osobne akcje dla wpływu i wypływu wyłącznie przy w pełni nieuzgodnionej transakcji.
- [x] Dedykowane testy: 6 passed, 37 assertions; rozszerzony audit: 14 passed, 57 assertions.
- [x] Finance regression: 196 passed, 1 skipped, 1205 assertions.
- [x] Full regression: 705 passed, 11 skipped, 9 znanych baseline failures, 2649 assertions.

## Następny krok

- [x] `UKFIN-407` — cofnięcie reconciliation z pełnym audit trail (ukończone 2026-11-07).

---

# Poprzednie zadanie — UKFIN-405

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

- [x] `UKFIN-406` — utworzenie expense/payment z nieuzgodnionej transakcji bankowej (ukończone 2026-11-05).

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
