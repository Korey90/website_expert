# UKFIN-402R1 - zgodność importu CSV z rzeczywistym wyciągiem bankowym

**Data analizy:** 2026-11-20
**Status:** implementacja i walidacja zakończone, właściwy import oczekuje na decyzję użytkownika
**Priorytet:** wysoki
**Dane diagnostyczne:** prywatny plik lokalny, nie może trafić do Git, logów ani fixture testowego
**Anchor files:** BankStatementCsvParser, ImportBankStatementCsvAction, ViewBankAccount, BankStatementCsvImportTest

## 1. Cel

Naprawić import rzeczywistego wyciągu CSV tak, aby:

- automatycznie rozpoznawał potwierdzony polski format eksportu Revolut;
- obsługiwał datę z czasem bez utraty poprawnej daty księgowania;
- zapewniał bezpieczną deduplikację także wtedy, gdy bank nie przekazuje transaction ID;
- nie importował nieukończonych albo niejednoznacznych operacji;
- pokazywał użyteczny i bezpieczny powód błędu zamiast ogólnego komunikatu;
- zachował gwarancje dry run, tenant isolation, RBAC i braku raw CSV w bazie.

## 2. Odtworzenie problemu

1. Utworzono fikcyjny aktywny rachunek bankowy.
2. Wybrano rzeczywisty plik CSV wygenerowany przez bank.
3. Ustawiono format CSV, pozostawiając mapowanie kolumn automatyczne.
4. Pozostawiono aktywny Dry run.
5. System pokazał ogólny błąd przetwarzania CSV bez przyczyny.

## 3. Bezpieczne ustalenia o pliku

Analiza nie ujawniała opisów, kwot, numerów rachunków ani danych kontrahentów.

| Cecha | Wynik |
|---|---|
| Rozmiar | 809 bajtów |
| Wiersze danych | 7 |
| Kolumny | 10 |
| Separator | przecinek |
| Kodowanie | poprawne UTF-8, bez BOM |
| Waluta | GBP |
| Daty rozpoczęcia/zrealizowania | obecne w każdym wierszu |
| Kształt daty | YYYY-MM-DD HH:MM:SS |
| Kwoty zerowe | 0 wierszy |
| Niezerowe opłaty | 0 wierszy |
| Statusy | jeden status zakończony |
| Limity 5 MB / 5000 wierszy / 100 kolumn | nieprzekroczone |
| Spójność zmian salda | wszystkie 6 możliwych porównań zgodne z kwotą |

Nagłówki techniczne:

- Rodzaj
- Produkt
- Data rozpoczęcia
- Data zrealizowania
- Opis
- Kwota
- Opłata
- Waluta
- State
- Saldo

Prywatny plik nie jest fixture testowym i nie może zostać skopiowany do testów.

## 4. Potwierdzona przyczyna

### 4.1 Pierwszy błąd - automatyczne mapowanie

BankStatementCsvParser rozpoznaje angielskie aliasy Date, Booking Date, Transaction Date, Amount, Currency i Description. Nie rozpoznaje:

- Data zrealizowania jako booking_date;
- Kwota jako amount;
- Waluta jako currency;
- Opis jako reference.

Przy pustym mapowaniu parser kończy działanie przed analizą wierszy wyjątkiem: **A booking date column must be mapped.**

ViewBankAccount przechwytuje DomainException i zastępuje jego przyczynę ogólnym komunikatem. To dokładnie wyjaśnia zachowanie zgłoszone przez użytkownika.

### 4.2 Drugi błąd - timestamp

Po ręcznym przypisaniu właściwych kolumn parser odczytuje wszystkie 7 wierszy, ale każdy odrzuca.

Pole Data zrealizowania ma format YYYY-MM-DD HH:MM:SS, natomiast normalizeDate obsługuje wyłącznie daty bez czasu:

- DD/MM/YYYY;
- YYYY-MM-DD;
- DD-MM-YYYY;
- DD.MM.YYYY;
- MM/DD/YYYY.

| Próba | Total | Valid | Failed |
|---|---:|---:|---:|
| Automatyczny format daty | 7 | 0 | 7 |
| Wybrany YYYY-MM-DD | 7 | 0 | 7 |

### 4.3 Dowód minimalnej naprawy

Diagnostyczna kopia w pamięci, w której Data zrealizowania przypisano do booking_date, z timestampu wykorzystano część daty, a Kwota, Opis i Waluta przypisano do pól systemowych, dała:

| Total | Valid | Failed | Unikalne source hashes |
|---:|---:|---:|---:|
| 7 | 7 | 0 | 7 |

Plik, delimiter, kodowanie, limity, kwoty i waluta nie są przyczyną błędu.

## 5. Dodatkowe luki

### 5.1 Ogólny komunikat

Użytkownik nie wie, której kolumny nie rozpoznano, czy format daty pasuje ani czy problem dotyczy limitu, kodowania lub danych. Potrzebne są strukturalne, lokalizowane kody błędów z bezpiecznym kontekstem, bez pokazywania wartości transakcji.

### 5.2 Zero poprawnych wierszy

Po ręcznym mapowaniu parser może zwrócić 0 valid i 7 failed, a UI nadal nazwać to zakończonym dry run. Zero poprawnych wierszy musi dać wynik nieudany z podsumowaniem kategorii.

### 5.3 Brak audytu automatycznego mapowania

BankStatementImport zapisuje tylko mapowanie ręczne. Gdy parser wykrywa kolumny automatycznie, column_mapping pozostaje puste. Import właściwy powinien zapisywać resolved mapping i wykryty profil.

### 5.4 Ryzyko fałszywej deduplikacji

Eksport nie zawiera transaction ID. Obecny fallback source_hash używa daty bez czasu, kwoty, waluty, reference i counterparty. Dwie transakcje tego samego dnia, na tę samą kwotę i z tym samym opisem mogą zostać błędnie uznane za duplikat.

Pełny timestamp występuje w pliku, ale jest odrzucany. Profil Revolut musi tworzyć stabilną, nieodwracalną tożsamość źródłową z pełnych pól identyfikujących, bez utrwalania surowego wiersza.

### 5.5 State i Opłata są ignorowane

W analizowanym pliku wszystkie pozycje są zakończone, a wszystkie opłaty wynoszą zero. Nie wolno jednak po cichu importować pending/reverted ani ignorować niezerowej opłaty bez potwierdzonej semantyki.

### 5.6 Brak zgodności waluty rachunku

Parser sprawdza, czy waluta jest obsługiwana globalnie, ale akcja nie wymusza zgodności z BankAccount.currency. Dla analizowanego pliku waluta to GBP. Inna waluta powinna zostać zablokowana do czasu osobnej obsługi rachunków wielowalutowych.

### 5.7 Prywatny plik nie jest ignorowany przez Git

docs/private/statement-rev.csv jest untracked, ale docs/private/ nie jest objęty .gitignore. Istnieje ryzyko przypadkowego dodania prawdziwego wyciągu do repozytorium.

## 6. Decyzja projektowa

Naprawa będzie oparta na profilu formatu bankowego, a nie wyłącznie na luźnym dopisaniu aliasów.

Profil **revolut_pl** będzie wykrywany po charakterystycznym zestawie nagłówków i określi:

- booking_date = Data zrealizowania;
- amount = Kwota;
- currency = Waluta;
- reference = Opis;
- format daty-czasu = Y-m-d H:i:s;
- status column = State;
- dozwolony status = ZAKOŃCZONO;
- fee column = Opłata;
- balance column = Saldo;
- kolumny używane do bezpiecznej tożsamości źródłowej.

Data rozpoczęcia nie zostanie automatycznie nazwana value_date bez potwierdzenia semantyki. Może uczestniczyć w nieutrwalanym materiale wejściowym do hasha deduplikacji.

Ręczne mapowanie użytkownika zachowa pierwszeństwo przed profilem i aliasami.

## 7. Plan implementacji

### Etap 0 - ochrona danych

- [x] Dodać docs/private/ do .gitignore.
- [x] Potwierdzić przez git check-ignore, że prawdziwy CSV jest ignorowany.
- [x] Nie kopiować rzeczywistych wierszy do testów, raportów, logów ani dokumentacji.
- [x] Zachować usuwanie pliku tymczasowego po próbie.

### Etap 1 - profil i mapowanie

- [x] Dodać mały resolver profilu CSV lub równoważną testowalną strukturę Finance.
- [x] Rozpoznawać profil revolut_pl po nagłówkach.
- [x] Zwracać resolved mapping niezależnie od źródła mapowania.
- [x] Zachować zgodność z dotychczasowymi formatami.
- [x] Nie mapować niepewnych semantycznie kolumn na podstawie podobnej nazwy.

### Etap 2 - daty i timestamp

- [x] Rozszerzyć auto-detection o Y-m-d H:i:s.
- [x] Dodać format jawny do UI albo wyraźnie pokazać wykryty profil.
- [x] Do booking_date zapisywać datę kalendarzową z Data zrealizowania.
- [x] Pełny timestamp zachować tylko w pamięci na potrzeby source identity.
- [x] Pustą datę zrealizowania traktować jako błąd wiersza.

### Etap 3 - tożsamość i deduplikacja

- [x] Dla profilu bez transaction ID wyliczać stabilny identyfikator z completed timestamp, started timestamp, typu, kwoty, opłaty, waluty i opisu.
- [x] Nie zapisywać surowego wiersza ani odwracalnych danych pomocniczych.
- [x] Zachować provider ID banku jako preferowane źródło, jeżeli przyszły format je udostępni.
- [x] Potwierdzić, że podobne transakcje z różnym timestampem nie są fałszywym duplikatem.
- [x] Potwierdzić, że ponowny import tego samego wyciągu wykrywa wszystkie duplikaty na danych testowych.

### Etap 4 - status, opłata i waluta

- [x] Importować automatycznie tylko status jednoznacznie zakończony.
- [x] Pending, reverted, failed i nieznane statusy raportować, a nie księgować.
- [x] Dla niezerowej opłaty nie zgadywać ujęcia. Do czasu potwierdzenia semantyki oznaczyć wiersz jako wymagający obsługi.
- [x] Sprawdzić zgodność waluty każdego wiersza z BankAccount.currency.
- [x] Zablokować różną walutę z bezpiecznym komunikatem.
- [ ] Opcjonalnie wykorzystać Saldo jako kontrolę ostrzegawczą, bez księgowania z samego salda.

### Etap 5 - diagnostyka i UX

- [x] Wprowadzić strukturalne kody błędów lub bezpieczną warstwę mapowania wyjątków.
- [x] Dodać lokalizowane komunikaty PL/EN/PT dla nierozpoznanych kolumn, timestampu, 0 valid, waluty, statusu i opłaty.
- [x] W dry run pokazać wykryty profil, resolved mapping, total/valid/failed/duplicates i kategorie błędów.
- [x] Nie pokazywać kwot, opisów, pełnych dat-czasów ani innych danych transakcji.
- [x] Import z 0 valid oznaczać jako failure, nie completed.
- [x] Przy imporcie właściwym zapisywać resolved mapping i profil.

### Etap 6 - testy regresyjne

- [x] Utworzyć tylko fikcyjny fixture z tą samą strukturą.
- [x] Test auto-detection revolut_pl.
- [x] Test 10 nagłówków i timestampu Y-m-d H:i:s.
- [x] Test pierwszeństwa ręcznego mapowania.
- [x] Test statusu zakończonego i odrzucenia nieukończonego.
- [x] Test zachowania dla niezerowej opłaty.
- [x] Test waluty zgodnej i niezgodnej z rachunkiem.
- [x] Test podobnych transakcji z różnymi timestampami.
- [x] Test ponownego importu tego samego pliku.
- [x] Test dry run bez zapisów.
- [x] Test zapisu resolved mapping.
- [x] Test braku raw payload w bazie, logach i notyfikacjach.
- [x] Zachować testy limitów, RBAC, membership i tenant isolation.

### Etap 7 - weryfikacja na prywatnym pliku

- [x] Uruchomić parser i dry run lokalnie na docs/private/statement-rev.csv.
- [x] Oczekiwany wynik: total 7, valid 7, failed 0.
- [x] Potwierdzić imported 0 i brak zmian bazy podczas dry run.
- [ ] Po zatwierdzeniu przez użytkownika wykonać właściwy import z wyłączonym dry run.
- [ ] Potwierdzić, że ponowny dry run wykazuje 7 duplikatów.
- [x] Nie ujawniać treści transakcji w raporcie.

### Etap 8 - walidacja jakości

- [x] Uruchomić BankStatementCsvImportTest.
- [x] Uruchomić powiązane testy bank reconciliation.
- [x] Uruchomić Finance regression proporcjonalnie do zmian.
- [x] Uruchomić Pint.
- [x] Sprawdzić kompletność PL/EN/PT.
- [x] Uruchomić git diff --check.
- [x] Potwierdzić git check-ignore dla prywatnego CSV.

## 8. Kryteria akceptacji

1. Prywatny plik przechodzi dry run z 7/7 poprawnych wierszy i 0 zapisów.
2. Potwierdzony polski eksport Revolut nie wymaga ręcznego mapowania.
3. Timestamp daje prawidłowy booking_date.
4. Ponowny import jest deduplikowany, a odrębne transakcje z różnym timestampem nie znikają.
5. Nieukończony status, niezerowa nieobsłużona opłata i niezgodna waluta nie są księgowane po cichu.
6. UI wskazuje bezpieczną przyczynę i dalsze działanie.
7. Automatyczne mapowanie jest audytowane.
8. Raw CSV ani dane transakcji nie trafiają do repozytorium, bazy, logów lub notyfikacji.
9. Dotychczasowe formaty i testy nie mają regresji.

## 9. Poza zakresem

- automatyczne księgowanie opłat jako osobnych journal entries;
- rachunki wielowalutowe i przewalutowania;
- Open Banking API;
- przechowywanie wyciągu jako FinancialAttachment;
- interpretacja Data rozpoczęcia jako value date bez potwierdzenia;
- zmiana zasad bank reconciliation po utworzeniu BankTransaction.

## 10. Kolejność realizacji

1. Ochrona docs/private/.
2. Failing tests dla formatu, timestampu, statusu, waluty i deduplikacji.
3. Profil i parser.
4. Akcja importu i audit resolved mapping.
5. Komunikaty UI oraz tłumaczenia.
6. Testy regresyjne.
7. Lokalny dry run na prywatnym pliku.
8. Decyzja użytkownika o właściwym imporcie.

## 11. Baseline

- BankStatementCsvImportTest: 8 passed, 51 assertions.
- Prywatny plik z automatycznym mapowaniem: globalny błąd booking date.
- Prywatny plik z ręcznym mapowaniem: 0 valid, 7 failed przez timestamp.
- Diagnostycznie z poprawnym mapowaniem i datą: 7 valid, 0 failed.

## 12. Wynik implementacji

- Prywatny plik: 7 total, 7 valid, 0 failed, 0 zapisów podczas pełnego dry run.
- Profil: automatycznie wykryty `revolut_pl`, bez ręcznego mapowania kolumn.
- Deduplikacja: pełny timestamp uczestniczy w nieodwracalnej tożsamości źródłowej; ponowienie na fikcyjnym wyciągu wykrywa wszystkie duplikaty.
- Bezpieczeństwo: `docs/private/` jest ignorowany przez Git, a surowe wartości transakcji nie trafiają do komunikatów ani metadanych importu.
- Test importu CSV: 12 testów, 85 asercji.
- Powiązane testy bankowe: 52 testy, 359 asercji.
- Pełny moduł Finance: 357 testów zaliczonych, 2512 asercji, 1 test pominięty z powodu braku blokad wierszy w SQLite.
- Tłumaczenia: EN, PL i PT mają po 1277 zgodnych kluczy.
- Właściwy import prywatnego wyciągu nie został wykonany. Pozostaje świadomą decyzją użytkownika po weryfikacji wyniku dry run w interfejsie.

