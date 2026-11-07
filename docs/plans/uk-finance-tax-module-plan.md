# Plan modułu finansów, księgowości i podatków UK

**Status:** IMPLEMENTACJA W TOKU — Fazy 0–3 zaimplementowane, Faza 4 w toku (`UKFIN-401`–`UKFIN-404` ukończone); gate Fazy 1 oczekuje na akceptację przykładowych dokumentów przez księgowego UK
**Data opracowania:** 2026-11-04  
**Rynek docelowy:** Wielka Brytania  
**Projekt:** WebsiteExpert  
**Priorytet rekomendowany:** najpierw ewidencja finansowa i VAT, następnie MTD VAT; pozostałe deklaracje jako osobne rozszerzenia

---

## 1. Cel biznesowy

Rozbudować istniejący moduł faktur i płatności do systemu, który pozwala firmie w UK:

1. ewidencjonować sprzedaż, zakupy, koszty, płatności, należności i zobowiązania;
2. przechowywać dokumenty źródłowe oraz pełną historię zmian;
3. prowadzić księgę w walucie funkcjonalnej GBP z obsługą transakcji GBP/EUR/PLN;
4. przygotowywać raporty zarządcze i paczkę danych dla księgowego;
5. wyliczać roboczą deklarację VAT na podstawie cyfrowych zapisów;
6. po osobnym dopuszczeniu integracji — pobierać obowiązki i składać VAT Return przez HMRC Making Tax Digital;
7. w dalszych etapach opcjonalnie obsługiwać MTD Income Tax, Corporation Tax, PAYE i CIS.

System nie może przedstawiać wyliczeń jako profesjonalnej porady podatkowej. Każda deklaracja przed wysłaniem ma wymagać jawnego zatwierdzenia użytkownika, a reguły podatkowe i mapowania powinny zostać zaakceptowane przez księgowego znającego rynek UK.

---

## 2. Zakres i granice produktu

### 2.1 Zakres podstawowy — rekomendowany do realizacji

- utwardzenie istniejących faktur, ofert i płatności pod multi-tenancy;
- poprawne faktury UK i VAT per pozycja;
- dostawcy, koszty, rachunki zakupowe i załączniki;
- noty kredytowe sprzedażowe i zakupowe;
- kategorie księgowe oraz podstawowy plan kont;
- import wyciągów bankowych i uzgadnianie płatności;
- niezmienny mechanizm podwójnego zapisu;
- okresy księgowe i blokowanie zamkniętych okresów;
- P&L, Balance Sheet, Trial Balance, Cash Flow, VAT Detail;
- Aged Receivables i Aged Payables;
- przygotowanie 9 pól VAT Return;
- integracja HMRC MTD VAT;
- eksport paczki dla księgowego.

### 2.2 Rozszerzenia opcjonalne — osobne produkty/sprinty

- MTD for Income Tax dla sole traders i landlordów;
- Corporation Tax i CT600;
- payroll, PAYE, FPS/EPS oraz workplace pension;
- Construction Industry Scheme;
- środki trwałe i amortyzacja podatkowa;
- magazyn i wycena zapasów;
- automatyczne bank feeds przez Open Banking.

Te rozszerzenia nie powinny blokować uruchomienia podstawowej ewidencji finansowej. Payroll, CIS i pełne zeznania podatkowe mają niezależne reguły, cykle publikacji i ryzyko regulacyjne.

---

## 3. Podstawa wymagań UK

Plan oparto na oficjalnych źródłach aktualnych na 2026-11-04:

- Firma musi przechowywać zapisy wszystkich wpływów i wydatków, aktywów, zobowiązań, sprzedaży i zakupów oraz dokumenty takie jak faktury, potwierdzenia i wyciągi bankowe. HMRC wskazuje dla dokumentacji Corporation Tax okres co najmniej 6 lat od końca właściwego okresu: [Company and accounting records — GOV.UK](https://www.gov.uk/running-a-limited-company/company-and-accounting-records).
- Faktura musi mieć m.in. unikalny numer, dane stron, opis, datę dostawy/usługi, datę wystawienia, kwoty, VAT i sumę należną: [Invoices — what they must include — GOV.UK](https://www.gov.uk/invoicing-and-taking-payment-from-customers/invoices-what-they-must-include).
- VAT records obejmują sprzedaż i zakupy, faktury wystawione i otrzymane, credit/debit notes, dane bankowe i dokumentację VAT; określone dane muszą być utrzymywane cyfrowo: [Keeping VAT records — GOV.UK](https://www.gov.uk/charge-reclaim-record-vat/keeping-vat-records).
- Dla VAT należy rozróżnić standard rate 20%, reduced rate 5%, zero rate 0% oraz exempt/outside scope: [VAT rates — GOV.UK](https://www.gov.uk/vat-rates).
- Pełna faktura VAT wymaga stawki VAT dla poszczególnych pozycji, tax point oraz VAT wyrażonego w sterlingach: [VAT Notice 700 — GOV.UK](https://www.gov.uk/guidance/vat-guide-notice-700).
- MTD wymaga cyfrowych zapisów i cyfrowych połączeń między elementami oprogramowania: [VAT Notice 700/22 — GOV.UK](https://www.gov.uk/government/publications/vat-notice-70022-making-tax-digital-for-vat/vat-notice-70022-making-tax-digital-for-vat).
- Oficjalne VAT API pozwala pobrać obligations, wysłać i odczytać return oraz pobierać liabilities i payments: [VAT MTD API — HMRC Developer Hub](https://developer.service.hmrc.gov.uk/api-documentation/docs/api/service/vat-api/1.0).
- MTD Income Tax obowiązuje etapami dla sole traders/landlordów: ponad £50,000 od kwietnia 2026, ponad £30,000 od kwietnia 2027 i ponad £20,000 od kwietnia 2028: [Use MTD for Income Tax — GOV.UK](https://www.gov.uk/guidance/use-making-tax-digital-for-income-tax/using-making-tax-digital-for-income-tax-for-the-first-time).
- Payroll wymaga FPS na lub przed payday oraz, zależnie od sytuacji, EPS: [Reporting to HMRC: FPS — GOV.UK](https://www.gov.uk/running-payroll/reporting-to-hmrc).
- CIS contractors składają miesięczne returns obejmujące płatności dla subcontractors: [File CIS monthly returns — GOV.UK](https://www.gov.uk/what-you-must-do-as-a-cis-contractor/file-your-monthly-returns).
- Dostęp do danych bankowych przez API wymaga modelu Open Banking z autoryzacją i zgodą użytkownika: [Open Banking API Standards](https://standards.openbanking.org.uk/api-specifications/latest/).

Wymagania prawne i API mogą się zmieniać. Przed rozpoczęciem każdego etapu integracyjnego należy ponownie zweryfikować bieżące dokumenty HMRC.

---

## 4. Delta analysis — stan obecny

### 4.1 Anchor files do ponownego użycia

| Obszar | Istniejący element | Zastosowanie |
|---|---|---|
| Faktury | `app/Models/Invoice.php` | relacje, statusy, przeliczenie subtotal/VAT/total/balance |
| Pozycje faktury | `app/Models/InvoiceItem.php` | baza dla pozycji sprzedażowych |
| Płatności | `app/Models/Payment.php` | metody, statusy i powiązanie z fakturą |
| Oferty | `app/Models/Quote.php` | wzorzec dokumentu sprzedażowego |
| Panel | `app/Filament/Resources/InvoiceResource.php` | formularze i tabela finansowa |
| PDF | `resources/views/pdf/invoice.blade.php` | istniejący dokument sprzedażowy |
| Raporty | `app/Http/Controllers/ReportController.php` | HTML/PDF/XLSX/CSV |
| Waluty | `app/Services/Currency/*` | GBP/EUR/PLN, formatowanie i przeliczenia |
| Płatności online | Stripe/PayU controllers i webhooki | automatyczna rejestracja wpłat |
| Tenant | `app/Traits/BelongsToTenant.php` | globalny scope i automatyczny `business_id` |
| Uprawnienia | `BaseResource`, Spatie Permission | kontrola dostępu w Filament |
| Testy | `tests/Feature/ReportInvoicesTest.php` i portal payment tests | wzorce testów raportów i płatności |

### 4.2 Funkcje, które już działają

- faktury, pozycje faktur i statusy płatności;
- VAT liczony jedną stawką na całą fakturę;
- kwoty zapłacone i saldo;
- PDF faktury;
- płatności Stripe, PayU i manualne;
- waluty GBP, EUR i PLN;
- raport faktur w HTML/PDF/XLSX/CSV;
- portal klienta;
- dane klienta: VAT number i Companies House number.

### 4.3 Główne luki

- brak `business_id` bezpośrednio na `quotes`, `invoices`, `payments`, `projects` i części modeli pochodnych;
- brak dostawców, rachunków zakupowych i kosztów;
- brak tax code per pozycja i mixed-rate invoices;
- brak tax point, reverse charge, exempt i outside scope;
- brak credit notes;
- brak dokumentów źródłowych i polityki retencji;
- brak kont księgowych, journals i double-entry ledger;
- brak okresów księgowych i lock date;
- brak bank reconciliation;
- brak VAT return i połączenia HMRC;
- brak P&L, Balance Sheet, Trial Balance i Cash Flow;
- brak modułów Income Tax, Corporation Tax, PAYE i CIS.

---

## 5. Decyzje architektoniczne

### 5.1 Tenant i identyfikatory

- Każdy rekord finansowy należy do `business_id`.
- `businesses.id` jest ULID, dlatego migracje mają używać `char('business_id', 26)` z właściwym FK, a nie domyślnego bigint `foreignId()`.
- Istniejące dokumenty trzeba backfillować przez `client.business_id` lub relację nadrzędną.
- Migracja ma najpierw dodać nullable column, wykonać kontrolowany backfill i raport braków, a dopiero potem ustawić NOT NULL tam, gdzie domena tego wymaga.
- Wszystkie listy, selecty i raporty muszą być testowane na izolację tenantów.

### 5.2 Waluta

- Waluta funkcjonalna księgi: GBP.
- Dokument przechowuje walutę oryginalną, kurs, źródło kursu, datę kursu i równowartość GBP.
- Nie wolno przeliczać historycznych zapisów ponownie po zmianie kursu.
- VAT należny i naliczony musi być dostępny w GBP, nawet gdy faktura ma inną walutę.
- Różnice kursowe tworzą osobne journal lines.

### 5.3 Ledger

- Posted journal jest niezmienny.
- Korekta odbywa się przez reversal i nowy zapis, nigdy przez cichą edycję.
- Każdy journal musi się bilansować: suma debit = suma credit.
- Draft dokumentu nie księguje się; posting następuje przez Action w transakcji DB.
- Każda akcja księgowa ma idempotency key.
- Usunięcie posted dokumentu jest zabronione; dostępne są void/reversal/credit note.

### 5.4 Dokumenty i audyt

- Załączniki przechowywane prywatnie, nigdy na publicznym dysku.
- Hash SHA-256 pliku do wykrywania zmian i duplikatów.
- Audit log: kto, kiedy i z jakiego stanu zmienił dokument.
- Retention policy domyślnie 6 lat, konfigurowalna wyłącznie w górę.
- Eksport kompletnej paczki audytowej per okres.

### 5.5 Integracja HMRC

- Osobny adapter `HmrcClientInterface` i implementacje sandbox/production.
- OAuth tokens szyfrowane przez Laravel encrypted casts.
- Żadnych tokenów, UTR, NINO ani pełnych requestów HMRC w logach aplikacyjnych.
- Wymagane fraud-prevention headers zgodne z bieżącą dokumentacją HMRC.
- Submission tylko po ekranie review i dodatkowym potwierdzeniu.
- Zapisywać payload hash, response metadata, correlation ID oraz immutable submission snapshot.
- Automatyczne retry tylko dla bezpiecznych operacji GET; POST submission wymaga idempotency i kontroli stanu przed ponowieniem.

---

## 6. Docelowy model danych

### 6.1 Konfiguracja firmy

#### `accounting_profiles`

- `business_id` — unique FK;
- `entity_type` — `limited_company`, `sole_trader`, `partnership`;
- `legal_name`, `company_number`, `registered_address`;
- `company_utr` — encrypted;
- `functional_currency` — domyślnie GBP;
- `accounting_basis` — `accrual`, `cash`;
- `financial_year_start`, `financial_year_end`;
- `vat_registered`, `vat_number`, `vat_scheme`;
- `vat_effective_from`, `vat_effective_to`;
- `paye_reference`, `accounts_office_reference` — encrypted, opcjonalne;
- timestamps + audit metadata.

#### `accounting_periods`

- rok/okres, daty początku i końca;
- `status`: open, closing, closed, locked;
- `locked_at`, `locked_by`;
- możliwość soft close i final lock.

### 6.2 VAT i podatki pośrednie

#### `tax_codes`

- kod i etykiety pl/en/pt;
- typ: standard, reduced, zero, exempt, outside_scope, reverse_charge;
- rate;
- input/output eligibility;
- mapowanie do pól VAT Return;
- daty obowiązywania, bez nadpisywania historii.

#### Rozszerzenie `invoice_items` i `quote_items`

- `tax_code_id`;
- `net_amount`, `vat_amount`, `gross_amount`;
- `supply_date`/tax point;
- `vat_rate_snapshot`;
- `description`, quantity, unit price zachowane jako snapshot.

#### `credit_notes` i `credit_note_items`

- typ sprzedażowy lub zakupowy;
- powiązanie z fakturą/expense;
- reason, issue date, tax point;
- kwoty netto/VAT/brutto;
- status draft/issued/allocated/void;
- posting do ledgeru przez reversal odpowiedniej części dokumentu.

### 6.3 Zakupy i wydatki

#### `suppliers`

- tenant, nazwa, adres, company number, VAT number;
- waluta domyślna i domyślne konto kosztowe;
- payment terms i dane kontaktowe;
- status active/archived.

#### `expenses`

- supplier, supplier invoice number i unikalność per supplier;
- issue date, tax point, due date, received date;
- currency, exchange rate snapshot i wartości GBP;
- subtotal, VAT, total, paid, due;
- status draft/approved/posted/partially_paid/paid/void;
- payment method, project/client optional;
- business purpose i notatki;
- `created_by`, `approved_by`, `posted_by`.

#### `expense_items`

- description, quantity, unit price;
- ledger account, tax code;
- net/VAT/gross w walucie dokumentu i GBP;
- billable flag i opcjonalne powiązanie z klientem/projektem.

#### `financial_attachments`

- polymorphic relation do invoice, expense, payment, bank transaction i filing;
- private storage path, mime, size, SHA-256, uploaded_by;
- document type: invoice, receipt, bank statement, contract, HMRC acknowledgement.

### 6.4 Bank i kasa

#### `bank_accounts`

- tenant, nazwa banku, masked account number, sort code masked;
- waluta, opening balance, ledger account;
- status i ostatnia synchronizacja;
- provider connection oddzielona od danych konta.

#### `bank_transactions`

- provider/import ID z unique constraint;
- booking/value date, amount, currency, reference, counterparty;
- raw payload zaszyfrowany lub zminimalizowany;
- reconciliation status;
- hash do deduplikacji importu.

#### `bank_transaction_matches`

- bank transaction ↔ invoice/payment/expense/journal;
- kwota dopasowana, confidence score, matched_by;
- obsługa split/partial/many-to-many;
- pełna historia cofnięcia uzgodnienia.

### 6.5 Księga główna

#### `ledger_accounts`

- tenant + code;
- typ: asset, liability, equity, income, expense;
- subtype, nazwy pl/en/pt;
- parent account, system flag, active flag;
- domyślny UK chart template kopiowany do firmy.

#### `journal_entries`

- tenant, date, accounting period, source type/id;
- status draft/posted/reversed;
- description, idempotency key;
- posted/reversed metadata;
- immutable source snapshot hash.

#### `journal_lines`

- journal, ledger account;
- debit/credit w GBP;
- original currency/amount i exchange rate;
- tax code, client, supplier, project opcjonalnie;
- memo i dimensions JSON na przyszłość.

### 6.6 VAT returns i HMRC

#### `vat_periods`

- period key HMRC, daty, due date;
- status open/fulfilled/submitted;
- source HMRC/manual.

#### `vat_returns`

- 9 pól VAT Return;
- status draft/reviewed/submitted/accepted/rejected;
- adjustments z uzasadnieniem;
- snapshot ledger cutoff;
- calculated_at, reviewed_by, submitted_by;
- HMRC correlation ID i receipt metadata.

#### `hmrc_connections`

- tenant, environment, scopes;
- encrypted access/refresh tokens;
- expires_at, VRN, status;
- last successful call i reconnect reason.

#### `hmrc_submission_logs`

- endpoint, method, payload hash, response code;
- correlation ID, request timestamp;
- sanitized error body;
- bez sekretów i danych nadmiarowych.

---

## 7. Plan realizacji

Każde zadanie jest mniejsze niż 8 godzin. Estymaty: XS < 1h, S 1–3h, M 3–8h.

### Faza 0 — zakres, bezpieczeństwo i fundament tenantów (1 tydzień)

**Cel:** usunąć ryzyka blokujące księgowość i zatwierdzić reguły z księgowym.

- [ ] `UKFIN-001` [S] Potwierdzić grupę użytkowników: limited company, sole trader, partnership.
- [ ] `UKFIN-002` [M] Warsztat z księgowym UK: plan kont, tax codes, VAT schemes, raporty i przykładowe dokumenty.
- [x] `UKFIN-003` [M] Audyt wszystkich finansowych queries, policies i tenant boundaries. *(2026-11-04: ścieżki zapisu + regresja reports/portal/workflow; kolejne modele obowiązkowo dopisywane do audytu)*
- [x] `UKFIN-004` [M] Migracja `business_id` do quotes/invoices/payments/projects i tabel pochodnych. *(objęto również contacts)*
- [x] `UKFIN-005` [M] Bezpieczny backfill + raport rekordów bez jednoznacznego tenanta. *(`finance:audit-tenants`)*
- [x] `UKFIN-006` [M] Dodanie `BelongsToTenant`/jawnego scope i indeksów złożonych.
- [x] `UKFIN-007` [M] Testy izolacji dla invoices, payments, quotes, reports i Filament selects.
- [x] `UKFIN-008` [S] Rejestr uprawnień: view/manage/approve/post/close/submit-tax.
- [x] `UKFIN-009` [S] Threat model: HMRC tokens, bank data, attachments, UTR/PAYE refs. *(`docs/security/uk-finance-threat-model.md`)*

**Gate:** nie rozpoczynać kosztów ani HMRC, dopóki testy cross-tenant nie są zielone.

### Faza 1 — poprawne dokumenty sprzedażowe UK (1–2 tygodnie)

**Cel:** faktury i credit notes zawierają wymagane dane oraz obsługują mixed VAT.

- [x] `UKFIN-101` [M] AccountingProfile + konfiguracja entity/VAT/year-end. *(schema, szyfrowanie, Action, Inertia UI, role owner/admin i PL/EN/PT)*
- [x] `UKFIN-102` [M] TaxCode model, migracja i seed standard/reduced/zero/exempt/outside scope/reverse charge.
- [x] `UKFIN-103` [M] Tax code i tax snapshot na invoice/quote items.
- [x] `UKFIN-104` [M] Refaktor kalkulacji VAT z poziomu faktury na pozycje.
- [x] `UKFIN-105` [M] Supply date/tax point i VAT amount in GBP. *(snapshot kursu, źródła i daty; kwoty GBP per pozycja i dokument; PDF)*
- [x] `UKFIN-106` [M] Sekwencje numeracji per tenant i document type z blokadą race condition. *(transakcja + `lockForUpdate`, osobna sekwencja roczna, backfill)*
- [x] `UKFIN-107` [M] CreditNote + items + lifecycle. *(snapshot z faktury, partial credit, issue/allocate/void, audit metadata i tenant scope)*
- [x] `UKFIN-108` [M] Aktualizacja InvoiceResource, QuoteResource i PDF. *(formularze tax point/tax code/FX, panel Credit Notes z lifecycle, PDF faktury i noty z danymi sprzedawcy, stawkami VAT i odwołaniem do dokumentu źródłowego)*
- [x] `UKFIN-109` [S] Walidacja pól zależnie od VAT registered/entity type. *(Form Request + normalizacja pól nieaktywnych)*
- [x] `UKFIN-110` [M] Testy: mixed rates, rounding, FX, zero/exempt, credit note, concurrent numbering. *(test współbieżny uruchamiany na MySQL/PostgreSQL; lokalne SQLite nie obsługuje `SELECT ... FOR UPDATE`; dodatkowo pokryto reverse charge bez doliczania VAT do należności)*

**Gate:** przykładowe faktury i credit notes zaakceptowane przez księgowego UK.

### Faza 2 — dostawcy, koszty i dokumenty (1–2 tygodnie)

**Cel:** użytkownik rejestruje każdy zakup i ma dowód źródłowy.

- [x] `UKFIN-201` [M] Supplier model, policy, factory i Filament Resource. *(tenant scope, soft archive, dane UK/VAT, warunki płatności, RBAC `view_finance`/`manage_finance`, PL/EN/PT)*
- [x] `UKFIN-202` [M] Expense + ExpenseItem schema i status workflow. *(mixed purchase VAT, tax/FX snapshots, billable client/project, reverse charge VAT bez zwiększania salda dostawcy, panel Filament PL/EN/PT)*
- [x] `UKFIN-203` [M] Actions: Create/Update/Approve/Post/VoidExpense. *(transakcje i blokady, audit metadata, niezmienność po approval; posted wymaga przyszłego ledger reversal)*
- [x] `UKFIN-204` [M] Form Requests i walidacja duplikatu supplier invoice. *(unikalność tenant + supplier, walidacja effective tax code/FX oraz referencji tenantowych)*
- [x] `UKFIN-205` [M] Private FinancialAttachment service z SHA-256. *(prywatny dysk, tenant scope, MIME/limit 10 MB, deduplikacja hash, 6-letnia retencja, kwarantanna `pending`, relacje do invoice/expense/payment/credit note)*
- [x] `UKFIN-206` [M] Podgląd PDF/obrazu i kontrolowany download. *(RBAC `view_finance`, brak publicznych URL-i, tylko status `clean`, kontrola SHA-256 przy każdym odczycie, `no-store`/`nosniff`, ochrona cross-tenant; integracja z silnikiem malware scan musi ustawiać wynik `clean`/`rejected`)*
- [x] `UKFIN-207` [M] Purchase credit notes i refundy kosztowe. *(osobne dokumenty `PCN`, snapshoty VAT/FX z expense items, kontrola maksymalnej ilości, issue/allocate/refund/void, redukcja salda kosztu, rejestr zwrotów gotówkowych do wysokości zapłaty, tenant/RBAC i panel PL/EN/PT)*
- [x] `UKFIN-208` [M] Recurring expenses jako template + scheduled draft generation. *(weekly/monthly/quarterly/yearly, pauza i wznowienie, idempotentne szkice z unikalnym occurrence, catch-up zaległych okresów, poprawna kotwica końca miesiąca, dzienna komenda scheduler, tenant/RBAC i panel PL/EN/PT; generowanie ograniczone do GBP, aby nie utrwalać nieaktualnych kursów FX)*
- [x] `UKFIN-209` [M] Raport kosztów per category/supplier/project/VAT code. *(standardowe kategorie kosztowe, sumy netto/VAT/brutto w GBP, filtry okresu, grupowanie po kategorii/dostawcy/projekcie/kodzie VAT, wydane purchase credits jako wartości ujemne, CSV, tenant/RBAC oraz panel PL/EN/PT)*
- [x] `UKFIN-210` [M] Testy auth, tenant, upload security, VAT, partial payments i duplicates. *(regresja RBAC i cross-tenant, prywatne pliki/MIME/limit/hash, mixed VAT/FX/rounding/reverse charge, duplikaty numerów i załączników, automatyczna synchronizacja faktury `partially_paid`/`paid` po zmianach płatności; pełny pakiet Finance na SQLite, z testem blokad współbieżnych przeznaczonym dla MySQL/PostgreSQL)*

**Gate:** kompletna ścieżka receipt → approval → payment → audit export.

### Faza 3 — ledger i okresy księgowe (2 tygodnie)

**Cel:** każda operacja finansowa ma zbilansowany, audytowalny zapis księgowy.

- [x] `UKFIN-301` [M] LedgerAccount i UK chart template. *(35 tenantowych kont UK z hierarchią, typem/podtypem, saldem normalnym, flagami system/postable/active i nazwami PL/EN/PT; idempotentna inicjalizacja i migracja istniejących firm, panel Filament/RBAC, domyślne konto dostawcy, automatyczne mapowanie kategorii kosztu oraz audyt referencji cross-tenant)*
- [x] `UKFIN-302` [M] AccountingPeriod + open/close/lock workflow. *(tenantowe okresy bez nakładania zakresów, stany `open` → `closing` → `closed` → `locked`, kontrolowane ponowne otwarcie z uzasadnieniem, nieodwracalna blokada, historia przejść i metadane audytowe, RBAC `manage_finance`/`close_finance_periods`, blokada księgowania kosztu w zamkniętym okresie oraz panel Filament PL/EN/PT)*
- [x] `UKFIN-303` [M] JournalEntry/JournalLine schema i constraints. *(tenantowe nagłówki i pozycje dziennika, okres/source/idempotency/snapshot/reversal metadata, kwoty debit/credit w GBP oraz snapshot waluty pierwotnej, wymiary księgowe, relacje z planem kont i VAT, ograniczenie DB wymuszające dokładnie jedną dodatnią stronę zapisu, unikalność klucza idempotencji per tenant, walidacja daty okresu i referencji cross-tenant oraz rozszerzony audyt spójności)*
- [x] `UKFIN-304` [M] Posting engine z transaction + idempotency. *(atomowe tworzenie i posting journalu, blokada per tenant dla bezpiecznej numeracji i retry, klucz idempotencji z kanonicznym SHA-256 payloadu, odrzucenie ponowienia ze zmienionymi danymi, bilans w pensach bez błędów float, okres `open`/`closing`, RBAC `post_finance`, walidacja kont i wymiarów tenantowych, snapshot FX, pełny rollback oraz niezmienność zaksięgowanych entries/lines)*
- [x] `UKFIN-305` [M] Posting sprzedaży i credit notes. *(wystawienie faktury i noty sprzedażowej atomowo tworzy idempotentny journal; dekretacja Trade Debtors / Sales / VAT Payable per pozycja i tax code, mixed VAT, zero/reverse charge, projekty i klienci, snapshoty waluty źródłowej i GBP, odwrotne zapisy noty, integracja z akcjami Filament, rollback w zamkniętym okresie oraz ochrona zaksięgowanych dokumentów przed zmianą pól księgowych)*
- [x] `UKFIN-306` [M] Posting zakupów i purchase credits. *(zatwierdzenie kosztu i wystawienie zakupowej noty kredytowej atomowo tworzy idempotentny journal; dekretacja Expense / Input VAT / Trade Creditors per pozycja, mixed VAT, VAT nieodliczalny, reverse charge z równoległym Input VAT i VAT Payable bez zwiększania salda dostawcy, snapshoty FX i wymiary supplier/client/project, odwrotne zapisy purchase credit, rollback statusu przy błędzie okresu/uprawnień oraz ochrona zaksięgowanych dokumentów i pozycji przed zmianą danych księgowych)*
- [x] `UKFIN-307` [M] Posting payments/refunds i accounts receivable/payable. *(wpływy klientów księgowane Bank / Trade Debtors, płatności dostawcom Trade Creditors / Bank, pełne refundy klientów jako zapis przeciwny oraz zwroty gotówkowe od dostawców Bank / Trade Creditors; osobny rejestr `expense_payments`, trwałe snapshoty kwoty/kursu GBP, wymiary client/project/supplier, atomowa synchronizacja sald i statusów `partially_paid`/`paid`, referencyjna idempotencja, ochrona zaksięgowanych płatności przed zmianą oraz rollback przy zamkniętym okresie lub braku uprawnienia)*
- [x] `UKFIN-308` [M] FX gains/losses. *(rozrachunki walutowe przechowują osobno faktyczną wartość przepływu GBP, wartość bilansową należności/zobowiązania i podpisaną zrealizowaną różnicę kursową; wpływy klientów, płatności dostawców i zwroty gotówkowe dostawców automatycznie dekretują zysk na konto 4300 albo stratę na 7100, częściowe rozliczenia zachowują kurs dokumentu dla AR/AP, refund klienta odwraca pierwotny dekret FX, formularze przyjmują snapshot kursu z dnia rozliczenia, a ponowienie z tą samą referencją pozostaje idempotentne)*
- [x] `UKFIN-309` [M] Reversal zamiast edycji posted journals. *(atomowa akcja tworzy dokładny zapis lustrzany z zamienionymi stronami debit/credit, zachowuje konta, podatki, waluty, kursy i wymiary, a oryginał oznacza jako `reversed` z aktorem, czasem i wymaganym uzasadnieniem; reversal może trafić do późniejszego otwartego okresu bez naruszania okresu zablokowanego, jest idempotentny i ograniczony do jednego na oryginał, nie można odwracać reversalu ani antydatować operacji, a pełny rollback chroni przed częściowym zapisem)*
- [x] `UKFIN-310` [M] General Ledger i Journal viewer read-only. *(tenantowy raport księgi głównej pokazuje saldo otwarcia, obroty i saldo narastające Dr/Cr per konto z filtrami daty/konta oraz zachowuje w historii zarówno oryginał oznaczony `reversed`, jak i zapis storna; dziennik Filament udostępnia wyłącznie listę i szczegółowy podgląd nagłówka, źródła, hashy audytowych, powiązań storna, kwot FX, podatków i wymiarów, z RBAC `view_finance`, izolacją tenantów i tłumaczeniami PL/EN/PT)*
- [x] `UKFIN-311` [M] Testy invariants: debit=credit, lock date, idempotency, reversal, tenant. *(dedykowany kontrakt integracyjny sprawdza każdą linię i journal w pensach, pełny rollback niezbilansowanego lub dwustronnego payloadu, blokadę dat w okresach closed/locked, zapis w późniejszym otwartym okresie, stabilność hasha i odrzucenie payload drift przy retry, dokładne pojedyncze storno zerujące każde konto, izolację tenantów oraz niezmienność i zakaz usuwania posted nagłówków i linii)*

**Gate: SPEŁNIONY.** Nie da się utworzyć niezbilansowanego ani zmienić posted journal; niezmienniki są pokryte testami integracyjnymi silnika i ograniczeniami schematu.

### Faza 4 — bank reconciliation (1–2 tygodnie)

**Cel:** wpływy i wydatki można uzgodnić z bankiem bez ręcznego przepisywania.

- [x] `UKFIN-401` [M] BankAccount model i bezpieczne maskowanie danych. *(tenantowy model wiąże rachunek z aktywnym kontem księgowym typu cash, walutą, saldem otwarcia, statusem i metadanymi synchronizacji; pełny 8-cyfrowy numer rachunku i 6-cyfrowy sort code istnieją wyłącznie podczas walidacji, baza przechowuje tylko końcówki 4/2 oraz HMAC fingerprint do deduplikacji, panel nigdy nie odczytuje ani nie ujawnia danych pełnych, edycja zachowuje maski bez ponownego podawania sekretów, archiwizacja zastępuje usunięcie, a RBAC, policy, tenant scope i audyt referencji chronią dostęp)*
- [x] `UKFIN-402` [M] CSV importer z mapowaniem kolumn i dry-run. *(akcja na rachunku przyjmuje prywatny plik CSV do 5 MB/5000 wierszy, wykrywa BOM, kodowanie UTF-8/Windows-1252 i delimiter, obsługuje ręczne lub automatyczne mapowanie daty, kwoty albo debit/credit, waluty, opisu, kontrahenta i provider ID, normalizuje brytyjskie formaty dat/kwot, raportuje błędy per wiersz, a domyślny dry-run nie zapisuje ani partii, ani transakcji; właściwy import zapisuje wyłącznie zminimalizowane dane i audyt partii, po czym usuwa plik tymczasowy)*
- [x] `UKFIN-403` [M] Deduplikacja bank transactions po provider ID/hash. *(tenantowy `BankTransaction` ma unikalność per rachunek zarówno po opcjonalnym provider ID, jak i stabilnym SHA-256 danych kanonicznych; importer wykrywa duplikaty istniejące i wewnątrz jednego pliku, baza chroni wyścigi przez unique constraints i `insertOrIgnore`, a audit command sprawdza własność rachunku oraz partii importu)*
- [x] `UKFIN-404` [M] Matching rules: reference, amount, date, counterparty. *(tenantowy silnik tworzy deterministycznie posortowanych kandydatów 0–100 dla wpłat klientów, zwrotów klientom, płatności dostawców i zwrotów od dostawców; najpierw odrzuca przeciwny kierunek i nieporównywalną walutę, potem punktuje kwotę w pensach, odległość dat w oknie 30 dni, znormalizowaną referencję i kontrahenta, obsługuje zapisany snapshot `amount_gbp`, klasy confidence exact/high/medium/low oraz częściowe kwoty od 40 punktów; akcja wymaga aktywnego membership, `view_finance` i transakcji `unmatched`, nie zapisując jeszcze uzgodnienia)*
- [x] `UKFIN-405` [M] UI reconcile: exact, partial, split i many-to-many. *(tenantowe reconciliation batches przechowują audyt autora, czasu, waluty i notatki, a polymorphic allocations łączą transakcje z customer payments/refunds, supplier payments i supplier refunds; transakcyjna akcja blokuje bank transaction oraz źródła, ponownie oblicza dostępne kwoty i odrzuca over-allocation, zapisując osobno kwotę bankową i źródłową dla snapshotów GBP; statusy unmatched/partially_reconciled/reconciled, Filament list/view z filtrami i modalem wielu alokacji, RBAC, tenant audit oraz testy exact/partial/split/many-to-many/FX domykają workflow)*
- [x] `UKFIN-406` [M] Create expense/payment from unmatched transaction. *(atomowa akcja dla w pełni nieuzgodnionego wpływu tworzy customer payment do zaksięgowanej faktury, a dla wypływu kompletny expense dostawcy z kalkulacją gross-to-net VAT, approve/post/payment; płatności zachowują `bank_account_id` i księgują na cash ledger konkretnego rachunku, obsługują same-currency oraz snapshot GBP/foreign, po czym tworzą reconciliation; workflow ma RBAC `manage_finance` + `post_finance`, blokady, pełny rollback, tenant audit, panel Filament PL/EN/PT i testy)*
- [x] `UKFIN-407` [M] Undo reconciliation z audit trail. *(aktywny reconciliation batch można atomowo oznaczyć jako `reversed` z autorem, czasem i wymaganym uzasadnieniem; historyczne alokacje pozostają niezmienne i nieusuwalne, ale przestają zmniejszać dostępne kwoty, a status transakcji jest przeliczany z pozostałych aktywnych batchy; akcja ma tenant/RBAC, deterministyczne blokady transakcji, batcha i źródeł, idempotencję, kontrolę integralności i rollback, panel Filament z pełnym audit trail, audit command, PL/EN/PT i testy exact/partial/split/UI)*
- [x] `UKFIN-408` [M] Bank balance/reconciliation report. *(tenantowy raport Filament liczy per rachunek saldo otwarcia z zapisanej bazy i wcześniejszych importów, wpływy, wypływy, saldo zamknięcia oraz kwoty uzgodnione i pozostałe; szczegóły transakcji przeliczają status z aktywnych alokacji, ignorując cofnięte batche, rachunki walutowe pozostają rozdzielone, a filtr okresu/rachunku, CSV, `view_finance`, Form Request, PL/EN/PT i testy tenant/RBAC domykają bezpieczny odczyt)*
- [x] `UKFIN-409` [M] Testy duplicates, split, FX, reversal i tenant isolation. *(przekrojowy kontrakt regresyjny potwierdza deduplikację provider ID/SHA-256 per rachunek i tenant, split → reversal → pełne ponowne uzgodnienie z niezmienną historią, częściowy FX `83.33 GBP ↔ 100.00 EUR` bez dryfu pensa oraz raport i capacity oparte wyłącznie o aktywne batche; bank suite 48/326, tenant audit bez nieprawidłowości, Finance 211 passed + 1 skipped, pełny regres bez nowych błędów)*

**Po MVP:** Open Banking przez licencjonowanego agregatora zamiast budowania bezpośredniego TPP od zera.

### Faza 5 — raporty finansowe i VAT workspace (1–2 tygodnie)

**Cel:** system daje sprawdzalne raporty i robocze 9 pól VAT Return.

- [ ] `UKFIN-501` [M] Trial Balance per period i comparison.
- [ ] `UKFIN-502` [M] Profit & Loss.
- [ ] `UKFIN-503` [M] Balance Sheet.
- [ ] `UKFIN-504` [M] Cash Flow w wersji zarządczej.
- [ ] `UKFIN-505` [M] Aged Receivables i Aged Payables.
- [ ] `UKFIN-506` [M] VAT Detail: output/input VAT, tax codes i dokumenty źródłowe.
- [ ] `UKFIN-507` [M] VatReturnCalculationAction dla 9 pól.
- [ ] `UKFIN-508` [M] VAT adjustments z reason, attachment i approval.
- [ ] `UKFIN-509` [M] VAT return review/lock/snapshot workflow.
- [ ] `UKFIN-510` [M] Accountant pack ZIP: CSV/XLSX/PDF + attachments manifest + hashes.
- [ ] `UKFIN-511` [M] Golden dataset testowany względem ręcznie zaakceptowanych wyników księgowego.

**Gate:** wszystkie 9 pól uzgodnione z ledgerem i zaakceptowanym zestawem referencyjnym.

### Faza 6 — HMRC MTD VAT (2–3 tygodnie + czas na dostęp produkcyjny)

**Cel:** użytkownik pobiera obligations i świadomie składa VAT Return do HMRC.

- [ ] `UKFIN-601` [M] Rejestracja aplikacji HMRC Developer Hub i konfiguracja sandbox.
- [ ] `UKFIN-602` [M] HmrcClientInterface + sandbox/production clients.
- [ ] `UKFIN-603` [M] OAuth connect/callback/refresh/revoke.
- [ ] `UKFIN-604` [M] Encrypted token storage i redacted logs.
- [ ] `UKFIN-605` [M] Fraud-prevention headers builder + test endpoint.
- [ ] `UKFIN-606` [M] Retrieve VAT obligations i synchronizacja periods.
- [ ] `UKFIN-607` [M] View VAT return/liabilities/payments.
- [ ] `UKFIN-608` [M] Submit VAT return z review, declaration i step-up confirmation.
- [ ] `UKFIN-609` [M] Idempotency, timeout recovery i submission status reconciliation.
- [ ] `UKFIN-610` [M] HMRC audit log i receipt download.
- [ ] `UKFIN-611` [M] Sandbox scenarios: success, invalid VRN, expired token, duplicate, 5xx, timeout.
- [ ] `UKFIN-612` [M] Security review, penetration test i production access checklist.
- [ ] `UKFIN-613` [M] Pilot na jednej firmie i jednej deklaracji pod nadzorem księgowego.

**Gate:** brak automatycznych submission; produkcja dopiero po pozytywnym sandboxie i zgodzie właściciela.

### Faza 7 — środki trwałe i zamknięcie roku (opcjonalnie, 1–2 tygodnie)

- [ ] `UKFIN-701` [M] FixedAsset i kategorie aktywów.
- [ ] `UKFIN-702` [M] Acquisition/disposal workflow.
- [ ] `UKFIN-703` [M] Book depreciation schedules.
- [ ] `UKFIN-704` [M] Asset register report.
- [ ] `UKFIN-705` [M] Year-end close i opening balance journals.
- [ ] `UKFIN-706` [M] Accountant adjustments import.

Capital allowances i finalne reguły podatkowe pozostają poza automatyczną księgą bez osobnej specyfikacji zaakceptowanej przez księgowego.

### Faza 8 — Income Tax / Corporation Tax / PAYE / CIS (opcjonalne strumienie)

#### A. MTD Income Tax

- digital records dla self-employment/property;
- quarterly updates;
- HMRC business details, obligations, summaries i final declaration;
- osobny eligibility engine według entity type i qualifying income;
- pełna implementacja tylko po potwierdzeniu dostępu do właściwych API i aktualnej ścieżki produkcyjnej.

#### B. Corporation Tax

- tax adjustments workspace, disallowable expenses i capital allowances;
- tax computation i CT600 mapping;
- iXBRL accounts/computations;
- XML submission adapter;
- w pierwszej wersji wyłącznie export pack dla księgowego, bez automatycznego filing.

#### C. PAYE/payroll

- employees, employments, tax codes, NI categories, pay runs;
- FPS/EPS, corrections, YTD figures, payslips, starter/leaver;
- wersjonowane rates/thresholds per tax year;
- osobny silnik testowany na oficjalnych test packs.

#### D. CIS

- subcontractors, verification status i deduction rate;
- payments/material costs/deductions;
- monthly return/nil return;
- statements dla subcontractors i reconciliation.

Każdy z czterech strumieni wymaga osobnego planu technicznego, konsultacji księgowej i własnego release gate.

---

## 8. Warstwa aplikacyjna — proponowana struktura

```text
app/
  Actions/Finance/
    ApproveExpenseAction.php
    PostExpenseAction.php
    PostInvoiceAction.php
    ReverseJournalAction.php
    ReconcileBankTransactionAction.php
    CalculateVatReturnAction.php
    SubmitVatReturnAction.php
  DataTransferObjects/Finance/
  Models/
    AccountingProfile.php
    AccountingPeriod.php
    Supplier.php
    Expense.php
    ExpenseItem.php
    TaxCode.php
    LedgerAccount.php
    JournalEntry.php
    JournalLine.php
    BankAccount.php
    BankTransaction.php
    VatPeriod.php
    VatReturn.php
    HmrcConnection.php
  Services/Finance/
    VatCalculator.php
    PostingEngine.php
    ExchangeRateService.php
    BankImportService.php
    ReconciliationService.php
  Services/Hmrc/
    HmrcClientInterface.php
    HmrcVatClient.php
    HmrcFraudPreventionHeaders.php
  Filament/Resources/
  Policies/
  Jobs/Finance/
lang/{pl,en,pt}/finance.php
tests/Feature/Finance/
tests/Unit/Finance/
```

Kontrolery i Filament Pages mają jedynie walidować/autoryzować oraz wywoływać Actions. Całe posting, VAT calculation, reconciliation i submission pozostają w Actions/Services.

---

## 9. UI i role

### Nawigacja Filament

```text
Finance
├── Dashboard
├── Sales
│   ├── Invoices
│   ├── Credit Notes
│   └── Payments Received
├── Purchases
│   ├── Expenses / Bills
│   ├── Suppliers
│   └── Purchase Credits
├── Banking
│   ├── Bank Accounts
│   ├── Import
│   └── Reconciliation
├── Accounting
│   ├── Chart of Accounts
│   ├── Journals
│   └── Periods
├── VAT
│   ├── VAT Workspace
│   ├── Returns
│   └── HMRC Connection
└── Reports
```

### Uprawnienia

- `finance.view` — odczyt;
- `finance.manage_sales`;
- `finance.manage_purchases`;
- `finance.approve_expenses`;
- `finance.reconcile_bank`;
- `finance.post_journals`;
- `finance.close_periods`;
- `finance.manage_tax_settings`;
- `finance.submit_vat_return` — tylko owner/admin/accountant;
- `finance.export_accountant_pack`.

Należy rozważyć osobną rolę `accountant`, która ma dostęp do finansów bez dostępu do CRM, treści i konfiguracji technicznej.

---

## 10. Testy i kryteria jakości

### Testy obowiązkowe

- unit tests dla VAT, rounding, FX i posting rules;
- feature tests dla Actions, approvals, posting, period locks i submissions;
- property/invariant tests: journal zawsze się bilansuje;
- tenant isolation dla każdego modelu i raportu;
- authorization: read/manage/approve/post/submit;
- concurrency tests dla numeracji i idempotency;
- file upload security i private downloads;
- HMRC sandbox contract tests;
- golden datasets zaakceptowane przez księgowego;
- backup/restore test dla finansowych tabel i attachments;
- test eksportu pełnego okresu po 6 latach retencji bez utraty referencji.

### Definition of Done dla każdego etapu

- [ ] migracje reversible i przetestowane na SQLite oraz docelowym MySQL;
- [ ] `business_id` i indeksy sprawdzone;
- [ ] policies i permissions działają;
- [ ] pl/en/pt kompletne;
- [ ] brak `any` w nowych komponentach TypeScript;
- [ ] Pint, ESLint, Prettier i testy dotkniętej domeny przechodzą;
- [ ] logi nie zawierają sekretów ani pełnych identyfikatorów podatkowych;
- [ ] dokumentacja i live-docs zaktualizowane;
- [ ] księgowy zaakceptował reguły podatkowe dotknięte zmianą.

---

## 11. Migracja istniejących danych

1. Wykonać read-only preflight i raport liczby dokumentów bez business/client/project.
2. Utworzyć backup bazy oraz checksum eksportu.
3. Dodać nowe kolumny jako nullable.
4. Backfill `business_id` przez klienta, projekt, fakturę lub zamówienie domeny.
5. Rekordy niejednoznaczne przenieść do kolejki manual review, nie przypisywać automatycznie do pierwszej firmy.
6. Przekształcić obecny `vat_rate` faktury w tax code snapshot na każdej pozycji.
7. Utworzyć opening balances zamiast generowania sztucznych historycznych journals, jeśli pełna historia nie jest wystarczająca.
8. Porównać sumy faktur, płatności i sald przed/po migracji.
9. Dopiero po zgodności ustawić constraints i uruchomić nowy posting engine.
10. Zachować skrypt rollbacku oraz eksport mapowania stary ID → nowy journal ID.

---

## 12. Bezpieczeństwo i prywatność

- szyfrowanie UTR, PAYE refs, HMRC tokens i provider credentials;
- osobne klucze/config dla sandbox i production;
- private object storage z signed URLs o krótkim czasie życia;
- malware scan załączników przed udostępnieniem;
- step-up authentication przed HMRC submission, period lock i zmianą tax settings;
- reautoryzacja po zmianie rachunku bankowego lub danych prawnych;
- immutable audit trail bez możliwości usuwania przez zwykłego admina;
- redaction eksportów supportowych;
- alerty dla failed submission, expired token, unreconciled balance i zmiany w zamkniętym okresie;
- backup szyfrowany i regularnie testowany restore;
- polityka incident response dla HMRC/Open Banking.

---

## 13. Deployment i obserwowalność

- feature flags: `finance_expenses`, `finance_ledger`, `finance_vat`, `hmrc_vat`;
- migracje expand/backfill/contract zamiast jednego ryzykownego deployu;
- shadow posting: przez jeden okres tworzyć journals bez wpływu na UI i porównywać wyniki;
- metryki: unbalanced attempts, posting failures, duplicate imports, reconciliation rate, HMRC latency/errors;
- kolejki oddzielne: `finance`, `hmrc`, `documents`;
- dead-letter/retry dashboard bez automatycznego ponawiania submission;
- health check HMRC tylko jako diagnostyka, bez wysyłania deklaracji;
- rollback nie może usuwać posted journals ani HMRC receipts.

---

## 14. Estymata i rekomendowana kolejność

| Zakres | Estymata solo dev | Wynik |
|---|---:|---|
| Faza 0 | 1 tydzień | bezpieczny tenant i zatwierdzona domena |
| Faza 1 | 1–2 tygodnie | poprawne dokumenty sprzedażowe UK |
| Faza 2 | 1–2 tygodnie | koszty, dostawcy, dowody |
| Faza 3 | 2 tygodnie | double-entry ledger |
| Faza 4 | 1–2 tygodnie | CSV bank reconciliation |
| Faza 5 | 1–2 tygodnie | raporty + VAT workspace |
| Faza 6 | 2–3 tygodnie | HMRC MTD VAT + pilot |
| Bufor 20% | 2–3 tygodnie | integracje, poprawki, review |

**Core bookkeeping bez HMRC:** około 7–10 tygodni.  
**Core + MTD VAT:** około 10–14 tygodni plus zewnętrzny czas oczekiwania na dostęp/akceptację HMRC.  
**Pełny zakres z CT, Income Tax, payroll i CIS:** osobny program co najmniej 4–6 miesięcy, wymagający dalszej specyfikacji.

### Rekomendowany pierwszy release

Fazy 0–2 jako **Finance Records MVP**:

- bezpieczne faktury UK;
- mixed VAT i credit notes;
- suppliers, expenses i attachments;
- raport sprzedaży/kosztów;
- eksport dla księgowego.

Następnie Fazy 3–5 jako **Accounting Core**, a dopiero potem Faza 6 jako **MTD VAT**.

---

## 15. Kryteria decyzji przed implementacją

Przed rozpoczęciem Fazy 0 użytkownik powinien zatwierdzić:

1. czy pierwszym odbiorcą jest limited company, sole trader, czy oba typy;
2. czy system ma zastąpić księgowość, czy przygotowywać dane dla zewnętrznego księgowego;
3. czy firma jest VAT registered i z jakiego VAT scheme korzysta;
4. czy podstawą ma być accrual czy cash accounting;
5. czy walutą funkcjonalną zawsze będzie GBP;
6. czy bank reconciliation zaczynamy od CSV, czy od razu od agregatora Open Banking;
7. czy HMRC MTD VAT ma wejść do pierwszej wersji produkcyjnej;
8. czy payroll i CIS są realną potrzebą, czy wyłącznie przyszłym rozszerzeniem;
9. kto pełni rolę księgowego-reviewera i zatwierdza golden datasets.

---

## 16. Rekomendacja końcowa

Nie należy zaczynać od HMRC API ani od automatycznego wyliczania Corporation Tax. Najpierw system musi posiadać kompletne, cyfrowe i niezmienne źródło danych: sprzedaż, zakupy, płatności, bank, tax codes i zbilansowany ledger. Dopiero na tej podstawie raport VAT oraz submission do HMRC będą sprawdzalne i możliwe do audytu.

Najbezpieczniejszy kierunek to:

```text
Tenant hardening
  → UK invoices + expenses
  → double-entry ledger
  → bank reconciliation
  → financial/VAT reports
  → HMRC MTD VAT
  → opcjonalnie CT / Income Tax / PAYE / CIS
```

