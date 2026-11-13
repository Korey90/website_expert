# Website Expert NI - instrukcja przeglądu UK VAT/MTD dla bookkeepera

**Odbiorca:** niezależny brytyjski bookkeeper lub księgowy<br>
**Środowisko:** **Website Expert NI - HMRC Sandbox** oraz UK Finance golden dataset v1<br>
**Status:** wymagany niezależny przegląd; dokument nie stanowi zatwierdzenia HMRC ani rzeczywistej wysyłki VAT<br>
**Kontakt systemowy:** [IMIĘ I NAZWISKO / E-MAIL]<br>
**Kontakt bezpieczeństwa:** [IMIĘ I NAZWISKO / E-MAIL / TELEFON]<br>
**Sprawdzane wydanie/commit:** [IDENTYFIKATOR]<br>
**SHA-256 accountant ZIP:** [UZUPEŁNIĆ PRZED PRZEKAZANIEM]

[English version](uk-vat-mtd-bookkeeper-review-guide.md)

## 1. Cel

Prosimy ocenić, czy sprawdzane wydanie zapewnia spójny i odpowiednio udokumentowany proces standardowej księgowości memoriałowej i VAT w UK oraz czy ścieżka HMRC MTD VAT Sandbox zachowuje zatwierdzony dziewięciopolowy VAT Return i jego ścieżkę audytową.

Zadaniem Recenzenta jest testowanie, kwestionowanie i dokumentowanie. Negatywny albo warunkowy wynik jest dopuszczalny. Nie należy zmieniać wniosku po to, aby oprogramowanie przeszło release gate.

Przegląd ma dwa odrębne obszary:

1. **Logika księgowa:** wersjonowany fikcyjny golden dataset i wygenerowany accountant pack.
2. **Integracja HMRC:** odizolowany wdrożony tenant **Website Expert NI - HMRC Sandbox** i istniejące dowody wysyłki sandbox.

Tenant HMRC zawiera kontrolowaną ścieżkę zerowego VAT Return. Nie musi zawierać pełnego golden dataset księgowości. Każdy obszar należy sprawdzić oddzielnie, a następnie ocenić, czy granica między zablokowanym VAT Return a payloadem HMRC jest wystarczająco udokumentowana.

## 2. Zasady bezpieczeństwa - przeczytaj przed zalogowaniem

1. Używaj wyłącznie własnego konta. Nie udostępniaj danych logowania ani sesji.
2. Przed testowaniem funkcji HMRC włącz MFA z aplikacji uwierzytelniającej.
3. W przełączniku workspace w górnym pasku wybierz **Website Expert NI - HMRC Sandbox**.
4. Nigdy nie wchodź do operacyjnego workspace **Website Expert NI**. Jeżeli jest widoczny lub wybrany, zatrzymaj się i skontaktuj z właścicielem.
5. Nie wprowadzaj rzeczywistych danych klienta, dostawcy, pracownika ani bankowych. Transakcje testowe muszą być fikcyjne.
6. Nie rozłączaj HMRC, nie zmieniaj testowego VRN, konfiguracji fraud headers ani ustawień środowiska.
7. Nie wysyłaj ani nie ponawiaj VAT Return bez pisemnej zgody właściciela wskazującej konkretny okres sandbox i okno testowe.
8. Nigdy nie kopiuj do notatek lub screenshotów access/refresh tokens, kodów MFA, sekretów MFA, pełnych VRN, wartości fraud headers ani danych logowania.
9. Nie uruchamiaj skanerów podatności, testów obciążeniowych ani prób omijania uprawnień. Jest to przegląd księgowy/UAT, a nie test penetracyjny.
10. Niezwłocznie zgłoś i zatrzymaj dany test, jeżeli uzyskasz dostęp do niewłaściwego tenantu, znajdziesz rzeczywisty rekord, niejednoznaczny wynik HMRC lub podejrzenie wycieku danych.

## 3. Materiały wymagane przed rozpoczęciem

- [ ] podpisana umowa B2B lub engagement letter;
- [ ] zaakceptowana oferta Wykonawcy;
- [ ] indywidualne aktywne konto i URL logowania;
- [ ] dostęp wyłącznie do **Website Expert NI - HMRC Sandbox**;
- [ ] identyfikator wydania/commitu;
- [ ] README golden dataset i plik źródłowy lub wygenerowany pakiet;
- [ ] SHA-256 accountant ZIP i wewnętrzny manifest;
- [ ] checklista księgowego i sign-off;
- [ ] zredagowane dowody HMRC Sandbox albo dostęp do odpowiednich rekordów UI;
- [ ] bezpieczne miejsce zapisu ustaleń; oraz
- [ ] wskazane kontakty produktowy i bezpieczeństwa.

Nie rozpoczynaj właściwego przeglądu, jeżeli brakuje identyfikatora wydania, hasha datasetu lub miejsca przekazywania dowodów.

## 4. Stałe dane referencyjne

### Tożsamość golden dataset

- Dataset ID: **website-expert-ni-uk-finance-golden-v1**
- Schema version: **1**
- Oczekiwany SHA-256: **6271a6a9e179c1a8769e7fd957f401fde892b0421ea2a73bdc7a0918fd2672a1**
- Okres: od 1 lipca do 30 września 2026
- Podstawa: księgowość memoriałowa, waluta funkcjonalna GBP, standard VAT accounting
- Kontrahenci i transakcje: fikcyjne

Jeżeli hash datasetu jest inny, zatrzymaj się. Nie zatwierdzaj zmodyfikowanego fixture v1. Zmieniony fixture wymaga nowej wersji i nowego przeglądu.

### Oczekiwane wartości kontrolne

| Kontrola | Wartość oczekiwana | Wynik Recenzenta |
|---|---:|---:|
| VAT Box 1 | £300.00 | |
| VAT Box 2 | £0.00 | |
| VAT Box 3 | £300.00 | |
| VAT Box 4 | £225.00 | |
| VAT Box 5 payable | £75.00 | |
| VAT Box 6 | £2,100 | |
| VAT Box 7 | £1,450 | |
| VAT Box 8 | £0 | |
| VAT Box 9 | £0 | |
| Trial Balance debit / credit | £13,235.00 / £13,235.00 | |
| Zysk netto | £1,165.00 | |
| Balance Sheet | £12,195.00 | |
| Stan końcowy środków pieniężnych | £10,475.00 | |
| Należności / zobowiązania | £995.00 / £730.00 | |

Zgodność sum sama w sobie nie potwierdza prawidłowego ujęcia podatkowego. Sprawdź scenariusz źródłowy i zapis księgowy stojący za każdą wartością kontrolną.

## 5. Kolejność przeglądu

### Etap A - integralność i zakres

- [ ] Zapisz identyfikator wydania/commitu.
- [ ] Zweryfikuj SHA-256 golden dataset.
- [ ] Zweryfikuj accountant ZIP na podstawie manifest.sha256.json.
- [ ] Potwierdź, że każdy dokument źródłowy i kontrahent są fikcyjni.
- [ ] Potwierdź metodę memoriałową, GBP jako walutę funkcjonalną i standard VAT accounting.
- [ ] Zapisz wszystkie wyłączone schematy i założenia faktyczne przed oceną sum.

### Etap B - profil księgowy i plan kont

- [ ] Tożsamość prawna/biznesowa jest jednoznacznie odróżniona od danych demonstracyjnych.
- [ ] Rok finansowy, metoda księgowa i ustawienia VAT są zrozumiałe.
- [ ] Kody, nazwy, typy i normalne salda kont są właściwe.
- [ ] Konta rozrachunkowe VAT są odrębne i możliwe do prześledzenia.
- [ ] Trade debtors, trade creditors, bank, fixed assets, income i expenses mają spójną klasyfikację.
- [ ] Okresy księgowe przechodzą przez statusy open, closing, closed i locked z podaniem przyczyny.
- [ ] Okres locked blokuje księgowania wsteczne i bezpośrednie modyfikacje.

Zmiany projektu planu kont zapisz jako ustalenia. Nie przebudowuj planu kont wewnątrz tenantu testowego.

### Etap C - transakcje źródłowe i cykl księgowania

Prześledź każdą reprezentatywną pozycję od źródła do dziennika, kont księgowych, VAT Detail i raportu.

- [ ] Sprzedaż i zakup ze stawką standardową 20%.
- [ ] Sprzedaż i zakup ze stawką obniżoną 5%.
- [ ] Sprzedaż i zakup zero-rated.
- [ ] Sprzedaż i zakup exempt.
- [ ] Sprzedaż i zakup outside scope wraz z decyzją dotyczącą Boxes 6/7.
- [ ] Zakup usługi transgranicznej reverse charge i równy wpływ na Box 1/Box 4, jeżeli VAT podlega odliczeniu.
- [ ] Zakup wyposażenia kapitałowego w Balance Sheet oraz Boxes 4/7, a nie w kosztach operacyjnych.
- [ ] Faktura EUR z niezmiennym snapshotem kursu GBP i wystarczającym dowodem.
- [ ] Customer credit note i supplier credit note we właściwym okresie VAT.
- [ ] Customer receipt/refund i supplier payment/refund.
- [ ] Opłata bankowa i odsetki.
- [ ] Pełne storno dziennika z niezmiennymi powiązaniami i wynikiem netto zero.
- [ ] Udokumentowana korekta output VAT w wysokości £5 bez sztucznej podstawy w Box 6.
- [ ] Brak niezbilansowanego posted journal i brak cichej edycji/usunięcia zaksięgowanych pozycji.

Dla każdej istotnej próbki zapisz numer źródła oraz dotknięte debit, credit i VAT boxes.

### Etap D - bank i kontrole subledger

- [ ] Zaimportowane pozycje bankowe są deduplikowane.
- [ ] Sugerowane dopasowania różnią się od potwierdzonych uzgodnień.
- [ ] Jednej transakcji bankowej nie można uzgodnić dwukrotnie.
- [ ] Cofnięcie dopasowania lub storno pozostawia czytelny audit trail.
- [ ] Saldo uzgodnienia bankowego i pozycje outstanding są zrozumiałe.
- [ ] Receivables i payables uzgadniają się z kontami kontrolnymi.
- [ ] Ręczne korekty księgi w ageing są właściwie obsłużone albo wyraźnie ujawnione.

### Etap E - raporty finansowe

- [ ] Debity i kredyty Trial Balance są równe.
- [ ] General Ledger pozwala przejść od salda do dziennika i źródła.
- [ ] Profit and Loss daje zysk netto £1,165.00 dla fixture.
- [ ] Balance Sheet bilansuje się na £12,195.00.
- [ ] Management Cash Flow uzgadnia się do końcowego cash £10,475.00 i jest oznaczony jako raport zarządczy, a nie ustawowy IAS 7 statement.
- [ ] Aged receivables/payables uzgadniają się do £995.00 / £730.00.
- [ ] Filtry dat i okresów nie mieszają po cichu okresów księgowych.
- [ ] Waluta, znaki, zaokrąglenia i etykiety eksportu są jednoznaczne.

### Etap F - VAT Detail i dziewięć pól VAT Return

- [ ] VAT Detail uzgadnia się z kontami kontrolnymi VAT.
- [ ] Box 3 równa się Box 1 plus Box 2.
- [ ] Box 5 prawidłowo pokazuje payable albo repayable.
- [ ] Boxes 1-5 zachowują dokładność do pensa.
- [ ] Boxes 6-9 używają dopuszczalnych pełnych funtów do wysyłki.
- [ ] Korekty i storna wpływają na właściwy okres i pola.
- [ ] Ujęcie service reverse charge jest właściwe dla opisanych faktów.
- [ ] Korekty VAT zawierają przyczynę, aktora, datę i metadane ujęcia.
- [ ] Błędy wcześniejszego VAT Return wymagające osobnego zgłoszenia pozostają poza procesem bieżącego Return.
- [ ] Snapshot przeglądu jest niezmienny i powiązany z widocznym SHA-256.
- [ ] Zmienione obliczenie nie może zostać wysłane na podstawie wcześniejszego zatwierdzenia.
- [ ] Zastąpienie snapshotu zachowuje stary rekord audytowy.
- [ ] Accountant ZIP powstaje wyłącznie z locked snapshot i zweryfikowanych dowodów źródłowych.

Nie zatwierdzaj Boxes 8/9 wyłącznie dlatego, że wynoszą zero. Wskaż, czy scenariusze obrotu towarowego NI-UE nie dotyczą pierwszego wydania, czy wymagają osobnego fixture.

### Etap G - tenant HMRC Sandbox

Ścieżka sandbox została już raz zakończona powodzeniem. Zakres podstawowy wykorzystuje istniejące dowody i nie wymaga kolejnej wysyłki.

1. Potwierdź, że wybrany workspace to **Website Expert NI - HMRC Sandbox**.
2. Otwórz **Finance > HMRC Connection**.
3. Potwierdź wyraźne oznaczenie środowiska sandbox i prezentację oczekiwanych OAuth scopes.
4. Sprawdź pokazany wynik walidacji fraud prevention headers bez żądania surowych wartości.
5. Za zgodą właściciela uruchom **Synchronise VAT obligations**, używając świeżego kodu uwierzytelniającego.
6. Potwierdź idempotencję ponownej synchronizacji i brak duplikowania obligations.
7. Sprawdź fulfilled obligation i użyj **View VAT Return** w UI.
8. Potwierdź, że dziewięć zwróconych pól odpowiada wysłanemu zerowemu Return w tym obszarze sandbox.
9. Sprawdź liabilities i payments tylko w zakresie scenariusza i dat podanych przez właściciela.
10. Pobierz istniejący receipt PDF. Musi informować, że jest dowodem odpowiedzi HMRC API wygenerowanym przez system, a nie dokumentem wydanym przez HMRC.
11. Potwierdź, że zawiera dowody wysyłki, hashe snapshot/payload i zweryfikowane eventy audytowe bez sekretów i pełnego VRN.

Nie używaj **Disconnect**, **Set sandbox VRN** ani **Submit to HMRC**, chyba że właściciel osobno i pisemnie zatwierdzi tę konkretną czynność.

### Etap H - wysyłka i obsługa błędów

Sprawdź interfejs i przekazane dowody testowe. Nie wywołuj sztucznych błędów HMRC, chyba że właściciel je zaplanuje.

- [ ] Wysyłka wymaga locked Return i odpowiadającej open obligation.
- [ ] Użytkownik musi zaakceptować final declaration i wpisać świeży kod MFA.
- [ ] Payload pochodzi z niezmiennego snapshotu, a nie z modyfikowalnych bieżących rekordów.
- [ ] Udana odpowiedź zapisuje zweryfikowany receipt i dowód correlation.
- [ ] Jednoznaczny 4xx jest zapisany jako rejected z oczyszczonymi szczegółami.
- [ ] Duplicate submission nie powoduje kolejnego POST.
- [ ] Timeout, błędna odpowiedź lub HMRC 5xx prowadzi do outcome_unknown i blokuje ślepe ponowienie.
- [ ] Reconciliation używa tylko GET i porównuje wszystkie dziewięć pól.
- [ ] Różnica prowadzi do jawnego konfliktu wymagającego wyjaśnienia.
- [ ] Eventy są append-only, a hash chain jest sprawdzany przed wygenerowaniem receipt.

### Etap I - uprawnienia i izolacja tenantów

- [ ] Konto ma dostęp wyłącznie do właściwych rekordów sandbox.
- [ ] Bezpośredni link do innego biznesu nie ujawnia rekordu.
- [ ] Konto nie może zarządzać użytkownikami, rolami, sekretami ani niezwiązanymi ustawieniami.
- [ ] Wysyłka, zatwierdzenie, księgowanie i zamykanie mają odrębne uprawnienia.
- [ ] Konto nie jest ownerem operacyjnego tenantu.

Nie stosuj technik exploitacji. Jeżeli zwykła nawigacja lub zwykły skopiowany link aplikacji ujawnia inny tenant, oznacz problem jako Krytyczny, zachowaj wyłącznie zredagowany dowód i zatrzymaj test.

## 6. Decyzje zawodowe wymagające odpowiedzi

Raport musi odpowiedzieć albo wyraźnie odroczyć:

1. Dla których przedstawionych klas outside-scope supplies lub purchases należy uwzględniać wartości w Boxes 6/7?
2. Czy obrót towarowy NI-UE dotyczy pierwszego wydania? Jeśli tak, jakie fixtures dla Boxes 8/9 są potrzebne?
3. Jakie dowody i zasady zaokrąglania powinny obowiązywać przy przeliczeniu VAT w walucie obcej?
4. Czy przykład service reverse charge jest prawidłowy dla opisanych faktów i założenia odliczalności?
5. Jakie przypadki nieodliczalnego lub częściowo odliczalnego input VAT są potrzebne przed szerszym użyciem?
6. Czy pola zatwierdzenia i dowodów VAT adjustment są wystarczające?
7. Czy przedstawiona korekta wymaga osobnego zgłoszenia do HMRC?
8. Czy klasyfikacje i etykiety Trial Balance, P&L, Balance Sheet, ageing i management Cash Flow są właściwe?
9. Czy każdą istotną kwotę VAT można prześledzić do dowodu i zaksięgowanego dziennika?
10. Które wyłączone schematy lub przypadki muszą blokować produkcję do czasu implementacji?

## 7. Format ustalenia

Użyj jednego rekordu dla każdego ustalenia:

| Pole | Wymagana treść |
|---|---|
| Finding ID | ACC-001, ACC-002 itd. |
| Ważność | Krytyczna / Wysoka / Średnia / Niska |
| Obszar | Ledger / VAT / Report / HMRC / Access / Evidence / UX |
| Wydanie | Identyfikator commitu lub wydania |
| Test reference | Etap i pozycja checklisty |
| Source reference | Fikcyjna faktura, dziennik, Return albo obligation |
| Oczekiwane | Prawidłowy wynik i jego podstawa |
| Rzeczywiste | Wynik pokazany lub obliczony przez system |
| Wpływ finansowy | Pole VAT, konto lub raport i kwota, jeżeli jest znana |
| Dowód | Zredagowany numer dowodu; nigdy sekret |
| Rekomendacja | Wymagana korekta lub decyzja |
| Retest | Nie testowano / Passed / Failed / Accepted risk |

### Klasyfikacja ważności

- **Krytyczna:** błędna kwota wysyłki VAT, nieuprawniony HMRC POST, ujawnienie między tenantami, naruszony podwójny zapis, modyfikowalny locked Return lub nieważny dowód audytowy.
- **Wysoka:** istotny błąd VAT box, konta kontrolnego, raportu lub okresu, który może wpłynąć na wysyłkę albo decyzję z przeglądu.
- **Średnia:** niepełne prześledzenie, niejasne ujęcie, brak dowodu zatwierdzenia albo nieistotny kwotowo błąd procesu.
- **Niska:** terminologia, układ lub użyteczność bez wpływu na wynik księgowy.

## 8. Wymagane rezultaty

- [ ] wypełniona [accountant-review-checklist.md](uk-finance-golden-dataset-v1/accountant-review-checklist.md);
- [ ] rejestr ustaleń według punktu 7;
- [ ] wypełniony [sign-off.md](uk-finance-golden-dataset-v1/sign-off.md);
- [ ] jawne założenia i wyłączenia;
- [ ] scenariusze potrzebne przed produkcją lub szerszym użyciem;
- [ ] zapisane hash accountant ZIP i identyfikator wydania; oraz
- [ ] jeżeli zawiera to zaakceptowana oferta, notatka z retestu oznaczająca każde ustalenie jako Passed, Failed albo Accepted risk.

Sign-off musi wskazywać dokładnie jeden wynik:

- **Zaakceptowano bez zmian**;
- **Zaakceptowano z podanymi warunkami**; albo
- **Wymagane poprawki; nie zaakceptowano**.

Nie używaj sformułowań sugerujących statutory audit assurance, certyfikację HMRC ani prawidłowość poza sprawdzonym fixture i zakresem.

## 9. Zakończenie zlecenia

1. Przekaż końcowe rezultaty bezpiecznym uzgodnionym kanałem.
2. Usuń lokalne pakiety i screenshoty, chyba że prawo wymaga ich przechowania.
3. Potwierdź usunięcie albo wskaż obowiązkową podstawę i okres przechowania.
4. Wyloguj wszystkie sesje i poinformuj właściciela, że konto może zostać cofnięte.
5. Nie zachowuj i nie wykorzystuj ponownie dostępu sandbox.

## 10. Dokumenty referencyjne

- [README golden dataset](uk-finance-golden-dataset-v1/README.md)
- [Checklista przeglądu księgowego](uk-finance-golden-dataset-v1/accountant-review-checklist.md)
- [Sign-off księgowego](uk-finance-golden-dataset-v1/sign-off.md)
- [Dokumentacja wdrożenia HMRC Sandbox](../integrations/hmrc-mtd-vat-sandbox.md)
- [HMRC production readiness gate](../integrations/hmrc-production-readiness.md)
- [Polska umowa B2B](../legal/uk-vat-mtd-accounting-review-b2b-agreement-pl.md)

