# UKFIN-513 - paczka dowodowa Universal Credit dla sole tradera

**Status:** Ukończone lokalnie - UKFIN-513A do UKFIN-513G, gate specjalisty i porównanie z aktualnym zadaniem UC zaakceptowane
**Data:** 2026-11-24
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

### UKFIN-513B - źródła i klasyfikacja - ukończone lokalnie 2026-11-23

1. Zbudować source collector dla payments, refunds, expense payments, bank transactions, owner-funded i asset flows.
2. Dodać klasyfikację UC, review i idempotency.
3. Dodać manual entries dla gotówki, barteru, tips, grants i tax/NI refunds.
4. Dodać business-use percentage i reason.
5. Zachowywać committed bank statement jako bezpieczny attachment.

Wynik etapu:

- migracja `000041` dodaje tenantowe, audytowalne `universal_credit_report_items` i `universal_credit_manual_entries`;
- kolektor używa daty faktycznego przepływu i pobiera częściowe customer receipts, customer refunds, expense payments, owner-funded i cash payments, supplier refunds oraz nierozliczone części bank transactions;
- powiązane acquisition, improvement i disposal są rozpoznawane przez płatność faktury lub kosztu, bez tworzenia drugiego przepływu na podstawie daty księgowej aktywa;
- każda pozycja ma stabilny identity hash i source fingerprint, a ponowne zebranie jest idempotentne oraz resetuje review po zmianie źródła;
- klasyfikacja waliduje kategorie snapshotu polityki, included/excluded, business-use percentage, uzasadnienie podziału i przyczynę wykluczenia;
- ręczne cash, barter, tips, grants, tax/NI refunds, asset transfers i tax/NI/pension/VAT payments wymagają dowodu albo jawnej przyczyny jego braku i mogą zostać wyłącznie audytowalnie unieważnione;
- committed bank statement CSV jest zachowywany jako prywatny `FinancialAttachment` typu `bank_statement`, z SHA-256 i skanem bezpieczeństwa; dry-run nadal nie zapisuje pliku;
- dodano dedykowane policies, relacje i audyt tenant/source identity dla nowych rekordów;
- migracja przeszła na lokalnym MySQL, tenant audit wykazał 0 błędów;
- Finance: 424 passed, 1 skipped, 3060 assertions;
- tłumaczenia finance: PL/EN/PT po 1491 identycznych kluczy.

### UKFIN-513C - kalkulatory - ukończone lokalnie 2026-11-23

1. Zaimplementować VAT inclusive/exclusive.
2. Zaimplementować mileage, home working i living at business premises.
3. Dodać loan-interest limit i pozostałe limity polityki.
4. Zablokować double claim actual/simplified.
5. Użyć arytmetyki w pensach, FX snapshot i kontrolowanych zaokrągleń.

Wynik etapu:

- migracja `000042` dodaje jawne snapshoty VAT i FX do pozycji raportu oraz tenantowe `universal_credit_simplified_expenses`;
- kalkulacja okresu obsługuje metodę VAT inclusive oraz VAT exclusive, przy czym dla zakupów odejmuje wyłącznie VAT odzyskiwalny, a VAT nieodzyskiwalny pozostaje częścią kosztu;
- częściowe płatności dzielą VAT dokumentu proporcjonalnie i zaokrąglają wynik do pełnych pensów;
- ręczny snapshot VAT i GBP wymaga źródła lub uzasadnienia, aktora i daty, a każda zmiana unieważnia poprzednie totals oraz hash;
- snapshot FX zapisuje kurs do GBP, źródło kursu, datę i powód korekty, a przeliczenie działa na liczbach całkowitych bez arytmetyki float;
- uproszczone mileage liczy 45p do 833 mil i 25p powyżej dla samochodu lub vana oraz 24p dla motorbike lub scooter;
- home working stosuje progi 25-50, 51-100 i ponad 100 godzin, a living at business premises odejmuje właściwy policy deduction według liczby mieszkańców;
- business loan interest jest ograniczony do 4100 pensów na assessment period i rozdzielany deterministycznie między pozycje;
- centralny double-claim guard blokuje jednoczesne użycie kosztu rzeczywistego i kolidującego simplified expense;
- kalkulacja zapisuje income, expenses, net oraz kanoniczny SHA-256 snapshot źródeł, klasyfikacji, VAT, FX i simplified expenses;
- uproszczone koszty są niezmienne, mogą być tylko audytowalnie unieważnione, wymagają dowodu albo przyczyny jego braku i mają osobną tenant policy;
- tenant audit sprawdza właściciela, snapshot FX oraz zgodność wersji polityki uproszczonych kosztów;
- migracja przeszła na lokalnym MySQL, tenant audit wykazał 0 błędów;
- Finance: 431 passed, 1 skipped, 3092 assertions;
- tłumaczenia finance: PL/EN/PT po 1510 identycznych kluczy.

### UKFIN-513D - review i raporty - ukończone lokalnie 2026-11-23

1. Dodać completeness preflight.
2. Dodać podgląd kategorii, wykluczeń, dowodów i uzgodnień.
3. Dodać review/lock/supersede z kanonicznym SHA-256 snapshotu.
4. Zbudować wspólny model summary/detail/reconciliation.
5. Generować identyczne wartości w PDF, XLSX i CSV.

Wynik etapu:

- migracja `000043` dodaje wersjonowany snapshot review, jego SHA-256, liczbę potwierdzonych ostrzeżeń i wersję oświadczenia review;
- completeness preflight działa bez zapisu i sprawdza integralność polityki, finalną klasyfikację, aktualność fingerprintów źródeł, kompletność VAT i FX, wynik kalkulacji, double claim, zgodność sum oraz stan dowodów i uzgodnień bankowych;
- wynik preflight ma stabilne statusy `green`, `amber` i `red`; błędy blokują review, a ostrzeżenia wymagają jawnego potwierdzenia;
- pusty, prawidłowo przeliczony okres przechodzi preflight jako `green` i może zostać poddany review oraz zablokowany;
- kontrolowane akcje `review`, `lock` i `supersede` sprawdzają aktywne członkostwo, dedykowane uprawnienie, poprawny stan oraz wymagane potwierdzenia lub uzasadnienia;
- review zapisuje kanoniczny snapshot obejmujący raport i wynik preflight, a lock buduje go ponownie i porównuje SHA-256; zmiana danych, dowodu albo uzgodnienia po review blokuje lock;
- model okresu chroni wartości kalkulacji, snapshot i metadane workflow przed bezpośrednią zmianą poza właściwym przejściem;
- wspólny model danych zawiera summary, szczegóły pozycji uwzględnionych i wykluczonych, simplified expenses, stan dowodów oraz reconciliation;
- osobista transakcja bankowa pozostaje widocznym wykluczeniem z powodem, a brak zachowanego wyciągu jest oznaczony jako `source_not_retained`;
- wszystkie trzy formaty PDF, XLSX i CSV powstają z tego samego modelu i tych samych wartości; eksport CSV ma ochronę tekstowych komórek przed formułami;
- tenant audit weryfikuje obecność, wersję i hash snapshotu review oraz wymagane metadane workflow;
- migracja przeszła na lokalnym MySQL, tenant audit wykazał 0 błędów;
- Finance: 438 passed, 1 skipped, 3129 assertions;
- tłumaczenia finance: PL/EN/PT po 1621 identycznych kluczy.

### UKFIN-513E - generator ZIP - ukończone lokalnie 2026-11-23

1. Dodać inventory builder dokumentów.
2. Dodać queue job, statusy i kontrolowane retry.
3. Kopiować tylko czyste, ponownie zweryfikowane pliki.
4. Dodać included/excluded manifest i hash każdego pliku.
5. Dodać prywatny download, expiry i scheduled cleanup.

Wynik etapu:

- migracja `000044` dodaje tenantowe `universal_credit_evidence_packs` oraz niezmienne pozycje manifestu z wersją, statusem, hashem snapshotu, hashem i rozmiarem ZIP, licznikami, próbami, czasem wygaśnięcia i sanitizowanym kodem błędu;
- inventory builder działa wyłącznie na immutable review snapshot i ponownie sprawdza tenant, rekord załącznika oraz SHA-256 zapisany podczas review;
- snapshot dowodów standardowych płatności obejmuje załączniki płatności oraz powiązanej faktury, expense lub purchase credit note; czyste dokumenty są dołączane, a `pending`, `rejected` i `failed` pozostają w manifeście jako wykluczone;
- job kolejki jest unikalny per pack, ma kontrolowane próby i backoff, zapisuje statusy `pending`, `processing`, `completed`, `failed` oraz pozwala na jawny retry bez dublowania pozycji manifestu;
- generator tworzy README, manifest JSON, podsumowanie PDF/XLSX/CSV, review PDF, policy PDF, szczegóły income/expense/exclusions, uzgodnienie XLSX, warnings CSV i metadane audytowe;
- każdy dołączony dokument jest kopiowany strumieniowo, a jego rozmiar i SHA-256 są ponownie sprawdzane przed utworzeniem ZIP; niespójność blokuje cały build i usuwa częściowy plik;
- wszystkie logiczne ścieżki przechodzą ochronę przed zip-slip, tekst CSV przechodzi ochronę przed formula injection, a manifest nie zawiera fizycznych ścieżek storage;
- gotowy ZIP trafia pod losową nazwę na prywatny dysk `financial`, jest ponownie odczytywany i hashowany, a download przed wysłaniem ponownie kontroluje pełny rozmiar i SHA-256;
- download wymaga `export_universal_credit_pack`, aktywnego tenanta, statusu completed i niewygasłego terminu oraz zwraca `Cache-Control: private, no-store` i kontrolne hashe w nagłówkach;
- codzienny cleanup o 02:30 usuwa tylko wygasły ZIP, zmienia status na `expired` i zachowuje rekord paczki, manifest, hashe oraz metadane audytowe;
- tenant audit kontroluje właściciela okresu i paczki, zgodność review snapshot hash, kompletność metadanych gotowej paczki, liczniki manifestu oraz wymagane hashe i powody wykluczenia;
- migracja przeszła na lokalnym MySQL, trasa downloadu i harmonogram są zarejestrowane, tenant audit wykazał 0 błędów;
- Finance: 443 passed, 1 skipped, 3247 assertions;
- tłumaczenia finance: PL/EN/PT po 1663 identyczne klucze.

### UKFIN-513F - panel i języki

1. Dodać `Finance > Universal Credit`.
2. Dodać kreator okresu, klasyfikację i simplified expenses.
3. Dodać wskaźniki green/amber/red.
4. Dodać review, lock, generate, download, mark as reported i supersede.
5. Dodać PL/EN oraz parytet kluczy PT.

Stan na 2026-11-23: UKFIN-513F ukończony lokalnie.

- panel `Finance > Universal Credit` udostępnia tenantową listę i szczegóły okresów z osobnymi uprawnieniami view, manage oraz export;
- owner lub admin może włączyć profil UC, zapisać metodę VAT i pomocniczy dzień początku, a użytkownik z uprawnieniem manage tworzy okres na podstawie dokładnych dat z konta UC;
- draft obsługuje idempotentne pobranie cash sources, klasyfikację included/excluded, business-use, ręczne zdarzenia kasowe z dowodem lub powodem jego braku oraz trzy rodzaje simplified expenses;
- ekran okresu pokazuje wartości income, expenses i net, status green/amber/red, błędy i ostrzeżenia preflightu, snapshot hashes oraz pełne metadane review, lock, report i supersede;
- kontrolowane akcje realizują calculate, review z jawnym potwierdzeniem ostrzeżeń, lock z powodem, generate pack, retry, prywatny download ZIP, supersede oraz eksport podsumowania do CSV, XLSX i PDF;
- po ręcznym zgłoszeniu do Universal Credit można zmienić wyłącznie locked na reported, zachować referencję i opcjonalny prywatny plik potwierdzenia; metadane zgłoszenia są niezmienne, a system jawnie informuje, że nie wysyła danych do UC;
- interfejs i komunikaty mają kompletne tłumaczenia EN, PL i PT: po 1823 identyczne klucze finance;
- nowe trasy eksportu i ZIP wymagają `export_universal_credit_pack`, panel respektuje dedykowane RBAC, aktywne członkostwo oraz tenant scope;
- Finance: 446 passed, 1 skipped, 3265 assertions; tenant audit: 0 błędów; Pint i kontrola składni: poprawne.

### UKFIN-513G - testy i gate - ukończone lokalnie 2026-11-24

1. Przygotować golden dataset zaakceptowany przez księgowego lub doradcę znającego UC w Irlandii Północnej.
2. Dodać instrukcję użytkownika i checklistę dokumentów.
3. Uruchomić security, tenant, queue, report i package tests.
4. Wykonać dry run na izolowanym tenantcie demonstracyjnym.
5. Dopuścić produkcję po porównaniu jednego okresu z formularzem UC.

Stan na 2026-11-24: część techniczna UKFIN-513G jest ukończona lokalnie, przegląd specjalisty został zaakceptowany w wersji papierowej, a porównanie z aktualnym zadaniem lub formularzem UC zakończyło się bez problemów. Gate walidacyjny jest zamknięty.

- dodano instrukcję użytkownika i checklistę dowodów w wersji EN oraz PL w `docs/guides`;
- dodano wersjonowany kandydat golden dataset `uk-universal-credit-golden-v1` z niezmiennym SHA-256, statusem `pending_specialist_review` i oczekiwanymi sumami GBP 1 290,00 income, GBP 199,00 expenses oraz GBP 1 091,00 net;
- izolowany test E2E tworzy osobny tenant, klasyfikuje included/excluded cash items, dodaje business-use, limit loan interest, mileage i home working, wykonuje calculate, amber preflight, review, lock, generuje zweryfikowany ZIP, zapisuje ręczne reported i uruchamia tenant audit;
- dry run przeszedł: 1 test, 38 asercji, wynik i komenda powtórzenia są w `docs/review/uk-universal-credit-golden-dataset-v1/dry-run-result.md`;
- kontrolowany tryb testu eksportuje dokładny ZIP z właściwego generatora do `docs/demo/uk-universal-credit-golden-v1`; standardowe testy używają fake private storage i nie nadpisują artefaktu;
- paczka dla specjalisty ma osobny SHA-256 i rozmiar zapisane w `artifact.sha256.json`, a instrukcja regeneracji wymaga jawnej flagi `GENERATE_UC_GOLDEN_PACK=1`;
- hash snapshotu review ma osobną etykietę EN/PL/PT i nie jest błędnie przedstawiany jako hash kalkulacji;
- checklisty specjalisty EN i PL są związane z dokładnym hashem fixture, więc zmiana danych wymaga nowej wersji i ponownej akceptacji;
- 24 listopada 2026 właściciel projektu potwierdził papierową akceptację dokładnego fixture i ZIP; referencja `UC-GOLDEN-V1-PAPER-20261124`, a dane reviewera i oryginał pozostają na dokumencie papierowym poza repozytorium;
- niezmienny fixture zachowuje historyczne pole `pending_specialist_review`, ponieważ jego zmiana unieważniłaby zaakceptowany SHA-256; autorytatywna decyzja znajduje się w osobnym `specialist-sign-off.json`;
- 24 listopada 2026 właściciel projektu potwierdził, że księgowy porównał te same przypadki z aktualnym zadaniem lub formularzem na koncie UC i nie stwierdził problemu;
- gate walidacyjny został zaakceptowany dla dokładnego fixture i ZIP; każda późniejsza zmiana danych, oczekiwanych wyników, polityki lub oficjalnego workflow UC wymaga nowej wersji i ponownej akceptacji.
- pełna regresja Finance po dodaniu dry runu i artefaktu: 447 passed, 1 skipped, 3305 assertions; Pint: poprawny.

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
