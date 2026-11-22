# UKFIN-101R1 / UKFIN-307R1 - Accounting profile w panelu i owner-funded expenses

**Status:** Zrealizowany lokalnie, gotowy do wdrożenia
**Data planu:** 2026-11-22
**Data realizacji:** 2026-11-22
**Zakres:** profil księgowy w Filament oraz płatności kosztów finansowane prywatnie przez sole tradera

## 1. Potwierdzony problem

### 1.1 Accounting profile istnieje, ale nie w panelu administracyjnym

W aplikacji istnieją:

- trasa `GET /business/accounting`;
- `AccountingProfileController`;
- strona Inertia `resources/js/Pages/Business/AccountingProfile.tsx`;
- zapis przez `UpdateAccountingProfileAction`.

Odnośnik do tej strony znajduje się wyłącznie w `PortalLayout.jsx` i jest uzależniony od `portal_capabilities.can_manage_finance`. Moduł finansowy używany przez właściciela działa natomiast w Filament pod `/admin`. W Filament nie istnieje `AccountingProfilePage`, wpis w `FilamentPermissionRegistry` ani pozycja w grupie `Finance`.

Wynik: funkcja istnieje technicznie, ale jest niewidoczna w panelu, w którym faktycznie prowadzona jest księgowość.

### 1.2 Profil Website Expert NI nie ma ustawionego typu podmiotu

Odczyt produkcyjny potwierdził:

- `legal_name = Website Expert NI`;
- `entity_type = null`;
- `accounting_basis = accrual`;
- `vat_registered = false`.

Użytkownik potwierdził, że Website Expert NI działa obecnie jako `sole_trader`. Wymagane jest udostępnienie formularza i jawne ustawienie tej wartości przez interfejs, bez ręcznej edycji bazy.

### 1.3 Każda ręczna płatność kosztu bez rachunku bankowego zmniejsza konto 1100

Modal `Record payment` zapisuje metodę płatności, ale nie pyta o źródło środków ani o rachunek bankowy. `PaymentJournalPayloadFactory` stosuje konto `1100 Bank and cash`, gdy `bank_account_id` jest pusty.

W konsekwencji płatność wykonana z prywatnych środków właściciela jest obecnie księgowana tak, jakby wyszła z firmowej gotówki:

```text
Dr 2100 Trade creditors
Cr 1100 Bank and cash
```

To zniekształca saldo środków pieniężnych i Management Cash Flow.

### 1.4 Plan kont nie ma konta kapitału wniesionego przez sole tradera

Domyślny plan kont zawiera ogólną sekcję `3000 Equity`, `3100 Share capital` i `3200 Retained earnings`, ale nie ma konta przeznaczonego na prywatne środki wniesione do działalności przez sole tradera.

HMRC wymaga, aby sole trader zachowywał informacje o kwotach zainwestowanych w firmę i środkach pochodzących ze źródeł prywatnych. System musi więc rozróżnić koszt firmowy od źródła, z którego został opłacony.

## 2. Cel

Po wdrożeniu użytkownik będzie mógł:

1. otworzyć `Accounting profile` bezpośrednio w grupie `Finance` panelu `/admin`;
2. ustawić `Entity type = Sole trader` przez bezpieczny formularz;
3. podczas rejestrowania płatności kosztu wybrać źródło `Owner's personal funds`;
4. zachować osobno metodę płatności, na przykład `Cash`, `Card` albo `Bank transfer`;
5. zaksięgować rozrachunek bez fikcyjnego ruchu na firmowym banku lub kasie;
6. uzyskać poprawny Trial Balance, Balance Sheet, Aged Payables i Profit and Loss;
7. wykluczyć owner-funded payment z bank matching i reconciliation.

## 3. Decyzja księgowa i model dekretacji

### 3.1 Źródło finansowania nie jest metodą płatności

Pole `method` pozostaje odpowiedzią na pytanie, jak zapłacono:

- cash;
- card;
- bank transfer;
- direct debit;
- cheque;
- other.

Nowe pole `settlement_source` odpowiada na pytanie, skąd pochodziły środki:

- `bank_account` - konkretny firmowy rachunek bankowy;
- `cash_account` - firmowa kasa lub gotówka;
- `owner_funds` - prywatne środki sole tradera.

Nie należy dodawać `owner_funds` jako kolejnej wartości `method`, ponieważ prywatna karta, gotówka i przelew właściciela nadal są różnymi metodami płatności, ale mają to samo źródło kapitałowe.

### 3.2 Nowe konto systemowe

Do domyślnego planu kont zostanie dodane:

```text
3300 Owner's capital introduced
Typ: equity
Podtyp: owner_capital
Normal balance: credit
Parent: 3000 Equity
Postable: yes
System: yes
```

Tłumaczenia:

- EN: `Owner's capital introduced`;
- PL: `Kapitał wniesiony przez właściciela`;
- PT: `Capital introduzido pelo proprietário`.

Konto `Owner's drawings` nie należy do bieżącego zakresu. Zostanie zaplanowane osobno razem z obsługą prywatnych wypłat z działalności.

### 3.3 Księgowanie kosztu zapłaconego prywatnie

Wystawienie lub zaksięgowanie kosztu pozostaje bez zmian:

```text
Dr konto kosztowe, na przykład 6700 Utilities and communications
Cr 2100 Trade creditors
```

Płatność przez `owner_funds`:

```text
Dr 2100 Trade creditors
Cr 3300 Owner's capital introduced
```

Efekt:

- koszt występuje w Profit and Loss;
- zobowiązanie wobec dostawcy zostaje rozliczone;
- kapitał właściciela rośnie w Balance Sheet;
- konto 1100 nie jest zmieniane;
- Management Cash Flow nie pokazuje fikcyjnego wydatku z firmowej gotówki;
- nie powstaje kandydat do uzgodnienia z wyciągiem bankowym.

### 3.4 Ograniczenie podmiotowe

`owner_funds` będzie dostępne tylko wtedy, gdy:

- istnieje aktywny tenant;
- profil księgowy ma `entity_type = sole_trader`;
- użytkownik ma `post_finance`;
- wydatek jest zaksięgowany i ma saldo do zapłaty;
- konto 3300 jest aktywne i postowalne.

Dla `limited_company` wymagane byłoby konto director's loan, a dla `partnership` osobne konta partnerów. Te przypadki nie mogą automatycznie używać konta sole tradera.

## 4. Plan zmian w bazie danych

### 4.1 Rozszerzenie `expense_payments`

Dodać kolumnę:

```text
settlement_source VARCHAR(30) NOT NULL
```

Dozwolone wartości aplikacyjne:

- `bank_account`;
- `cash_account`;
- `owner_funds`.

Backfill istniejących rekordów:

- `bank_account_id IS NOT NULL` -> `bank_account`;
- `bank_account_id IS NULL` -> `cash_account`.

Backfill nie zmienia istniejących journali. Klasyfikuje wyłącznie faktyczne historyczne zachowanie, w którym brak rachunku powodował użycie konta 1100.

Dodać indeks:

```text
(business_id, settlement_source, paid_at)
```

### 4.2 Inicjalizacja konta 3300

Rozszerzyć `DefaultUkChartAccounts` i `InitializeAccountingSetupAction` o konto 3300.

Migracja danych ma idempotentnie dodać konto 3300 do wszystkich istniejących tenantów bez modyfikowania nazw, statusów ani sald istniejących kont. Rodzic musi wskazywać konto 3000 tego samego tenanta.

### 4.3 Niezmienność danych historycznych

- posted payment pozostaje immutable;
- `settlement_source` po postingu nie może zostać zmienione;
- istniejące journal entries nie są przepisywane;
- zmiana entity type nie może reinterpretować istniejących owner-funded payments;
- próba zmiany profilu z `sole_trader` na inny typ przy istniejących owner-funded payments wymaga blokady i komunikatu o konieczności kontrolowanej migracji księgowej.

## 5. Plan backendu

### 5.1 Model i stałe

W `ExpensePayment` dodać stałe:

```text
SOURCE_BANK_ACCOUNT
SOURCE_CASH_ACCOUNT
SOURCE_OWNER_FUNDS
```

Dodać `settlement_source` do `fillable` oraz wspólną metodę zwracającą dozwolone źródła.

### 5.2 Walidacja płatności

Rozszerzyć `RecordExpensePaymentAction`:

- `bank_account` wymaga aktywnego `bank_account_id` tego samego tenanta;
- `cash_account` wymaga pustego `bank_account_id`;
- `owner_funds` wymaga pustego `bank_account_id` i profilu `sole_trader`;
- brak profilu lub `entity_type = null` blokuje owner funds i podaje link do Accounting profile;
- idempotentne ponowienie musi porównywać kwotę, walutę, kurs, datę, metodę, settlement source i bank account;
- zmiana źródła przy tej samej referencji musi zostać odrzucona jako payload drift.

### 5.3 Budowanie journala płatności

Rozszerzyć `PaymentJournalPayloadFactory::forExpensePayment()`:

- `bank_account` -> ledger przypisany do wybranego `BankAccount`;
- `cash_account` -> konto 1100;
- `owner_funds` -> konto 3300;
- linia rozrachunkowa nadal używa konta 2100;
- istniejąca obsługa FX pozostaje aktywna;
- snapshot źródła zawiera `settlement_source`, `method`, ID rachunku, jeżeli występuje, oraz ID i kod użytego konta ledgerowego;
- memo nie może nazywać owner-funded posting `Bank payment`.

### 5.4 Integracja z importem i reconciliation

Zmienić wszystkie miejsca tworzące `ExpensePayment`:

- płatność utworzona z transakcji bankowej zawsze zapisuje `settlement_source = bank_account`;
- płatność ręczna wymaga jawnego wyboru;
- bank matching pomija `cash_account` i `owner_funds`;
- `ReconcileBankTransactionAction` odrzuca alokację do płatności, która nie ma `settlement_source = bank_account`;
- capacity i candidate scoring nie mogą proponować owner-funded payments;
- audit command zgłasza niespójność source, bank account i journala.

### 5.5 Zmiana typu podmiotu

Rozszerzyć `UpdateAccountingProfileAction`:

- pozwolić ustawić `sole_trader` dla profilu bez typu;
- przy zmianie z `sole_trader` na inny typ sprawdzić istniejące owner-funded payments;
- przy danych historycznych zablokować prostą zmianę i wymagać osobnego procesu migracji;
- zachować obecną zasadę nieujawniania zaszyfrowanych wartości UTR/PAYE.

## 6. Accounting profile w panelu `/admin`

### 6.1 Nowa strona Filament

Dodać `App\Filament\Pages\AccountingProfilePage`:

- grupa nawigacji: `Finance`;
- slug: `accounting-profile`;
- ikona: ustawienia finansowe;
- sortowanie przed Accounting periods;
- odczyt i inicjalizacja przez `InitializeAccountingSetupAction`;
- zapis wyłącznie przez `UpdateAccountingProfileAction`;
- etykieta UTR dostosowana do entity type, bez mylącego określenia `Company UTR` dla sole tradera;
- brak logiki biznesowej w klasie strony.

### 6.2 Uprawnienia

- `view_finance` pozwala otworzyć stronę i zobaczyć bezpieczne podsumowanie;
- zapis wymaga `manage_finance` oraz aktywnej roli biznesowej `owner` albo `admin`;
- wartości `company_utr`, `paye_reference` i `accounts_office_reference` nie są odsyłane do formularza po odszyfrowaniu;
- interfejs pokazuje tylko informację, czy dana wartość jest zapisana;
- cross-tenant lookup jest zabroniony.

### 6.3 Istniejąca strona Inertia

Trasa `/business/accounting` pozostaje na czas kompatybilności. Dokumentacja i panel administracyjny będą kierować do `/admin/accounting-profile`.

Po potwierdzeniu, że portal nie ma niezależnych użytkowników wymagających edycji profilu, można później przekierować starą trasę do Filament i usunąć duplikat UI.

## 7. Zmiany w formularzu `Record payment`

Dodać pole `Paid from` przed `Payment method`:

- `Business bank account`;
- `Business cash`;
- `Owner's personal funds`.

Zachowanie dynamiczne:

- wybór banku pokazuje wymagany select aktywnych `BankAccount`;
- wybór cash nie pokazuje rachunku;
- owner funds jest widoczne wyłącznie dla `sole_trader`;
- brak skonfigurowanego entity type pokazuje komunikat i link do profilu;
- podsumowanie przed zatwierdzeniem pokazuje docelową dekretację `2100 -> 3300`;
- szczegóły płatności pokazują osobno `Paid from` i `Payment method`.

Nowe teksty wymagają zgodnych tłumaczeń PL, EN i PT.

## 8. Raporty i skutki księgowe

### 8.1 Raporty wymagające testu

- Profit and Loss - koszt pozostaje ujęty tylko raz;
- Balance Sheet - konto 3300 zwiększa equity;
- Trial Balance - debit równa się credit;
- Aged Payables - płatność zeruje lub zmniejsza zobowiązanie;
- Management Cash Flow - owner-funded payment nie tworzy ruchu cash;
- General Ledger - pokazuje źródło i prawidłowe konto;
- Expense Report - brak zmiany kwoty kosztu;
- Accountant Pack - journal i snapshot pozostają audytowalne.

### 8.2 Bank reconciliation

Owner-funded payment nie może:

- pojawić się jako kandydat bankowy;
- zostać przypisany do importowanej transakcji;
- zwiększyć kwoty uzgodnionej rachunku;
- wpłynąć na saldo bankowe lub raport reconciliation.

## 9. Testy

### 9.1 Accounting profile

- właściciel widzi pozycję w Finance i otwiera stronę `/admin/accounting-profile`;
- użytkownik bez `view_finance` nie widzi strony i otrzymuje 403 przy bezpośrednim URL;
- `view_finance` bez prawa zarządzania widzi read-only;
- tylko owner/admin z `manage_finance` zapisuje profil;
- zapis `sole_trader` czyści pola dotyczące limited company zgodnie z obecną akcją;
- zaszyfrowane identyfikatory nie wracają w stanie formularza;
- tenant A nie odczytuje i nie modyfikuje profilu tenant B.

### 9.2 Owner-funded payment

- pełna płatność GBP tworzy dokładnie `Dr 2100 / Cr 3300`;
- konto 1100 nie otrzymuje żadnej linii;
- partial payment aktualizuje `amount_paid`, `amount_due` i status;
- druga część płatności domyka koszt do `paid`;
- owner-funded payment w walucie obcej zachowuje carrying amount, settlement amount i FX line;
- identyczne ponowienie jest idempotentne;
- zmiana settlement source przy tej samej referencji jest odrzucona;
- brak konta 3300 powoduje pełny rollback;
- zamknięty okres powoduje pełny rollback;
- limited company, partnership i profil bez typu są odrzucane;
- użytkownik bez `post_finance` jest odrzucany;
- cross-tenant bank account i ledger account są odrzucane.

### 9.3 Bank i raporty

- owner funds nie występuje w bank matching;
- ręczna próba reconciliation jest odrzucona;
- cash account nadal księguje na 1100;
- bank account księguje na ledger wybranego rachunku;
- P&L, Balance Sheet, Trial Balance, Aged Payables i Cash Flow mają oczekiwane wartości;
- migracja poprawnie klasyfikuje istniejące payments i nie zmienia journal hashów.

## 10. Kolejność implementacji

1. [x] Dodać testy regresyjne potwierdzające obecną niewidoczność profilu i błędny fallback do 1100.
2. [x] Dodać konto 3300 do szablonu planu kont i migrację istniejących tenantów.
3. [x] Dodać `settlement_source` do `expense_payments` oraz bezpieczny backfill.
4. [x] Rozszerzyć model i `RecordExpensePaymentAction` o walidację źródła.
5. [x] Rozszerzyć `PaymentJournalPayloadFactory` o owner funds i poprawne snapshoty.
6. [x] Zabezpieczyć matching, reconciliation, capacity oraz bank-created payment.
7. [x] Dodać `AccountingProfilePage` do Filament i rejestru permissions.
8. [x] Rozszerzyć modal płatności o `Paid from`, rachunek bankowy i podgląd dekretacji.
9. [x] Dodać PL, EN i PT oraz poprawić instrukcję użytkownika.
10. [x] Dodać testy raportowe, tenantowe, RBAC, FX, idempotency i migration backfill.
11. [x] Uruchomić Pint, testy targeted, pełny Finance, audyt tenantów i kontrolę tłumaczeń.
12. [ ] Wdrożyć migrację i kod, następnie przez UI ustawić Website Expert NI jako `sole_trader`.
13. [ ] Wykonać produkcyjny smoke test na kontrolnym koszcie bez użycia prawdziwego bank transaction.

## 11. Wdrożenie produkcyjne

1. wykonać kopię bazy i kodu;
2. wdrożyć kod;
3. uruchomić migrację;
4. potwierdzić jeden rekord konta 3300 dla każdego tenanta;
5. potwierdzić backfill `settlement_source` bez rekordów pustych lub niespójnych;
6. odbudować cache i zrestartować worker;
7. otworzyć `/admin/accounting-profile`;
8. ustawić Website Expert NI jako `sole_trader`;
9. utworzyć kontrolny draft kosztu i sprawdzić preview owner funds;
10. po akceptacji zaksięgować testową płatność i potwierdzić brak linii na 1100;
11. sprawdzić Trial Balance, Balance Sheet, P&L, Aged Payables i Cash Flow;
12. uruchomić tenant audit i HTTP smoke tests.

## 12. Kryteria akceptacji

- Accounting profile jest widoczny w `/admin` w grupie Finance.
- Website Expert NI można ustawić jako sole trader bez ręcznej edycji DB.
- Formularz płatności wymaga jawnego źródła środków.
- Owner funds i metoda płatności są przechowywane oddzielnie.
- Płatność owner funds księguje `Dr 2100 / Cr 3300`.
- Konto 1100 i wszystkie rachunki bankowe pozostają bez zmian.
- Koszt otrzymuje poprawny status `partially_paid` albo `paid`.
- Owner-funded payment nie może zostać uzgodniony z bankiem.
- Wszystkie journal entries pozostają zbilansowane, immutable i tenantowe.
- Raporty finansowe są ze sobą zgodne co do pensa.
- Interfejs i komunikaty mają zgodne tłumaczenia PL, EN i PT.
- Pełny pakiet Finance nie ma nowych regresji.

## 13. Poza zakresem

- director's loan account dla limited company;
- osobne rachunki kapitałowe partnerów w partnership;
- owner drawings i prywatne wypłaty z działalności;
- automatyczna ocena, czy wydatek jest podatkowo allowable;
- nowy kod VAT dla kosztów bez prawa do odliczenia;
- retroaktywna zmiana zaksięgowanych historycznych payment journals;
- automatyczne dzielenie wydatku mieszanego na część biznesową i prywatną;
- refund zwracany bezpośrednio do prywatnych środków właściciela.

Te przypadki wymagają oddzielnych decyzji księgowych i nie mogą być domyślnie utożsamiane z owner funds sole tradera.

## 14. Źródła i założenia

Plan opiera się na aktualnym kodzie aplikacji oraz oficjalnych zasadach HMRC dotyczących prowadzenia ewidencji przez self-employed. HMRC wymaga przechowywania zapisów wszystkich kosztów firmowych, informacji o kwotach zainwestowanych w działalność oraz danych pozwalających odróżnić transakcje biznesowe od prywatnych.

- [GOV.UK: Business records if you're self-employed - What records to keep](https://www.gov.uk/self-employed-records/what-records-to-keep)
- [GOV.UK: Expenses if you're self-employed - How to claim expenses](https://www.gov.uk/expenses-if-youre-self-employed/how-to-claim)

Implementacja zapewnia poprawny mechanizm ewidencyjny. Nie zastępuje oceny księgowego dotyczącej podatkowej kwalifikacji konkretnego wydatku.

## 15. Wynik implementacji lokalnej

- Dodano natywną stronę `/admin/accounting-profile` w grupie Finance. Odczyt wymaga `view_finance`, a zapis aktywnego członkostwa owner/admin oraz `manage_finance`.
- Pola UTR i PAYE nie są zwracane do formularza. Interfejs pokazuje wyłącznie informację, czy sekret jest już zapisany.
- Dodano jawne źródła płatności kosztu: rachunek bankowy, firmowa gotówka i prywatne środki właściciela.
- Owner funds są dostępne tylko dla profilu `sole_trader` i księgowane jako `Dr 2100 Trade creditors / Cr 3300 Owner's capital introduced`.
- Płatności owner-funded nie tworzą ruchu na koncie 1100 ani na rachunku bankowym i są wykluczone z bank matching oraz reconciliation.
- Raporty P&L, Balance Sheet, Trial Balance, Aged Payables i Management Cash Flow zostały objęte testem kontrolnym.
- Migracja `2026_11_22_000036` została wykonana lokalnie. Backfill jest kompletny, każdy lokalny tenant z planem kont otrzymał konto 3300, a audyt tenantów zwraca `invalid_count = 0`.
- Pełna regresja Finance: 385 testów zaliczonych, 1 pominięty zgodnie z ograniczeniem SQLite, 2653 asercje.
- Wdrożenie produkcyjne, ustawienie Website Expert NI jako `sole_trader` oraz produkcyjny smoke test pozostają do wykonania w krokach 12 i 13.
