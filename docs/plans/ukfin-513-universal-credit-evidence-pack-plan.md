# UKFIN-513 - paczka dowodowa Universal Credit dla sole tradera

**Status:** Implementacja w toku - UKFIN-513A ukończone lokalnie
**Data:** 2026-11-23  
**Projekt:** Website Expert NI  
**Grupa docelowa:** Sole trader w Irlandii Północnej  
**UI:** polski i angielski, z zachowaniem parytetu istniejących kluczy portugalskich

## 1. Cel

Dodać funkcję, która dla dokładnego miesięcznego okresu rozliczeniowego Universal Credit przygotuje jedną prywatną paczkę ZIP zawierającą:

- podsumowanie przychodów i kosztów do przepisania do konta Universal Credit;
- szczegółową listę pozycji uwzględnionych i wykluczonych;
- faktury, rachunki, paragony, wyciągi bankowe i inne dowody;
- uzgodnienie raportowanych kwot z płatnościami, gotówką i rachunkami bankowymi;
- raport brakujących dowodów, nierozliczonych operacji i pozycji wymagających decyzji;
- manifest plików oraz sumy SHA-256 pozwalające wykryć zmianę paczki.

Funkcja nie wysyła danych do Universal Credit. Oficjalna ścieżka nadal wymaga zalogowania do konta UC i wykonania zadania `Report your income and expenses`. System przygotuje wartości i dowody, a po ręcznym zgłoszeniu pozwoli zapisać datę oraz potwierdzenie.

## 2. Oficjalna podstawa wymagań

Plan opiera się na oficjalnych informacjach aktualnych na 2026-11-23:

- osoba samozatrudniona raportuje przychody i koszty co miesiąc, także gdy w okresie wynoszą zero;
- okres obejmuje wszystko faktycznie otrzymane lub zapłacone w datach wskazanych przez UC, niezależnie od daty wykonania pracy lub wystawienia faktury;
- dowodami mogą być między innymi rachunki, faktury i rachunki bankowe;
- przychód obejmuje płatności kartą, gotówką, czekiem i przelewem, zapłatę rzeczową, napiwki, określone zwroty podatku lub National Insurance, dotacje oraz wskazane zbycia aktywów;
- koszt musi być związany z biznesem, konieczny i odpowiedni oraz nie może być nadmierny;
- przy koszcie mieszanym raportuje się tylko część biznesową;
- podatnik VAT wybiera wariant z VAT albo bez VAT i musi stosować go konsekwentnie;
- uproszczone koszty mogą dotyczyć pojazdów, pracy z domu i mieszkania w miejscu prowadzenia działalności.

Źródła:

- [Report business income and expenses if you're self-employed - nidirect](https://www.nidirect.gov.uk/articles/report-business-income-and-expenses-if-youre-self-employed)
- [Business expenses you can report if you're self-employed - nidirect](https://www.nidirect.gov.uk/articles/business-expenses-you-can-report-if-youre-self-employed)
- [Business records if you're self-employed - GOV.UK](https://www.gov.uk/self-employed-records/what-records-to-keep)
- [Expenses if you're self-employed - GOV.UK](https://www.gov.uk/expenses-if-youre-self-employed/how-to-claim)

Reguły UC nie są tym samym co księgowy Profit and Loss ani wyliczenie podatku dla Self Assessment. Przykładowo lista UC obejmuje faktycznie zapłacony Income Tax, wybrane składki National Insurance i składki emerytalne. Nie są to zwykłe koszty księgowe P&L. Obecnego P&L nie wolno więc przedstawiać jako raportu UC przez samo przemianowanie kolumn.

## 3. Zakres

### W zakresie UKFIN-513

- wyłącznie firma z `AccountingProfile.entity_type = sole_trader`;
- jeden jawny assessment period z datą `from` i `to`, włącznie z obiema datami;
- kalkulacja według dnia faktycznego otrzymania lub zapłaty, niezależna od księgowego `cash/accrual`;
- wpływy bankowe, kartowe, gotówkowe, czeki i płatności rzeczowe;
- koszty z rachunku firmowego, gotówki i prywatnych środków właściciela;
- osobna klasyfikacja UC, która nie zmienia kont ledgeru ani klasyfikacji podatkowej;
- wariant VAT inclusive albo VAT exclusive;
- rzeczywiste koszty, procent wykorzystania biznesowego i uproszczone koszty;
- PDF, XLSX, CSV, dokumenty źródłowe oraz manifest w ZIP;
- wersja robocza i finalna, niezmienna paczka po review;
- UI PL/EN oraz parytet kluczy PT;
- tenant isolation, RBAC, audit trail, prywatny storage i skan antywirusowy.

### Poza zakresem

- automatyczne wysyłanie danych do UC lub wyliczanie kwoty świadczenia;
- decyzja prawna, czy wydatek jest dopuszczalny w indywidualnej sytuacji;
- Self Assessment, MTD Income Tax, payroll i prywatne dochody właściciela;
- partnership i podział wartości między partnerów;
- OCR podejmujący automatyczne decyzje podatkowe;
- modyfikacja oryginalnych dokumentów źródłowych.

## 4. Delta analysis

### Elementy do ponownego użycia

- `GenerateAccountantPackAction` jako wzorzec bezpiecznego ZIP, ponownej weryfikacji hashy i manifestu. Obecna akcja pozostaje paczką VAT.
- `FinanceReportDocumentFactory` i kontrolery eksportów jako wspólny model PDF, XLSX i CSV.
- Generowane PDF faktur i sales credit notes.
- `FinancialAttachmentService` z prywatnym storage, SHA-256 i skanem bezpieczeństwa.
- Ledger, expense payments, bank transactions i reconciliation jako źródła przepływów.
- Owner-funded expense payment dla kosztu zapłaconego prywatnie przez sole tradera.
- Istniejące tenant scopes, audit i permissions.

### Luki

1. Obecna paczka księgowa zależy od locked VAT Return, a nie od assessment period UC.
2. Obecny P&L nie odwzorowuje zasad faktycznie otrzymanych i zapłaconych kwot UC.
3. Brakuje osobnej klasyfikacji UC oraz pozycji `excluded` i `review_required`.
4. Brakuje kontrolowanego procentu business use i uzasadnienia podziału kosztu mieszanego.
5. Brakuje mileage, godzin pracy z domu i osób mieszkających w miejscu prowadzenia firmy.
6. Brakuje dedykowanego zapisu barteru, napiwku, dotacji, zwrotu tax/NI i innych nietypowych wpływów.
7. Oryginalny CSV banku jest usuwany po imporcie. Zachowane transakcje i hash nie pozwalają odtworzyć pliku.
8. Brakuje polityki VAT dla UC i kontroli jej konsekwentnego stosowania.
9. Brakuje wersjonowanych stawek UC obowiązujących w dacie okresu.
10. `view_finance` jest zbyt szerokie dla zbiorczego eksportu dokumentów i wyciągów.
11. Brakuje review, lock, oznaczenia jako zgłoszone oraz załączenia potwierdzenia UC.

## 5. Decyzje projektowe

### Osobny workflow UC

Powstanie `Universal Credit evidence pack`. Nie będzie to tryb obecnej paczki VAT, ponieważ oba produkty mają inne okresy, zasady i odbiorców.

### Data przepływu jest nadrzędna

- przychód trafia do okresu według daty otrzymania;
- koszt trafia do okresu według daty zapłaty;
- data faktury, tax point i posting date pozostają informacyjne;
- częściowa płatność raportuje wyłącznie otrzymaną lub zapłaconą część;
- refund lub reversal trafia do okresu faktycznego przepływu i nie zmienia starej finalnej paczki.

### Niezależna klasyfikacja UC

Mapowanie UC będzie nakładką na źródło i nie zmieni journalu, expense category ani VAT Return. Każda pozycja zachowa:

- typ `income`, `expense`, `excluded` albo `review_required`;
- kategorię UC;
- kwotę brutto, VAT, kwotę raportowaną i snapshot GBP;
- metodę `actual`, `business_use_percentage`, `simplified` albo `manual`;
- procent biznesowy i uzasadnienie, jeżeli dotyczą;
- płatność, bank transaction, dokumenty i dowody;
- wersję polityki, która wyliczyła wynik.

Automatyczne mapowanie z konta lub kategorii kosztu będzie tylko podpowiedzią. Finalizacja wymaga review i nie może uznać kosztu za dopuszczalny wyłącznie na podstawie nazwy konta.

### Polityka VAT

- `vat_inclusive`: raportowane są kwoty z VAT, a płatności VAT do HMRC i zwroty VAT stanowią osobne pozycje zgodnie z regułami UC;
- `vat_exclusive`: raportowane są kwoty bez odzyskiwalnego VAT, a ruchy rozliczeniowe VAT nie są doliczane drugi raz.

Metoda jest wersjonowana od wskazanej daty. Zmiana nie przelicza finalnych paczek, wywołuje ostrzeżenie i wymaga uzasadnienia.

### Wersjonowane stawki

Flat rates i limity będą zapisane jako audytowalna polityka z `effective_from`. Pierwsza wersja odwzoruje aktualne nidirect, w tym:

- samochód lub van: 45p za milę do 833 mil miesięcznie i 25p powyżej;
- motorbike lub scooter: 24p za milę;
- aktualne progi i stawki pracy z domu;
- aktualne odliczenia dla mieszkania w miejscu prowadzenia firmy;
- aktualny miesięczny limit business loan interest;
- zakaz łączenia simplified expense z objętym nim kosztem faktycznym.

Zmiana oficjalnych zasad utworzy nową wersję. Stara paczka zachowa snapshot poprzedniej polityki.

## 6. Model danych

### `universal_credit_profiles`

- tenant, `is_enabled` i `vat_reporting_method`;
- `vat_method_effective_from`;
- opcjonalny domyślny dzień początku okresu, wyłącznie jako podpowiedź;
- autorzy i timestamps.

Daty każdego okresu muszą zostać potwierdzone na podstawie konta UC.

### `universal_credit_assessment_periods`

- tenant, numer, `date_from` i `date_to`;
- status `draft`, `in_review`, `locked`, `reported` lub `superseded`;
- snapshot VAT method i policy version;
- income, expenses i net w pensach GBP;
- source snapshot hash;
- aktorzy i czasy review, lock i report;
- opcjonalna referencja lub notatka z potwierdzenia;
- kontrola nakładania okresów.

Okres z zerowym income i expenses jest poprawny i może zostać zablokowany oraz wyeksportowany.

### `universal_credit_report_items`

- tenant i assessment period;
- direction, category i classification status;
- source morph do payment, expense payment, bank transaction, manual entry lub fixed asset transaction;
- cash date i document date;
- original amount/currency oraz snapshot GBP;
- gross, VAT, business share i reportable amount w pensach;
- method, business-use percent, reason i policy version;
- dowody i reconciliation IDs;
- review actor/time;
- idempotency key zapobiegający podwójnemu ujęciu źródła.

### `universal_credit_manual_entries`

Kontrolowany model dla gotówki, barteru, tips, grant/subsidy, business-related tax/NI refund, asset transfer/sale, tax/NI/pension/VAT payment i korekt. Wpis wymaga daty, kwoty, kategorii, opisu, autora oraz dowodu albo jawnego powodu jego braku.

Jeśli zdarzenie powinno być również w ledgerze, preflight pokaże brak powiązania zamiast tworzyć journal w tle.

### `universal_credit_simplified_expenses`

- typ `vehicle_mileage`, `home_working` albo `business_premises_living`;
- dane wejściowe, wersja polityki i snapshot progów;
- wyliczona kwota GBP;
- dowód lub dziennik pomocniczy;
- kontrola braku podwójnego ujęcia kosztu faktycznego.

### `universal_credit_evidence_packs` i manifest items

Pack zapisze status, hash snapshotu, prywatną ścieżkę, SHA-256 ZIP, rozmiar, liczby included/excluded, autora, czasy i sanitizowany kod błędu. Manifest item zapisze logiczną ścieżkę, źródło, inclusion status/reason, rozmiar i hash. Usunięcie wygasłego ZIP nie usunie audytu.

### Retencja oryginalnych wyciągów

Po poprawnym imporcie innym niż dry-run oryginalny plik zostanie prywatnym `FinancialAttachment` powiązanym z `BankStatementImport`, z typem `bank_statement`, SHA-256 i skanem bezpieczeństwa. Dry-run nadal usunie plik.

Starych plików nie da się odtworzyć z bazy. Ich transakcje pozostaną w raporcie, ale manifest pokaże `source_not_retained`. Użytkownik będzie mógł ponownie dołączyć oryginał po kontroli hasha lub zapisać przyczynę braku.

## 7. Kategorie UC

### Przychody

- goods and services paid by bank/card/cheque/cash;
- payment in kind;
- tips and gratuities;
- business-related Income Tax or National Insurance refunds;
- taxable grants and subsidies;
- sale or transfer of an asset previously reported as an expense;
- other business income;
- excluded personal/non-trading receipt;
- review required.

### Koszty

- car/minicab simplified travel;
- other vehicle and business travel;
- place of business;
- dopuszczone przez UC tax, National Insurance, pension i VAT payments;
- legal, accounting, insurance, banking and loan interest;
- office and equipment;
- staff and subcontractors;
- goods, raw materials and work clothing;
- payment in kind;
- marketing and advertising;
- professional subscriptions and relevant training;
- use of home for business;
- living at business premises;
- other necessary and appropriate business expense;
- excluded/disallowed;
- review required.

System pokaże osobno kwotę zapłaconą, część prywatną, część niedopuszczalną, VAT i końcową kwotę UC. Przykładowy rachunek internetowy £36 zapłacony gotówką, z 50 procent business use, da £18 reportable.

## 8. Zawartość ZIP

```text
README.txt
manifest.sha256.json
01-summary/
  universal-credit-summary.pdf
  universal-credit-summary.xlsx
  universal-credit-summary.csv
  review-and-declaration.pdf
02-details/
  income-detail.csv
  expense-detail.csv
  excluded-and-private.csv
  bank-and-cash-reconciliation.xlsx
  missing-evidence-and-warnings.csv
  calculation-policy.pdf
03-evidence/
  income/invoices/
  income/other/
  expenses/receipts-and-invoices/
  expenses/other/
  banking/statements/
  simplified-expenses/
  owner-funded/
04-audit/
  source-inventory.csv
  exclusions.csv
  generation-metadata.json
```

Podsumowanie zawiera firmę, dokładne daty, VAT method, kategorie, totals, liczbę dowodów i ostrzeżenia. Nie zawiera pełnego UTR, NINO, numerów kart ani sekretów. Każdy plik korzysta z jednego immutable snapshotu. README wyjaśnia, że ZIP nie jest potwierdzeniem zgłoszenia do UC.

## 9. Dobór dokumentów

Automatycznie dołączane będą:

- PDF faktury lub sales credit note dla otrzymanej płatności;
- czyste purchase invoices, receipts i credit notes zapłaconych kosztów;
- dowody płatności i refundów;
- oryginalne, czyste wyciągi obejmujące okres;
- dokumenty owner-funded i cash expenses;
- dowody VAT, tax, NI, pension, grant i asset transactions;
- dzienniki kalkulacji uproszczonych kosztów.

Plik `pending`, `rejected`, brakujący, o niezgodnym hashu, z innego tenanta albo niezwiązany z okresem nie trafi do ZIP. Każdy brak będzie widoczny w raporcie.

Historyczny PDF faktury musi korzystać z danych z chwili wystawienia. Jeśli model nie ma kompletnego snapshotu nazw i adresów, trzeba go dodać albo oznaczyć dokument jako `system-generated copy` z datą wygenerowania. Aktualnych danych nie wolno przedstawiać jako historycznych.

## 10. Workflow

1. Owner włącza `Universal Credit reporting` i wybiera metodę VAT.
2. Tworzy okres z dokładnymi datami pokazanymi w koncie UC.
3. System zbiera faktycznie otrzymane i zapłacone kwoty.
4. Użytkownik dopisuje gotówkę, barter, tips i zdarzenia bez standardowej płatności.
5. Klasyfikuje pozycje, określa business use lub wybiera simplified expense.
6. System uzgadnia pozycje z bankiem, dokumentami i ledgerem.
7. Preflight pokazuje błędy, ostrzeżenia i wartości do wpisania do UC.
8. Użytkownik wykonuje review i lock. Powstaje immutable snapshot oraz hash.
9. Job kolejki generuje prywatny ZIP z `Cache-Control: no-store`.
10. Użytkownik ręcznie raportuje dane w Universal Credit.
11. Oznacza okres jako `reported` i opcjonalnie dodaje potwierdzenie.

Korekta finalnego okresu tworzy wersję superseding z powodem. Nie zmienia starej paczki.

## 11. Preflight

### Blokery finalizacji

- firma nie jest sole traderem;
- okresy nakładają się lub mają nieprawidłowe daty;
- firma VAT registered nie ma aktywnej metody UC VAT;
- istnieje `review_required`;
- źródło ujęto dwa razy;
- reportable amount nie uzgadnia się z przepływem lub wpisem ręcznym;
- actual i simplified expense obejmują ten sam koszt;
- dokument ma obcy tenant, błędny hash albo malware;
- przekroczono bezpieczny limit paczki.

### Ostrzeżenia wymagające potwierdzenia

- brak dokumentu wraz z zapisanym powodem;
- historyczny bank statement nie był zachowany;
- bank transaction została jawnie uznana za prywatną lub poza biznesem;
- koszt gotówkowy lub owner-funded nie ma bank trace;
- nietypowy business-use percentage;
- VAT method zmieniła się od poprzedniego okresu;
- pozycja nie ma właściwego powiązania z ledgerem;
- okres ma zero income i expenses.

Preflight ocenia kompletność, nie potwierdza akceptacji wydatku przez DWP.

## 12. Bezpieczeństwo i operacje

- permissions: `view_universal_credit_reporting`, `manage_universal_credit_reporting`, `export_universal_credit_pack`;
- domyślnie owner/admin, bookkeeper dopiero po jawnym nadaniu;
- tenant authorization przy preview, lock, generation i każdym downloadzie;
- jeden aktywny build per business/period;
- prywatny storage, losowa nazwa techniczna i ochrona zip-slip;
- ponowna kontrola SHA-256 przy kopiowaniu dokumentu;
- ochrona CSV przed formula injection i typowane komórki XLSX;
- brak NINO, UTR, numerów kart, tokenów i pełnych danych bankowych w logach;
- rekomendowany czas downloadu 7 dni;
- cleanup usuwa ZIP, ale zachowuje audit i manifest;
- dokumenty źródłowe zachowują obecną politykę retencji finansowej.

## 13. Etapy implementacji

### UKFIN-513A - profil i reguły - ukończone lokalnie 2026-11-23

1. Dodać profil UC, assessment periods i wersjonowane policies.
2. Dodać kategorie income/expense i pierwszą politykę nidirect.
3. Dodać permissions, policies i tenant audit.
4. Dodać VAT inclusive/exclusive z effective date.

Wynik etapu:

- migracja `000040` tworzy profil UC, assessment periods oraz wersjonowane polityki;
- pierwsza polityka `ni_uc_self_employment` zawiera aktualne kategorie, flat rates, limity i oficjalne źródła nidirect;
- reguły są kanonizowane, zabezpieczone SHA-256, niezmienne i kopiowane do snapshotu okresu;
- okresy używają dokładnych dat, maksymalnie 31 dni, nie mogą się nakładać i powstają z zerowymi totals;
- VAT inclusive/exclusive ma effective date i snapshot na okresie;
- dodano osobne uprawnienia view/manage/export, widoczne w edytorze ról;
- profile i okresy mają tenant policies, relacje, blokady zmiany entity type i rozszerzony audit;
- migracja przeszła na lokalnym MySQL, tenant audit wykazał 0 błędów;
- Finance: 418 passed, 1 skipped, 3017 assertions;
- tłumaczenia finance: PL/EN/PT po 1467 identycznych kluczy.

### UKFIN-513B - źródła i klasyfikacja

1. Zbudować source collector dla payments, refunds, expense payments, bank transactions, owner-funded i asset flows.
2. Dodać klasyfikację UC, review i idempotency.
3. Dodać manual entries dla gotówki, barteru, tips, grants i tax/NI refunds.
4. Dodać business-use percentage i reason.
5. Zachowywać committed bank statement jako bezpieczny attachment.

### UKFIN-513C - kalkulatory

1. Zaimplementować VAT inclusive/exclusive.
2. Zaimplementować mileage, home working i living at business premises.
3. Dodać loan-interest limit i pozostałe limity polityki.
4. Zablokować double claim actual/simplified.
5. Użyć arytmetyki w pensach, FX snapshot i kontrolowanych zaokrągleń.

### UKFIN-513D - review i raporty

1. Dodać completeness preflight.
2. Dodać podgląd kategorii, wykluczeń, dowodów i uzgodnień.
3. Dodać review/lock/supersede z kanonicznym SHA-256 snapshotu.
4. Zbudować wspólny model summary/detail/reconciliation.
5. Generować identyczne wartości w PDF, XLSX i CSV.

### UKFIN-513E - generator ZIP

1. Dodać inventory builder dokumentów.
2. Dodać queue job, statusy i kontrolowane retry.
3. Kopiować tylko czyste, ponownie zweryfikowane pliki.
4. Dodać included/excluded manifest i hash każdego pliku.
5. Dodać prywatny download, expiry i scheduled cleanup.

### UKFIN-513F - panel i języki

1. Dodać `Finance > Universal Credit`.
2. Dodać kreator okresu, klasyfikację i simplified expenses.
3. Dodać wskaźniki green/amber/red.
4. Dodać review, lock, generate, download, mark as reported i supersede.
5. Dodać PL/EN oraz parytet kluczy PT.

### UKFIN-513G - testy i gate

1. Przygotować golden dataset zaakceptowany przez księgowego lub doradcę znającego UC w Irlandii Północnej.
2. Dodać instrukcję użytkownika i checklistę dokumentów.
3. Uruchomić security, tenant, queue, report i package tests.
4. Wykonać dry run na izolowanym tenantcie demonstracyjnym.
5. Dopuścić produkcję po porównaniu jednego okresu z formularzem UC.

## 14. Testy akceptacyjne

- granice dat okresu są włączne i nie pobierają pozycji spoza zakresu;
- invoice wystawiona wcześniej trafia do okresu dopiero według daty płatności;
- częściowa płatność raportuje wyłącznie zapłaconą część;
- unpaid expense nie trafia do okresu, ale cash/owner-funded payment trafia;
- refund i reversal używają daty rzeczywistego przepływu;
- pusty okres generuje prawidłowe podsumowanie zerowe;
- VAT inclusive i exclusive dają różne, oczekiwane wyniki bez podwójnego VAT;
- zmiana VAT method nie zmienia poprzedniej finalnej paczki;
- rachunek £36 z 50 procent business use daje £18 reportable;
- mileage stosuje próg i blokuje podwójne ujęcie paliwa;
- home working używa polityki obowiązującej w okresie;
- loan interest pokazuje limit i wykluczoną nadwyżkę;
- barter, tips, grant, tax/NI refund i asset sale są możliwe do wykazania;
- osobisty bank transaction pozostaje widoczny jako reviewed exclusion;
- brak starego wyciągu daje `source_not_retained`;
- pending/rejected/missing attachment nie trafia do ZIP;
- niezgodny hash blokuje build;
- totals w PDF, XLSX i CSV są identyczne;
- retry nie dubluje items ani plików;
- wygasły download nie działa, ale audit pozostaje;
- permissions, direct URL i cross-tenant source są poprawnie blokowane;
- PL/EN są kompletne, a PT ma ten sam zestaw kluczy;
- ZIP otwiera się, nie ma niebezpiecznych ścieżek i przechodzi walidację manifestu.

## 15. Definition of Done

- paczka używa dokładnego assessment period z konta UC;
- income wynika z receipts, a expenses z payments;
- każdy przepływ jest uwzględniony albo jawnie wykluczony z powodem;
- każda raportowana pozycja ma dowód albo widoczny brak dowodu;
- VAT i simplified expenses wynikają z wersjonowanych reguł;
- totals uzgadniają się z details i źródłami bez dryfu pensa;
- snapshot i finalny ZIP są niezmienne, hashowane i tenant-safe;
- można zapisać ręczne zgłoszenie w Universal Credit;
- UI odróżnia raport UC od P&L, Self Assessment i VAT Return;
- golden dataset został ręcznie zaakceptowany przed produkcją.

## 16. Kolejność w roadmapie

UKFIN-513 można rozpocząć niezależnie od UKFIN-704, ale asset register report powinien być gotowy przed finalnym testem sprzedaży lub transferu aktywów:

1. UKFIN-704 - asset register report;
2. UKFIN-513A i UKFIN-513B;
3. UKFIN-513C i UKFIN-513D;
4. UKFIN-513E i UKFIN-513F;
5. UKFIN-513G i gate księgowego lub doradcy UC.

Szacowany nakład pełnego zakresu: 8-12 dni roboczych implementacji i testów, bez czasu oczekiwania na zewnętrzną weryfikację rzeczywistego assessment period.
