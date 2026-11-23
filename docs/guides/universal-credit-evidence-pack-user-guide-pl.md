# Paczka dowodowa Universal Credit - instrukcja użytkownika

- Status: instrukcja operacyjna do lokalnych testów UKFIN-513
- Odbiorca: sole trader z Irlandii Północnej, upoważniony bookkeeper lub reviewer
- System: Website Expert NI

## 1. Do czego służy moduł

Moduł `Finance > Universal Credit` przygotowuje miesięczne wartości kasowe i dowody do zadania Universal Credit dla osoby samozatrudnionej.

Moduł nie:

- wysyła danych do Universal Credit;
- oblicza końcowego świadczenia UC, minimum income floor, przenoszonych strat ani surplus earnings;
- zastępuje rachunku zysków i strat, Self Assessment ani deklaracji VAT;
- rozstrzyga prawnie, czy nietypowy koszt jest dopuszczalny.

Wnioskodawca nadal loguje się na oficjalne konto Universal Credit, wykonuje zadanie `Report your income and expenses`, a następnie zapisuje potwierdzenie w Website Expert NI.

## 2. Przed pierwszym okresem

1. Sprawdź, czy `Accounting profile` ma typ `Sole trader`.
2. Sprawdź uprawnienia roli:
   - `view_universal_credit_reporting` do podglądu;
   - `manage_universal_credit_reporting` do przygotowania, przeglądu i blokady;
   - `export_universal_credit_pack` do raportów i paczek dowodowych.
3. Otwórz `Finance > Universal Credit` i wybierz `Skonfiguruj Universal Credit`.
4. Włącz raportowanie.
5. Jeśli działalność jest zarejestrowana do VAT, wybierz jedną spójną metodę:
   - kwoty zawierają VAT; albo
   - kwoty nie zawierają odzyskiwalnego VAT.
6. Podaj datę obowiązywania metody VAT.
7. Opcjonalnie zapisz zwykły dzień początku jako przypomnienie. Nie zastępuje on dokładnych dat z konta UC.

## 3. Miesięczny workflow

### Krok 1 - Utwórz dokładny assessment period

Przepisz daty `Od` i `Do` z konta Universal Credit wnioskodawcy. Nie wyliczaj ich z miesiąca kalendarzowego ani okresu księgowego. Zakres jest włącznie i nie może przekroczyć 31 dni.

Utwórz okres także wtedy, gdy nie było przychodów ani kosztów. Okres zerowy również trzeba zgłosić.

### Krok 2 - Pobierz przepływy pieniężne

Wybierz `Pobierz przepływy pieniężne`. Akcja jest idempotentna i można ją powtarzać, dopóki okres jest wersją roboczą.

Raport używa daty faktycznego otrzymania lub zapłaty środków. Sama faktura sprzedaży lub kosztowa nie wystarcza, jeśli w okresie nie nastąpił przepływ pieniędzy.

Każda pozycja musi otrzymać stan:

- `Included` z dozwoloną kategorią UC; albo
- `Excluded` z jasnym powodem.

Nie ukrywaj aktywności osobistej, zdublowanej lub niedopuszczalnej. Jeśli jest istotna dla uzgodnienia źródeł, pozostaw ją jako jawne wykluczenie.

### Krok 3 - Ustal użytek firmowy

Przychód raportuje się w 100 procentach. Dla kosztu mieszanego podaj tylko procent firmowy i wyjaśnij podstawę.

Przykład: płatność za internet GBP 36 przy 50 procentach użytku firmowego daje GBP 18 do zgłoszenia.

### Krok 4 - Dodaj brakujące zdarzenia kasowe

Użyj `Dodaj ręczne zdarzenie kasowe`, gdy prawdziwego zdarzenia nie pobrano z faktur, wydatków, płatności ani banku. Przykłady to gotówkowy przychód, napiwek, świadczenie rzeczowe, kwalifikowana dotacja, zwrot podatku lub NI, przekazanie lub sprzedaż aktywa albo płatność podatku, NI, emerytury lub VAT.

Podaj:

- faktyczną datę przepływu wewnątrz okresu;
- kwotę oryginalną i walutę;
- snapshot kursu do GBP dla waluty obcej;
- czytelny opis;
- plik dowodowy albo powód jego braku.

Ręczne zdarzenie jest niezmienne. Błędną pozycję unieważnij z powodem i dodaj jej poprawny zamiennik.

### Krok 5 - Dodaj wybrane koszty uproszczone

Moduł obsługuje:

- przebieg pojazdu;
- pracę z domu;
- mieszkanie w lokalu działalności.

Nie zgłaszaj jednocześnie kosztu uproszczonego i kolidującego kosztu rzeczywistego. System blokuje znane podwójne roszczenia, ale użytkownik odpowiada za prawdziwość wyboru i danych wejściowych.

### Krok 6 - Sprawdź dowody i oblicz

Dołącz rachunki, faktury, dowody bankowe i pozostałe dokumenty. Pliki są prywatne i muszą przejść skan bezpieczeństwa, zanim trafią do ZIP.

Wybierz `Oblicz` i sprawdź wskaźnik gotowości:

- `Gotowy` - brak problemów i ostrzeżeń;
- `Ostrzeżenia` - można kontynuować po sprawdzeniu i jawnym zaakceptowaniu każdego ostrzeżenia;
- `Zablokowany` - przed przeglądem usuń wszystkie problemy blokujące.

Po zmianie klasyfikacji lub kosztu uproszczonego wykonaj obliczenie ponownie.

### Krok 7 - Przejrzyj i zablokuj

Wybierz `Rozpocznij przegląd`, potwierdź deklaracje i dodaj przydatne notatki. Przegląd tworzy snapshot SHA-256.

Wybierz `Zablokuj okres` i podaj konkretny powód. Po blokadzie wartości są niezmienne. Błędnego zablokowanego okresu nie edytuj bezpośrednio. Zastąp go i przygotuj nową wersję.

### Krok 8 - Wygeneruj i sprawdź paczkę

Wybierz `Wygeneruj paczkę dowodową`. Generowanie wykonuje kolejka, a tabela paczek odświeża się automatycznie.

Po statusie `Gotowa` pobierz prywatny ZIP. Sprawdź co najmniej:

- `01-summary/universal-credit-summary.pdf`;
- `01-summary/review-and-declaration.pdf`;
- `02-details/income-detail.csv`;
- `02-details/expense-detail.csv`;
- `02-details/excluded-and-private.csv`;
- `02-details/missing-evidence-and-warnings.csv`;
- `manifest.sha256.json`.

Do ZIP trafiają wyłącznie czyste pliki po kontroli integralności. Pliki brakujące, oczekujące, odrzucone lub zmienione pozostają widoczne w manifeście jako wykluczone.

### Krok 9 - Zgłoś ręcznie i zachowaj potwierdzenie

Przepisz wartości z zablokowanego podsumowania do oficjalnego zadania Universal Credit. Po poprawnym ręcznym zgłoszeniu wybierz `Oznacz jako zgłoszone`.

Możesz zachować:

- oficjalny numer potwierdzenia;
- zrzut ekranu lub PDF potwierdzenia.

Metadane potwierdzenia są niezmienne. Jest to wewnętrzny ślad audytowy, a nie dowód wysłania danych przez Website Expert NI.

## 4. Korekty

Użyj `Zastąp okres` z dokładnym powodem, jeśli wersja robocza, sprawdzona, zablokowana lub zgłoszona nie powinna być dalej bieżąca. Zachowaj stary okres do audytu i utwórz zamiennik dla tych samych dokładnych dat po usunięciu aktywnego nakładania.

Nigdy nie nadpisuj zablokowanego snapshotu, wygenerowanego ZIP ani potwierdzenia zgłoszenia.

## 5. Checklista dowodów

Przed przeglądem potwierdź, że okres zawiera lub wyjaśnia:

- każdy firmowy wpływ kartą, gotówką, czekiem i przelewem;
- świadczenia rzeczowe, napiwki, kwalifikowane dotacje oraz firmowe zwroty podatku lub NI;
- przekazanie lub sprzedaż aktywa wcześniej zgłoszonego jako koszt;
- każdą firmową płatność faktycznie wykonaną w okresie;
- część firmową kosztów mieszanych;
- mile, godziny pracy z domu albo liczbę mieszkańców przy kosztach uproszczonych;
- wyciągi i uzgodnienie aktywności bankowej;
- rachunki i faktury dla kosztów włączonych;
- jawny powód każdej pozycji wykluczonej i każdego niedostępnego dokumentu;
- spójne traktowanie VAT w całym właściwym okresie.

## 6. Oficjalne źródła

Poniższe strony sprawdzono 23 listopada 2026:

- [nidirect: Report business income and expenses if you're self-employed](https://www.nidirect.gov.uk/articles/report-business-income-and-expenses-if-youre-self-employed)
- [nidirect: Business expenses you can report if you're self-employed](https://www.nidirect.gov.uk/articles/business-expenses-you-can-report-if-youre-self-employed)
- [GOV.UK: Report business income and expenses to Universal Credit if you are self-employed](https://www.gov.uk/government/publications/universal-credit-for-the-self-employed/how-to-report-your-earnings-from-self-employment)
- [GOV.UK: Business expenses you can report to Universal Credit if you are self-employed](https://www.gov.uk/government/publications/universal-credit-for-the-self-employed/business-expenses-you-can-report-to-universal-credit-if-you-are-self-employed)

Wytyczne i przepisy mogą się zmieniać. Sprawdź je ponownie przed dopuszczeniem produkcyjnym oraz przy każdej aktualizacji wersji polityki.
