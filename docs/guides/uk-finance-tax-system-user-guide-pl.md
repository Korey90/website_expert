# Instrukcja obsługi systemu finansów, księgowości i podatków UK

**System:** Website Expert NI

**Wersja instrukcji:** 1.0

**Stan funkcjonalny opisany na:** 22 listopada 2026

**Język panelu użyty w przykładach:** angielski

## 1. Cel instrukcji

Ten dokument opisuje codzienną i okresową obsługę modułu finansowego Website Expert NI:

- konfigurację profilu księgowego firmy;
- klientów, dostawców i plan kont;
- faktury sprzedaży, płatności i noty kredytowe;
- koszty, faktury zakupowe, płatności i korekty dostawców;
- koszty cykliczne;
- załączniki i skanowanie bezpieczeństwa;
- rachunki bankowe, import CSV i uzgodnienie banku;
- okresy księgowe, dzienniki i księgę główną;
- raporty finansowe;
- szczegóły VAT, korekty VAT i dziewięć pól VAT Return;
- połączenie z HMRC MTD VAT;
- przegląd, blokadę, wysyłkę i potwierdzenie VAT Return;
- procedury kontrolne i rozwiązywanie typowych problemów.

Instrukcja opisuje działanie aplikacji. Nie zastępuje oceny zawodowej brytyjskiego księgowego ani porady podatkowej. Klasyfikację transakcji, prawo do odzyskania VAT, tax point, zastosowanie reverse charge oraz sposób korekty błędów powinien zatwierdzić księgowy znający sytuację firmy.

## 2. Zakres i ważne ograniczenia

### 2.1 Funkcje dostępne

System obsługuje:

- ewidencję sprzedaży i zakupów;
- księgowanie dokumentów do podwójnego ledgeru w GBP;
- VAT standardowy 20%, obniżony 5%, zero rate, exempt, outside scope i reverse charge;
- dokumenty w obsługiwanych walutach ze snapshotem kursu do GBP;
- płatności klientów i dostawców;
- sprzedażowe i zakupowe noty kredytowe;
- rejestr środków trwałych, kategorie oraz kontrolowane nabycie, ulepszenie, sprzedaż i odwrócenie;
- import wyciągów CSV i uzgodnienie banku;
- raporty zarządcze i księgowe;
- lokalne obliczenie VAT Return;
- HMRC MTD VAT w sandboxie: OAuth, obligations, view return, liabilities, payments i submission;
- paczkę dla księgowego oraz PDF potwierdzenia HMRC.

### 2.2 Funkcje niedostępne lub nieukończone

System nie obsługuje obecnie:

- payroll, obliczania wynagrodzeń, FPS ani EPS;
- deklaracji PAYE;
- Corporation Tax i CT600;
- Self Assessment ani MTD Income Tax;
- CIS;
- harmonogramów i automatycznego księgowania amortyzacji;
- obliczania UK capital allowances;
- automatycznych bank feeds przez Open Banking;
- automatycznego rozliczania prepayments i accruals;
- obsługi biura rachunkowego przez Agent Authorisation API;
- produkcyjnej wysyłki HMRC przed przejściem odrębnego production readiness gate.

Pola UTR, PAYE reference i Accounts Office Reference w profilu służą tylko do bezpiecznego przechowywania danych. Nie uruchamiają deklaracji dla tych podatków.

### 2.3 Obsługiwane schematy VAT Return

Profil pozwala zapisać schemat `standard`, `cash`, `annual` albo `flat_rate`, ale obecny kalkulator VAT Return obsługuje tylko:

- `standard`;
- `annual`.

Wybranie `cash` albo `flat_rate` zablokuje utworzenie VAT Return do czasu wdrożenia odpowiedniego silnika. Nie zmieniaj schematu wyłącznie po to, aby ominąć blokadę.

## 3. Najważniejsza zasada działania

Wprowadzenie rekordu nie zawsze oznacza jego zaksięgowanie.

```text
Dokument roboczy
  -> zatwierdzenie
  -> posting do ledgeru
  -> raporty finansowe i VAT
  -> płatność
  -> uzgodnienie bankowe
```

- `Draft` jest dokumentem roboczym i zwykle nie wpływa na raporty.
- `Approved` potwierdza kontrolę kosztu, ale nie zawsze oznacza wpis w ledgerze.
- `Posted` oznacza, że powstał zbilansowany journal.
- Raport `Profit and Loss`, `Trial Balance`, `Balance Sheet`, `General Ledger` i `VAT Detail` korzysta z ledgeru.
- `Expense Report` uwzględnia koszty od statusu `Approved`.
- Płatność i uzgodnienie bankowe są odrębnymi etapami. Sam wyciąg bankowy nie zastępuje faktury.

## 4. Dostęp, tenant i uprawnienia

Przed pracą upewnij się, że w panelu wybrana jest właściwa firma. Wszystkie dane finansowe są odseparowane przez `business_id`.

| Uprawnienie | Zakres |
|---|---|
| `access_admin_panel` | Wejście do panelu administracyjnego |
| `view_finance` | Odczyt finansów, dokumentów i raportów |
| `manage_finance` | Tworzenie i edycja roboczych danych, import banku, uzgodnienia |
| `approve_finance` | Zatwierdzanie kosztów, korekt VAT i VAT Return |
| `post_finance` | Posting dokumentów i płatności do ledgeru |
| `close_finance_periods` | Zamykanie i blokowanie okresów oraz VAT Return |
| `submit_tax_returns` | Wysyłka do HMRC, reconciliation i pobranie receipt |

Rekomendowany podział obowiązków:

- operator wprowadza dokumenty i załączniki;
- bookkeeper sprawdza klasyfikację, VAT i zatwierdza;
- osoba z prawem `post_finance` księguje;
- właściciel lub upoważniona osoba blokuje i wysyła VAT Return.

Nie nadawaj `submit_tax_returns` osobie, która ma jedynie wprowadzać dokumenty.

## 5. Konfiguracja początkowa

### 5.1 Profil księgowy

W panelu `/admin` przejdź do `Finance > Accounting profile`. Bezpośrednia ścieżka to `/admin/accounting-profile`. Starsza ścieżka `/business/accounting` pozostaje dostępna dla zgodności.

Uzupełnij:

1. rodzaj podmiotu: limited company, sole trader lub partnership;
2. pełną nazwę prawną;
3. Companies House number dla limited company;
4. adres rejestrowy;
5. początek i koniec roku finansowego;
6. accounting basis;
7. status rejestracji VAT;
8. numer VAT;
9. schemat VAT;
10. datę rozpoczęcia i ewentualnego zakończenia rejestracji VAT.

Walutą funkcjonalną modułu księgowego jest obecnie GBP.

Pierwsze otwarcie profilu inicjalizuje dla bieżącej firmy:

- profil księgowy;
- domyślne UK tax codes;
- domyślny plan kont UK.

Dane UTR, PAYE Reference i Accounts Office Reference są szyfrowane. Pozostawienie pola pustego przy kolejnej edycji nie usuwa zapisanej wartości.

Użytkownik z `view_finance` może wyświetlić profil. Zapis wymaga `manage_finance` oraz aktywnej roli owner albo admin w bieżącej firmie.

### 5.2 Tax codes

Domyślny zestaw:

| Kod | Znaczenie | Typowa stawka |
|---|---|---:|
| `S20` | Standard rate | 20% |
| `R5` | Reduced rate | 5% |
| `Z0` | Zero rate | 0% |
| `EXEMPT` | VAT exempt | 0% |
| `OUTSIDE` | Outside scope | 0% |
| `RC20` | Reverse charge | 20% |

Kod VAT wybiera się dla każdej pozycji dokumentu. Nie wybieraj kodu tylko na podstawie wartości procentowej. `Z0`, `EXEMPT` i `OUTSIDE` mają różne znaczenie i inne mapowanie do VAT Return.

### 5.3 Plan kont

W `Finance > Ledger accounts` sprawdź domyślny plan kont. Najczęściej używane konta kosztowe:

- `6100` Rent and rates;
- `6200` Software and subscriptions;
- `6300` Office costs;
- `6400` Travel and subsistence;
- `6500` Professional fees;
- `6600` Advertising and marketing;
- `6700` Utilities and communications;
- `6800` Insurance;
- `6900` Bank charges;
- `7900` Other expenses.

Nie zmieniaj znaczenia kont systemowych po rozpoczęciu księgowania. Nowe konto powinno mieć właściwy type, subtype, normal balance i oznaczenie, czy można na nim księgować.

### 5.4 Okresy księgowe

W `Finance > Accounting periods` utwórz okresy bez nakładania się dat.

Statusy:

| Status | Znaczenie |
|---|---|
| `Open` | Można księgować |
| `Closing` | Trwa kontrola zamknięcia, księgowanie nadal jest możliwe |
| `Closed` | Nowe księgowania są zablokowane, okres można ponownie otworzyć z uzasadnieniem |
| `Locked` | Blokada końcowa, bez możliwości ponownego otwarcia z panelu |

Nie używaj `Locked`, dopóki raporty nie zostały sprawdzone i zaakceptowane.

### 5.5 Kategorie i rejestr środków trwałych

W `Finance > Asset categories` albo `Finance > Kategorie środków trwałych` utwórz kategorie używane przez firmę, na przykład sprzęt komputerowy. Każda kategoria wymaga trzech aktywnych kont bieżącego tenanta:

- konta środka trwałego typu `asset/fixed_asset`;
- konta umorzenia typu `asset/contra_asset`;
- konta kosztu amortyzacji typu `expense/depreciation`.

Ustaw domyślną metodę amortyzacji księgowej, okres użytkowania w miesiącach i procent wartości rezydualnej. Są to ustawienia książkowe, nie reguły UK capital allowances.

W `Finance > Fixed assets` albo `Finance > Środki trwałe` możesz utworzyć roboczy rekord aktywa. System nada numer `FA-YYYY-NNN` i skopiuje ustawienia z kategorii. Dopóki rekord ma status `Draft` lub `Wersja robocza`:

- można go edytować i usunąć;
- nie powstaje journal entry;
- rekord nie wpływa na P&L, Balance Sheet, VAT ani inne raporty;
- nie oznacza jeszcze aktywacji ani podatkowego rozpoznania środka trwałego.

#### Nabycie i aktywacja

Przed aktywacją środka trwałego:

1. wprowadź zakup jako zwykły koszt z osobną pozycją dla danego aktywa;
2. dodaj fakturę albo rachunek jako załącznik i poczekaj na status skanu `Clean`;
3. zatwierdź i zaksięguj koszt;
4. otwórz roboczy środek trwały i wybierz `Nabądź i aktywuj` albo `Acquire and activate`;
5. wybierz całą pozycję kosztu, ustaw datę oddania do użytkowania i sprawdź podgląd journalu.

System przenosi koszt z konta pozycji Expense na konto aktywa z kategorii:

```text
Wn konto środka trwałego
Ma pierwotne konto kosztowe
```

VAT, zobowiązanie wobec dostawcy, płatność i uzgodnienie bankowe pozostają w dokumencie Expense. System nie tworzy drugiego zakupu. Wartość aktywa obejmuje kwotę netto GBP i nieodliczalny VAT. Odliczalny VAT oraz neutralny reverse charge nie zwiększają kosztu aktywa.

Jedna pozycja Expense może zostać użyta tylko raz i zawsze w całości. Jeżeli jedna faktura obejmuje kilka aktywów, przygotuj osobne pozycje przed zaksięgowaniem kosztu.

#### Ulepszenie

Dla aktywnego środka trwałego użyj `Dodaj ulepszenie` albo `Add improvement`. Źródłem musi być inna, zaksięgowana pozycja Expense z czystym dokumentem. System wykonuje tę samą reklasyfikację i zwiększa koszt historyczny aktywa bez ponownego ujęcia VAT lub zobowiązania.

#### Sprzedaż i zbycie

Pierwsza wersja obsługuje zbycie wyłącznie przez sprzedaż udokumentowaną fakturą:

1. utwórz fakturę sprzedaży z osobną pozycją dotyczącą środka trwałego;
2. wystaw fakturę, aby powstał journal należności, sprzedaży i VAT;
3. na aktywnym środku trwałym wybierz `Zaksięguj sprzedaż i zbycie` albo `Record sale and disposal`;
4. wybierz całą pozycję faktury i ustaw datę równą tax point faktury;
5. sprawdź podgląd i zatwierdź.

Faktura zachowuje VAT i saldo klienta. Journal zbycia usuwa zwykły przychód ze sprzedaży, wyksięgowuje koszt historyczny i umorzenie oraz pokazuje zysk albo stratę na właściwych kontach.

#### Korekta

Zaksięgowanych operacji aktywa nie można edytować ani usuwać. Użyj `Odwróć ostatnią operację`, podaj datę i uzasadnienie. Operacje odwraca się od najnowszej do najstarszej. System zachowuje oryginalny journal i tworzy dokładny zapis lustrzany.

Purchase credit note dla wykorzystanej pozycji kosztu oraz sales credit note dla pozycji użytej przy zbyciu są blokowane. Najpierw odwróć zależną operację środka trwałego, a dopiero potem wystaw notę.

Ten workflow nie oblicza UK capital allowances, nie księguje jeszcze amortyzacji książkowej i nie obsługuje darowizny, prywatnego wycofania, częściowego zbycia ani zamiany aktywa. Te przypadki wymagają osobnej decyzji księgowej.

## 6. Sprzedaż i VAT należny

### 6.1 Przygotowanie klienta

Przed wystawieniem faktury sprawdź:

- poprawną nazwę prawną klienta;
- adres;
- adres e-mail odbiorcy faktury;
- numer VAT, jeżeli ma zastosowanie;
- walutę i warunki płatności.

### 6.2 Tworzenie faktury

Przejdź do `Finance > Invoices` i utwórz fakturę.

Uzupełnij co najmniej:

1. klienta;
2. issue date;
3. tax point;
4. due date;
5. walutę;
6. opis każdej pozycji;
7. ilość i cenę jednostkową;
8. tax code każdej pozycji;
9. kurs GBP, źródło kursu i datę kursu dla dokumentu walutowego;
10. warunki i notatki, jeśli są potrzebne.

Sprawdź subtotal, VAT i total przed wystawieniem.

### 6.3 Wystawienie i posting faktury

Faktura zaczyna w statusie `Draft`.

Akcja `Send Invoice`:

- przelicza dokument;
- zmienia status na `Sent`;
- tworzy journal sprzedaży;
- blokuje zmianę pól księgowych;
- wysyła e-mail, jeżeli klient ma primary contact email.

Po wystawieniu nie edytuj kwoty ani VAT. Błąd popraw przez sprzedażową credit note.

### 6.4 Płatność klienta

Na fakturze użyj `Mark as Paid`, jeżeli rejestrujesz całą pozostałą płatność ręcznie. Wybierz metodę, datę, referencję oraz rachunek bankowy, gdy jest wymagany.

Alternatywnie po imporcie wyciągu otwórz dodatnią transakcję bankową i użyj `Create customer payment`, wskazując istniejącą fakturę.

Płatność:

- zmniejsza `amount_due`;
- zmienia status faktury na `Partially paid` albo `Paid`;
- tworzy journal płatności;
- może utworzyć realised FX gain lub loss dla waluty obcej.

### 6.5 Sprzedażowa nota kredytowa

Na wystawionej fakturze użyj `Create credit note`.

1. Podaj przyczynę.
2. Wybierz datę i tax point.
3. Wybierz pozycje i ilości podlegające korekcie.
4. Sprawdź draft noty.
5. Użyj `Issue`, aby zaksięgować korektę.
6. Użyj `Allocate`, aby przypisać kredyt do salda faktury.

Statusy noty: `Draft -> Issued -> Partially allocated -> Allocated`.

Draft można anulować akcją `Void`. Wystawiona nota tworzy odwrócenie odpowiedniej części sprzedaży, VAT i należności.

## 7. Zakupy, koszty i VAT naliczony

### 7.1 Dostawca

Przejdź do `Finance > Suppliers`. Utwórz dostawcę przed wprowadzeniem kosztu, jeżeli chcesz zachować pełną historię i raport Aged Payables.

Sprawdź:

- nazwę prawną;
- adres;
- numer VAT;
- domyślne warunki płatności;
- status `Active`.

### 7.2 Tworzenie kosztu

Przejdź do `Finance > Expenses` i wybierz `Create`.

Uzupełnij:

1. dostawcę;
2. supplier invoice number;
3. issue date;
4. tax point;
5. received date;
6. due date;
7. walutę i kurs GBP, jeżeli dokument nie jest w GBP;
8. business purpose;
9. opis pozycji;
10. kategorię kosztu;
11. konto ledgeru;
12. tax code;
13. ilość i cenę jednostkową.

Formularz pokazuje podgląd netto, VAT i brutto przed zapisem. Cena jednostkowa zachowuje do 10 miejsc dziesiętnych. Jest to istotne przy pozycjach takich jak `1250 x 0.0333333333`.

### 7.3 Dokument źródłowy

Po zapisaniu otwórz koszt i użyj `Upload attachment`.

Najczęściej wybierz:

- `Invoice` dla faktury zakupowej;
- `Receipt` dla paragonu lub pokwitowania;
- `Contract` dla umowy pomocniczej;
- `Other` tylko gdy żaden dokładniejszy typ nie pasuje.

Do każdego miesięcznego kosztu cyklicznego dołącz osobną fakturę za dany okres. Wyciąg bankowy, statement albo pro-forma nie są automatycznie równoważne prawidłowej fakturze VAT.

### 7.4 Zatwierdzenie i posting

Przepływ kosztu:

```text
Draft -> Approved -> Posted -> Partially paid -> Paid
```

1. Operator sprawdza dane i dokument.
2. Osoba z `approve_finance` używa `Approve`.
3. Osoba z `post_finance` używa `Post`.
4. Po postingu koszt trafia do ledgeru, P&L, Trial Balance i VAT Detail.
5. Użyj `Record payment` albo połącz wydatek z ujemną transakcją bankową.

System wymaga pozycji, dodatniej kwoty i business purpose przed zatwierdzeniem. Aktualnie nie wymusza faktury jako warunku zwykłego kosztu, dlatego kontrola załącznika jest obowiązkiem operatora i bookkeepera.

### 7.5 Kiedy koszt pojawia się w raportach

- `Draft` nie pojawia się w Expense Report ani P&L.
- `Approved` pojawia się w Expense Report.
- `Posted`, `Partially paid` i `Paid` pojawiają się w Expense Report i w raportach ledgerowych.
- P&L używa daty journalu i wybranego okresu.

### 7.6 Płatność kosztu

Na zaksięgowanym koszcie wybierz `Record payment` i podaj:

- kwotę;
- źródło `Paid from`;
- metodę płatności;
- rachunek bankowy, jeżeli źródłem jest firmowy rachunek bankowy;
- referencję;
- datę;
- kurs rozliczenia dla waluty obcej.

Pole `Paid from` i metoda płatności mają różne znaczenie. Metoda opisuje sposób zapłaty, na przykład karta albo przelew. `Paid from` wskazuje, czy użyto firmowego rachunku bankowego, gotówki firmowej, czy prywatnych środków właściciela.

Dla profilu `sole_trader` dostępna jest opcja `Prywatne środki właściciela` (`Owner's personal funds` w interfejsie angielskim). Użyj jej, jeżeli właściciel opłacił firmowy koszt prywatnymi środkami. System pokaże podgląd i zaksięguje płatność jako:

```text
Dr 2100 Trade creditors
Cr 3300 Owner's capital introduced
```

Taka płatność nie zmienia salda konta 1100 ani żadnego firmowego rachunku bankowego i nie pojawia się w bank matching. Sam koszt nadal wpływa na P&L w momencie postingu. Płatność zmniejsza zobowiązanie wobec dostawcy i zwiększa kapitał właściciela w Balance Sheet.

Opcja prywatnych środków nie jest dostępna dla limited company ani partnership. Najpierw ustaw poprawny rodzaj podmiotu w `Finance > Accounting profile`. Nie używaj tej funkcji do owner drawings, director loan ani wydatków prywatnych niezwiązanych z działalnością.

Możesz również otworzyć ujemną transakcję bankową i wybrać `Link existing expense`. Ta akcja może zatwierdzić draft, zaksięgować koszt, utworzyć płatność i uzgodnić transakcję, jeżeli użytkownik ma wszystkie wymagane uprawnienia.

Nie twórz nowego kosztu z transakcji bankowej, jeżeli koszt został już wprowadzony. Użyj `Link existing expense`, aby uniknąć duplikatu.

### 7.7 Zakupowa nota kredytowa

Otwórz koszt i użyj `Create purchase credit note`.

Następnie:

1. sprawdź pozycje i kwoty;
2. użyj `Issue`, aby zaksięgować notę;
3. użyj `Allocate`, jeżeli nota zmniejsza niezapłacone saldo kosztu;
4. użyj `Refund`, jeżeli dostawca zwrócił pieniądze za wcześniej opłacony koszt.

Statusy: `Draft -> Issued -> Partially settled -> Settled`.

Nie wprowadzaj zwrotu przekraczającego zapłaconą kwotę ani pozostałe saldo noty.

## 8. Koszty cykliczne

`Recurring Expense` jest szablonem harmonogramu. Nie jest kosztem księgowym i sam nie pojawia się w raportach.

### 8.1 Tworzenie szablonu

Przejdź do `Finance > Recurring expenses` i ustaw:

- nazwę;
- status `Active`;
- częstotliwość: weekly, monthly, quarterly albo yearly;
- interval;
- `Start date`;
- `Next run date`;
- opcjonalny `End date`;
- dostawcę, business purpose i warunki płatności;
- pozycje, tax codes, kategorie i konta.

`Start date` opisuje początek harmonogramu. `Next run date` określa dokładną datę najbliższego wygenerowania. System nie tworzy automatycznie wcześniejszych pozycji, jeżeli `Next run date` ustawiono w przyszłości.

### 8.2 Automatyczne generowanie

Scheduler uruchamia `finance:generate-recurring-expenses` codziennie o 01:30 UTC. Na serwerze Website Expert cron wywołuje Laravel Scheduler co minutę.

W terminie system tworzy rzeczywisty `Expense` w statusie `Draft` i przesuwa `Next run date`.

Po wygenerowaniu:

1. znajdź nowy draft w `Finance > Expenses`;
2. wpisz prawdziwy numer faktury i sprawdź daty;
3. popraw kwoty, gdy faktura różni się od szablonu;
4. dołącz fakturę za dany okres;
5. poczekaj na status skanu `Clean`;
6. zatwierdź i zaksięguj;
7. zarejestruj lub uzgodnij płatność.

Zaległe okresy możesz wprowadzić ręcznie jako zwykłe Expenses. Zachowaj przyszły `Next run date`, aby nie utworzyć duplikatu.

### 8.3 Jedna faktura roczna

Jeżeli dostawca wystawia jedną fakturę roczną, nie kopiuj jej do 12 niezależnych kosztów miesięcznych. Wprowadź fakturę raz. Miesięczne rozliczenie prepayment lub accrual powinno zostać wykonane według instrukcji księgowego. Moduł kosztów cyklicznych nie jest silnikiem rozliczeń międzyokresowych.

## 9. Załączniki i skanowanie bezpieczeństwa

### 9.1 Limity

- maksymalny rozmiar pliku: 10 MB;
- obsługiwane są PDF, JPEG, PNG, WEBP, TXT, CSV, XLSX i DOCX;
- pliki są przechowywane prywatnie;
- okres retencji skonfigurowano na 6 lat.

### 9.2 Statusy skanowania

| Status | Znaczenie | Działanie |
|---|---|---|
| `Pending` | Plik czeka na skan | Poczekaj na worker |
| `Clean` | Plik przeszedł skan i kontrolę integralności | Można otworzyć i użyć jako dowód |
| `Rejected` | Wykryto zagrożenie albo niedozwolony wynik | Nie używaj pliku, wgraj bezpieczną kopię |
| `Failed` | Skan nie zakończył się technicznie | Użyj `Retry attachment scan` |

System sprawdza rozmiar i SHA-256 przed skanowaniem oraz przed wydaniem pliku.

### 9.3 Korekty VAT

Dla VAT adjustment co najmniej jeden załącznik typu `VAT adjustment evidence` ze statusem `Clean` jest obowiązkowy przed `Approve` i `Post`.

## 10. Rachunki bankowe i import CSV

### 10.1 Dodanie rachunku

Przejdź do `Finance > Bank accounts` i podaj:

- nazwę rachunku;
- bank;
- account number;
- sort code;
- walutę;
- konto ledgeru typu cash;
- opening balance i jego datę;
- status `Active`.

Account number i sort code są chronione i maskowane w panelu.

### 10.2 Import wyciągu

Otwórz rachunek i wybierz `Import CSV`.

Zalecana procedura:

1. Wybierz plik bez jego ręcznej edycji.
2. Pozostaw delimiter, date format i decimal separator jako `Auto`, jeżeli profil banku jest rozpoznawany.
3. Sprawdź automatyczne mapowanie kolumn.
4. Pozostaw `Dry run` włączony.
5. Uruchom import i przeczytaj podsumowanie oraz błędy wierszy.
6. Jeżeli wynik jest poprawny, uruchom ponownie z wyłączonym `Dry run`.

Ograniczenia importu:

- maksymalnie 5 MB;
- maksymalnie 5000 wierszy;
- kodowanie UTF-8 lub Windows-1252;
- obsługiwane są tylko waluty skonfigurowane w aplikacji;
- transakcje Revolut ze statusem innym niż completed są odrzucane;
- niezerowa opłata bankowa w osobnej kolumnie wymaga jawnego księgowania i jest obecnie odrzucana;
- deduplikacja korzysta z provider ID lub kanonicznego fingerprintu.

Ponowny import tego samego pliku nie powinien tworzyć duplikatów.

### 10.3 Uzgodnienie transakcji

Otwórz `Finance > Bank transactions`.

Dla wpływu dodatniego możesz:

- uzgodnić z istniejącą płatnością;
- użyć `Create customer payment` i wskazać fakturę.

Dla wypływu ujemnego możesz:

- uzgodnić z istniejącą płatnością kosztu;
- użyć `Link existing expense`;
- użyć `Create supplier expense`, jeżeli koszt jeszcze nie istnieje.

Statusy transakcji:

- `Unmatched`;
- `Partially reconciled`;
- `Reconciled`.

Jedną transakcję można rozdzielić na kilka alokacji. Suma alokacji nie może przekroczyć bezwzględnej kwoty transakcji.

### 10.4 Cofnięcie uzgodnienia

Użyj `Undo reconciliation`, wybierz partię uzgodnienia i podaj przyczynę. Operacja nie usuwa historii. Oznacza uzgodnienie jako reversed i zachowuje audyt.

## 11. Ledger i okresy księgowe

### 11.1 Automatyczny posting

System tworzy journal automatycznie przy:

- wystawieniu faktury sprzedaży;
- wystawieniu sprzedażowej credit note;
- postingu kosztu;
- wystawieniu purchase credit note;
- płatności klienta;
- płatności dostawcy;
- zwrocie od dostawcy;
- postingu korekty VAT.

Każdy journal musi mieć równe debit i credit w GBP. Zakwalifikowane journal entries są niezmienne.

### 11.2 Journal entries

`Finance > Journal entries` jest widokiem tylko do odczytu. Pokazuje:

- numer i datę wpisu;
- źródło;
- okres księgowy;
- linie debit i credit;
- tax code i wymiary;
- kwoty walutowe oraz GBP;
- status i powiązanie ze stornem;
- hashe audytowe.

Nie poprawiaj zaksięgowanego dokumentu przez bezpośrednią zmianę journalu. Użyj credit note, purchase credit note, VAT adjustment albo kontrolowanego reversal zgodnie z rodzajem błędu.

### 11.3 General Ledger

`Finance > General Ledger` pokazuje historię wybranego konta, saldo otwarcia, obroty i saldo narastające. Ustaw zakres dat i konto przed analizą.

## 12. Raporty

| Raport | Źródło | Warunek ujęcia | Zastosowanie |
|---|---|---|---|
| Expense Report | Expense items i purchase credits | Koszt od `Approved`, nota od `Issued` | Analiza kosztów według kategorii, dostawcy, projektu lub klienta |
| Profit and Loss | Ledger | Journale `Posted` i `Reversed` w okresie | Przychody, koszty i wynik |
| Trial Balance | Ledger | Journale `Posted` i `Reversed` | Kontrola sald debit i credit |
| Balance Sheet | Ledger | Zapis do wybranej daty | Aktywa, zobowiązania i kapitał |
| Management Cash Flow | Ledger i konta cash | Zaksięgowane ruchy gotówkowe | Zarządcza analiza przepływów, nie statutory cash flow statement |
| Aged Receivables / Payables | Konta AR i AP | Zaksięgowane otwarte salda do daty | Należności i zobowiązania według wieku |
| Bank Reconciliation | Transakcje bankowe i aktywne alokacje | Zaimportowane transakcje | Stopień uzgodnienia banku |
| VAT Detail | Ledger i tax codes | Zaksięgowane linie w zakresie | Źródła output VAT, input VAT i pól VAT Return |
| General Ledger | Journal lines | Zaksięgowane i odwrócone journale | Pełny ślad księgowy konta |

Trial Balance, Expense Report, Profit and Loss, Balance Sheet, Bank Reconciliation, Management Cash Flow, Aged Receivables and Payables oraz VAT Detail mają trzy formaty eksportu:

- `CSV` do prostego przetwarzania danych;
- `Excel` jako skoroszyt XLSX z komórkami liczbowymi, formatami kwot i podsumowaniami;
- `PDF` jako gotowy do przekazania lub archiwizacji dokument z nazwą firmy, parametrami raportu i tabelami.

Eksport zawsze używa filtrów aktualnie ustawionych na ekranie. Pliki są generowane na żądanie, nie są publiczne i wymagają uprawnienia `view_finance`. Paczka dla księgowego jest nadal dostępna osobno na zablokowanym VAT Return.

Jeżeli raport jest pusty, sprawdź kolejno:

1. tenant;
2. zakres dat lub accounting period;
3. status dokumentu;
4. czy wykonano `Post`;
5. czy journal ma datę w wybranym okresie;
6. czy konto ma właściwy typ;
7. czy nie wybrano niewłaściwego filtra.

## 13. VAT Detail i dziewięć pól VAT Return

### 13.1 Kontrola VAT Detail

Przejdź do `Reports > VAT Detail` i ustaw okres dokładnie zgodny z deklaracją.

Sprawdź:

- liczbę dokumentów i journali;
- sprzedaż netto i zakupy netto;
- output VAT i input VAT;
- zgodność z kontami kontrolnymi VAT;
- podsumowanie tax codes;
- szczegóły każdego dokumentu;
- ostrzeżenia i problemy blokujące.

Status `Reconciled` oznacza, że szczegóły VAT zgadzają się z ruchem kont kontrolnych. Nie oznacza automatycznie, że klasyfikacja podatkowa każdej transakcji jest poprawna.

### 13.2 Pola deklaracji

System oblicza:

1. VAT należny od sprzedaży i innych outputs;
2. VAT należny od odpowiednich nabyć towarów w NI z państw UE;
3. sumę pól 1 i 2;
4. VAT odzyskiwany od zakupów i innych inputs;
5. VAT netto do zapłaty albo odzyskania;
6. sprzedaż i inne outputs bez VAT;
7. zakupy i inne inputs bez VAT;
8. odpowiednie dostawy towarów z NI do państw UE;
9. odpowiednie nabycia towarów do NI z państw UE.

Pola 6-9 są przekazywane do HMRC w pełnych funtach zgodnie z formatem API. Panel pokazuje również wartości ledgerowe przed konwersją.

### 13.3 Typowe blokady

VAT Return nie będzie gotowy, gdy:

- firma nie jest oznaczona jako VAT registered;
- brakuje numeru VAT lub dat obowiązywania;
- wybrano nieobsługiwany schemat `cash` albo `flat_rate`;
- okres wykracza poza rejestrację VAT;
- VAT Detail nie uzgadnia się;
- kwota VAT nie ma poprawnego mapowania do pola;
- outside scope lub reverse charge wymaga weryfikacji;
- korekta błędu wymaga osobnego zgłoszenia do HMRC.

## 14. Korekty VAT

Korektę twórz w `Finance > VAT adjustments` tylko po ustaleniu sposobu rozliczenia z księgowym.

### 14.1 Rodzaje

- `Accounting adjustment` dla zwykłej korekty księgowej, która nie jest błędem wcześniejszej deklaracji;
- `Error correction` dla błędu dotyczącego wcześniejszego okresu.

Ustaw:

- stronę `Output` albo `Input`;
- kierunek `Increase` albo `Decrease`;
- correction method;
- effective date;
- datę wykrycia i okres pierwotny dla error correction;
- tax code;
- VAT control account;
- contra account;
- kwotę w GBP;
- dokładną przyczynę;
- HMRC reference, jeżeli istnieje.

### 14.2 Obowiązkowy dowód

1. Zapisz draft.
2. Użyj `Upload evidence`.
3. Poczekaj na `Clean`.
4. Użyj `Approve`.
5. Użyj `Post`.

Korekta dopiero po postingu wpływa na VAT Detail i VAT Return.

Nie oznaczaj umyślnego błędu jako current return. System wymaga dla niego separate notification. Ostateczny sposób korekty i obowiązek kontaktu z HMRC potwierdza księgowy.

## 15. Lokalny VAT Return

### 15.1 Utworzenie snapshotu do przeglądu

W `Reports > VAT Detail`:

1. ustaw daty zgodne z obligation HMRC;
2. usuń wszystkie problemy blokujące;
3. sprawdź ostrzeżenia;
4. wyeksportuj VAT Detail CSV, jeżeli potrzebna jest kontrola poza systemem;
5. wybierz `Create VAT Return review`.

Powstaje VAT Return w statusie `Draft` z:

- dziewięcioma polami;
- listą dokumentów źródłowych;
- liczbą journali i linii;
- snapshotem ustawień profilu;
- SHA-256 snapshotu.

Samo utworzenie VAT Return niczego nie wysyła do HMRC.

### 15.2 Review

Otwórz `Finance > VAT Returns` i wybierz dokument.

Bookkeeper powinien:

1. porównać pola 1-9 z VAT Detail;
2. sprawdzić transakcje nietypowe;
3. sprawdzić credit notes i purchase credits;
4. sprawdzić korekty VAT i dowody;
5. porównać bank, AR i AP;
6. zapisać uwagi;
7. zaznaczyć potwierdzenie i użyć `Review`.

Jeżeli dane ledgeru zmieniły się po utworzeniu snapshotu, review zostanie zablokowany. Użyj `Supersede` na starym draft lub reviewed return i utwórz nowy snapshot.

### 15.3 Lock

Po review osoba z `close_finance_periods` używa `Lock` i podaje przyczynę.

Lock:

- utrwala snapshot;
- blokuje wpisy z datą wewnątrz zakresu VAT Return;
- umożliwia pobranie accountant pack;
- jest wymagany przed submission.

Nie ma standardowej akcji odblokowania locked VAT Return. Przed blokadą wykonaj pełną kontrolę.

## 16. HMRC MTD VAT

### 16.1 Stan środowiska

Na dzień tej instrukcji integracja jest przeznaczona do sandbox end-to-end. Produkcyjny submission pozostaje zablokowany do spełnienia checklisty bezpieczeństwa, akceptacji księgowego i wymogów HMRC.

Zawsze sprawdź environment na stronie `Finance > HMRC connection`. Testowe potwierdzenie nie jest złożeniem prawdziwej deklaracji.

### 16.2 Wymagania użytkownika

Użytkownik wykonujący operacje HMRC musi mieć:

- aktywne członkostwo w bieżącej firmie;
- `submit_tax_returns` dla submission i receipt;
- skonfigurowane MFA przez authenticator app;
- świeży kod TOTP przy operacji;
- przeglądarkę z włączonym JavaScript;
- połączenie z publicznego adresu, dla którego system może zebrać wymagane fraud prevention headers.

Nie zapisuj kodu TOTP w notatkach ani dokumentach.

### 16.3 Połączenie

1. Otwórz `Finance > HMRC connection`.
2. W sandboxie ustaw testowy VRN akcją `Set sandbox VRN`.
3. Wybierz `Connect`.
4. Zaloguj się danymi HMRC test user.
5. Zatwierdź wymagane scopes.
6. Po powrocie sprawdź status `Connected`, environment i datę wygaśnięcia tokenu.

`Refresh` odnawia token. `Disconnect` usuwa lokalne połączenie. Cofnięcie autoryzacji po stronie HMRC wykonuje się oddzielnie w HMRC.

### 16.4 Fraud prevention headers

W sandboxie:

1. wybierz `Validate fraud prevention headers`;
2. wpisz bieżący kod authenticatora;
3. sprawdź wynik HMRC;
4. użyj `Retrieve fraud header feedback`, jeżeli potrzebujesz szczegółów.

`INVALID_HEADERS` wymaga poprawki przed dalszym testem. `POTENTIALLY_INVALID_HEADERS` z samym ostrzeżeniem dotyczącym `Gov-Vendor-License-IDs` jest obecnie dopuszczone wyłącznie w sandboxie zgodnie z konfiguracją testową. Produkcja działa fail-closed i wymaga rozwiązania tego warunku.

Wartości nagłówków i tokeny nie są pokazywane ani zapisywane w panelu.

### 16.5 Synchronizacja obligations

Wybierz `Synchronise VAT obligations`.

1. Podaj zakres nieprzekraczający 366 dni.
2. Opcjonalnie wybierz Open, Fulfilled albo oba.
3. Wpisz kod TOTP.
4. Uruchom synchronizację.

Wynik pokazuje liczbę received, new, updated i unchanged. Ponowne pobranie tych samych danych jest bezpieczne i nie tworzy duplikatów.

`Open` oznacza okres dostępny do złożenia. `Fulfilled` oznacza okres spełniony według HMRC.

### 16.6 View VAT Return

Akcja `View VAT Return` odczytuje deklarację bezpośrednio z HMRC dla fulfilled obligation.

- wynik jest read-only;
- pola są pokazywane w panelu;
- odczyt nie zmienia ledgeru;
- komunikat sukcesu bez pól może oznaczać problem prezentacji i powinien zostać zgłoszony przed wykorzystaniem wyniku.

### 16.7 Liabilities and payments

Akcja `Retrieve VAT liabilities and payments` pobiera dane dla zakresu do 366 dni.

Te dane:

- pochodzą bezpośrednio z HMRC;
- są tylko do odczytu;
- nie tworzą journali;
- nie są automatycznym potwierdzeniem uzgodnienia rachunku bankowego.

### 16.8 Submission

Przed wysyłką muszą być spełnione wszystkie warunki:

- VAT Return ma status `Locked`;
- istnieje odpowiadająca mu open obligation HMRC z identycznymi datami;
- połączenie jest `Connected`;
- token ma scope `write:vat`;
- użytkownik ma `submit_tax_returns`;
- MFA działa;
- fraud prevention headers są kompletne;
- snapshot i payload mają prawidłowe hashe;
- nie istnieje wcześniejszy skuteczny submission dla tego VAT Return.

Procedura:

1. Otwórz locked VAT Return.
2. Opcjonalnie pobierz `Accountant pack` i uzyskaj akceptację.
3. Wybierz `Submit to HMRC`.
4. Przeczytaj deklarację użytkownika.
5. Zaznacz świadome potwierdzenie.
6. Wpisz świeży kod TOTP.
7. Wyślij tylko raz.

System zapisuje próbę `Prepared` przed wywołaniem API. Nie wykonuje ślepego automatycznego ponowienia POST.

### 16.9 Wyniki submission

| Status | Znaczenie | Działanie |
|---|---|---|
| `Submitted` | HMRC zwrócił poprawne HTTP 201 i receipt | Pobierz PDF receipt i zsynchronizuj obligations |
| `Rejected` | HMRC jednoznacznie odrzucił żądanie | Odczytaj kod, usuń przyczynę, nie zgaduj |
| `Outcome unknown` | Timeout lub niejednoznaczna odpowiedź | Nie wysyłaj ponownie, użyj `Reconcile with HMRC` |
| `Outcome conflict` | Dane odczytane z HMRC nie zgadzają się z lokalnym snapshotem | Zatrzymaj proces i przeprowadź ręczną analizę |

Przy `DUPLICATE_SUBMISSION` także użyj reconciliation. System wykonuje wyłącznie odczyt obligations i return, bez ponownego POST.

### 16.10 Receipt i kontrola po wysyłce

Po sukcesie:

1. wybierz `Download HMRC receipt`;
2. zapisz PDF w kontrolowanym repozytorium dokumentów;
3. sprawdź processing date i form bundle number;
4. ponownie zsynchronizuj obligations;
5. potwierdź, że obligation ma status `Fulfilled`;
6. odczytaj VAT Return z HMRC;
7. porównaj wszystkie dziewięć pól z lokalnym snapshotem;
8. w późniejszym terminie sprawdź liabilities i payments.

Receipt zawiera ślad audytowy i hashe. Nie jest fakturą ani dowodem zapłaty VAT.

## 17. Pełna procedura kwartalna VAT

### Etap A - kompletność danych

- [ ] Wszystkie faktury sprzedaży zostały wystawione i zaksięgowane.
- [ ] Wszystkie faktury zakupowe zostały wprowadzone, opisane i załączone.
- [ ] Wszystkie credit notes zostały wystawione i zaksięgowane.
- [ ] Koszty cykliczne mają rzeczywiste Expenses i dokumenty.
- [ ] Załączniki mają status `Clean`.
- [ ] Wszystkie bank statements zostały zaimportowane.
- [ ] Transakcje bankowe zostały uzgodnione albo wyjaśnione.

### Etap B - kontrola księgi

- [ ] Trial Balance jest zbilansowany.
- [ ] General Ledger nie zawiera nieoczekiwanych zapisów.
- [ ] P&L i Balance Sheet są racjonalne.
- [ ] Aged Receivables i Payables zgadzają się z otwartymi saldami.
- [ ] Bank Reconciliation nie ma niewyjaśnionych istotnych pozycji.

### Etap C - kontrola VAT

- [ ] HMRC obligations zostały zsynchronizowane.
- [ ] Zakres VAT Detail odpowiada dokładnie open obligation.
- [ ] VAT Detail jest reconciled.
- [ ] Tax codes i transakcje nietypowe zostały sprawdzone.
- [ ] VAT adjustments mają clean evidence i status `Posted`.
- [ ] Problemy blokujące zostały usunięte.
- [ ] Ostrzeżenia zostały opisane lub zaakceptowane.

### Etap D - review i lock

- [ ] Utworzono świeży VAT Return review.
- [ ] Bookkeeper sprawdził pola 1-9.
- [ ] VAT Return ma status `Reviewed`.
- [ ] Pobrano accountant pack.
- [ ] Uzyskano zgodę osoby odpowiedzialnej.
- [ ] VAT Return został zablokowany z uzasadnieniem.

### Etap E - HMRC

- [ ] Environment jest właściwy.
- [ ] HMRC connection ma status `Connected`.
- [ ] Fraud prevention validation jest akceptowalny dla środowiska.
- [ ] Użytkownik ma MFA i `submit_tax_returns`.
- [ ] Open obligation ma identyczne daty.
- [ ] Deklarację wysłano jeden raz.
- [ ] Receipt został pobrany.
- [ ] Obligation po synchronizacji ma status `Fulfilled`.
- [ ] View VAT Return zgadza się z lokalnym snapshotem.

## 18. Codzienna, miesięczna i roczna obsługa

### Codziennie lub przy każdym dokumencie

- wprowadź dokument z właściwym tax point;
- dołącz dowód;
- sprawdź skan;
- zatwierdź i zaksięguj;
- zarejestruj płatność albo pozostaw otwarte saldo;
- nie usuwaj dokumentów już zaksięgowanych.

### Co tydzień

- sprawdź nowe drafty kosztów cyklicznych;
- sprawdź `Pending` i `Failed` attachments;
- sprawdź nieuzgodnione transakcje bankowe;
- sprawdź overdue invoices i due supplier expenses.

### Co miesiąc

- zaimportuj pełny wyciąg;
- uzgodnij bank;
- przejrzyj P&L, Balance Sheet i Trial Balance;
- sprawdź AR i AP;
- sprawdź VAT Detail narastająco;
- rozpocznij closing po zakończeniu wprowadzania danych.

### Po zakończeniu roku

- uzgodnij wszystkie konta;
- przekaż accountant pack i raporty księgowemu;
- wprowadź zatwierdzone korekty końcowe;
- zamknij, a dopiero po finalnej akceptacji zablokuj okres;
- Corporation Tax i statutory accounts przygotuj poza tym modułem albo z księgowym.

## 19. Rozwiązywanie typowych problemów

### Recurring Expense jest Active, ale nie ma go w raporcie

Przyczyny:

- szablon nie jest dokumentem księgowym;
- `Next run date` jeszcze nie nadszedł;
- scheduler nie wykonał zadania;
- wygenerowany Expense ma status `Draft`;
- Expense nie został zaksięgowany;
- raport ma niewłaściwy zakres dat.

Sprawdź kolumnę Generated, listę Expenses i log schedulera.

### Expense jest zapisany, ale nie ma go w P&L

- `Draft` nie jest raportowany;
- `Approved` pojawia się w Expense Report, ale nie w P&L;
- użyj `Post`;
- sprawdź accounting period i datę journalu.

### Faktura lub koszt nie daje się edytować

Po utworzeniu journalu pola księgowe stają się niezmienne. Użyj odpowiedniej credit note, purchase credit note lub korekty. Nie usuwaj wpisu z bazy.

### Attachment pozostaje Pending

1. Odśwież rekord po krótkim czasie.
2. Sprawdź, czy queue worker i ClamAV działają.
3. Sprawdź failed jobs.
4. Dla `Pending` lub `Failed` użyj `Retry attachment scan`.
5. Nie uznawaj pliku za zweryfikowany przed `Clean`.

Runbook techniczny: [Skaner bezpieczeństwa załączników](../operations/financial-attachment-security-scanner.md).

### CSV nie importuje się

Najpierw użyj dry run. Sprawdź:

- kodowanie;
- delimiter;
- format daty;
- separator dziesiętny;
- mapowanie amount albo debit/credit;
- walutę;
- status transakcji;
- niezerową opłatę bankową;
- limit rozmiaru i liczby wierszy.

### Transakcji bankowej nie można połączyć z kosztem

Wybrany koszt musi:

- należeć do tego samego tenant;
- mieć zgodną walutę;
- mieć otwarte `amount_due` wystarczające dla pozostałej kwoty transakcji;
- mieć dozwolony status;
- nie być już w pełni uzgodniony.

### VAT Detail nie uzgadnia się

Sprawdź dokumenty bez tax code, ręczne journale, VAT adjustments, konta VAT oraz wpisy odwracające. Porównaj różnicę output i input z konkretnymi liniami ledgeru.

### Nie można utworzyć VAT Return review

Usuń wszystkie problemy blokujące. Szczególnie sprawdź:

- VAT registered;
- numer i daty VAT;
- schemat standard albo annual;
- zakres dat;
- reconciliation VAT Detail;
- mapowanie tax codes.

### HMRC zwraca 401 lub reconnect required

Użyj `Refresh`. Jeżeli odnowienie nie działa, wykonaj `Connect` ponownie. Nie kopiuj tokenów do panelu ani logów.

### HMRC zwraca invalid headers

Uruchom walidację fraud headers i odczytaj feedback. Sprawdź publiczny IP, port klienta, TLS forwarding chain, vendor headers, MFA i telemetrię przeglądarki. Nie omijaj wymagań w produkcji.

### HMRC request zakończył się timeoutem

Nie naciskaj `Submit` ponownie. Status powinien przejść do `Outcome unknown`. Użyj `Reconcile with HMRC` z nowym kodem TOTP.

### Submission przeszedł, ale obligation nadal jest Open

Odczekaj na aktualizację HMRC i wykonaj ponowną synchronizację. Sprawdź również `View VAT Return`. Nie składaj drugiej deklaracji tylko dlatego, że status nie zmienił się natychmiast.

## 20. Bezpieczeństwo i audyt

- Nigdy nie umieszczaj HMRC client secret, tokenów, TOTP, UTR ani pełnego VRN w dokumentacji, zgłoszeniach i zrzutach ekranu.
- Używaj osobnych credentials dla sandbox i production.
- Nie udostępniaj załączników przez publiczne URL.
- Nie poprawiaj danych finansowych bezpośrednio w bazie.
- Zachowuj przyczynę każdego reopen, lock, void, reversal i adjustment.
- Sprawdzaj tenant przed każdą operacją.
- Korzystaj z indywidualnych kont użytkowników i MFA.
- Nadawaj minimalny zestaw uprawnień.
- Przechowuj VAT records przez wymagany okres. Konfiguracja aplikacji ustawia retencję załączników na 6 lat.

HMRC wymaga zachowania otrzymanych i wystawionych faktur oraz cyfrowych danych VAT. Prawidłowa faktura VAT jest podstawowym dowodem odzyskania input VAT. Dokumenty VAT należy co do zasady przechowywać co najmniej 6 lat.

## 21. Słownik

| Pojęcie | Znaczenie w systemie |
|---|---|
| Tenant | Oddzielna firma i jej dane |
| Tax point | Data rozpoznania transakcji dla VAT |
| Output VAT | VAT należny od sprzedaży |
| Input VAT | VAT naliczony przy zakupach |
| Ledger | Księga podwójnego zapisu |
| Journal | Zbilansowany zestaw debit i credit |
| Posting | Utworzenie journalu na podstawie dokumentu |
| Reconciliation | Powiązanie transakcji bankowej z dokumentem lub płatnością |
| Snapshot | Niezmienny zestaw danych użyty do review VAT Return |
| Obligation | Okres deklaracyjny zwrócony przez HMRC |
| Receipt | Techniczne potwierdzenie przyjęcia VAT Return przez HMRC |
| TOTP | Jednorazowy kod z aplikacji authenticator |
| Fraud prevention headers | Dane wymagane przez HMRC dla wywołań API |

## 22. Dokumenty uzupełniające

- [Konfiguracja i testy HMRC sandbox](../integrations/hmrc-mtd-vat-sandbox.md)
- [HMRC production readiness](../integrations/hmrc-production-readiness.md)
- [Instrukcja przeglądu dla bookkeepera](../review/uk-vat-mtd-bookkeeper-review-guide-pl.md)
- [Skaner bezpieczeństwa załączników](../operations/financial-attachment-security-scanner.md)
- [Plan modułu finansów i podatków UK](../plans/uk-finance-tax-module-plan.md)

## 23. Oficjalne źródła

- [GOV.UK - Keeping VAT records](https://www.gov.uk/charge-reclaim-record-vat/keeping-vat-records)
- [GOV.UK - Invoices and required information](https://www.gov.uk/invoicing-and-taking-payment-from-customers/invoices-what-they-must-include)
- [GOV.UK - Record keeping for VAT Notice 700/21](https://www.gov.uk/guidance/record-keeping-for-vat-notice-70021)
- [HMRC Developer Hub - VAT MTD API](https://developer.service.hmrc.gov.uk/api-documentation/docs/api/service/vat-api/1.0)
- [HMRC Developer Hub - VAT MTD obligations and returns guide](https://developer.service.hmrc.gov.uk/guides/vat-mtd-end-to-end-service-guide/documentation/obligations.html)

