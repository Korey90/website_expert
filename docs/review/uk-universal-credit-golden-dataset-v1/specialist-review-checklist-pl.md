# Golden dataset Universal Credit v1 - checklista specjalisty

- Status przeglądu: oczekuje
- Dataset: `uk-universal-credit-golden-v1`
- Niezmienny SHA-256 fixture: `ef92a67e1c458f001334f4659f9dd87bc54986cd2660c3fd757dd3caa6f0436a`
- Assessment period: 23 listopada 2026 do 22 grudnia 2026
- Podmiot: sole trader w Irlandii Północnej, niezarejestrowany do VAT

## Cel przeglądu

Proszę ocenić, czy klasyfikacje, obliczenia, nazewnictwo i prezentacja dowodów nadają się dla osoby samozatrudnionej wykonującej miesięczne zadanie Universal Credit w Irlandii Północnej.

To nie jest przegląd Self Assessment, deklaracji VAT ani ustawowych sprawozdań finansowych.

## Oczekiwany wynik kandydata

| Miara | Oczekiwana wartość |
|---|---:|
| Przychód z działalności | GBP 1 290,00 |
| Dopuszczalne koszty | GBP 199,00 |
| Dochód netto | GBP 1 091,00 |
| Włączone pozycje kasowe | 5 |
| Jawnie wykluczone pozycje kasowe | 1 |
| Koszty uproszczone | 2 |

Skład kosztów:

- internet i telefon o mieszanym użyciu: zapłacono GBP 120,00, użytek firmowy 50 procent, raportowane GBP 60,00;
- kwalifikowana płatność NI: GBP 35,00;
- odsetki od pożyczki firmowej: zapłacono GBP 50,00, limit GBP 41,00;
- przebieg samochodu: 100 mil po 45 pensów, GBP 45,00;
- praca z domu: 60 godzin, GBP 18,00;
- zakup osobisty: GBP 75,00, jawnie wykluczony.

## Pliki do sprawdzenia

Paczka do przeglądu: `docs/demo/uk-universal-credit-golden-v1/universal-credit-golden-v1.zip`

Metadane integralności: `docs/demo/uk-universal-credit-golden-v1/artifact.sha256.json`

W wygenerowanym ZIP sprawdź:

- `01-summary/universal-credit-summary.pdf`;
- `01-summary/review-and-declaration.pdf`;
- `02-details/income-detail.csv`;
- `02-details/expense-detail.csv`;
- `02-details/excluded-and-private.csv`;
- `02-details/bank-and-cash-reconciliation.xlsx`;
- `02-details/missing-evidence-and-warnings.csv`;
- `02-details/calculation-policy.pdf`;
- `manifest.sha256.json`.

## Pytania kontrolne

Każdą pozycję oznacz jako `Zaliczone`, `Niezaliczone` albo `Wymaga wyjaśnienia`.

| Obszar | Pytanie | Decyzja | Uwagi |
|---|---|---|---|
| Okres | Czy dokładne, włączne daty assessment period są obsłużone poprawnie? |  |  |
| Metoda kasowa | Czy wpływy i płatności przypisano według faktycznej daty przepływu, a nie daty faktury? |  |  |
| Okres zerowy | Czy workflow pozwala poprawnie zgłosić zerowy przychód i zerowe koszty? |  |  |
| Przychody | Czy płatność za usługę i napiwki mają poprawne kategorie? |  |  |
| Użytek mieszany | Czy 50 procent użytku firmowego jest czytelne i zastosowane wyłącznie do kosztu? |  |  |
| Podatek i NI | Czy płatność NI jest poprawnie opisana i skategoryzowana dla tego przypadku? |  |  |
| Odsetki | Czy miesięczny limit GBP 41 jest prawidłowo zastosowany i ujawniony? |  |  |
| Przebieg | Czy 100 mil służbowych po 45 pensów jest właściwe dla scenariusza? |  |  |
| Praca z domu | Czy kwota dla 60 godzin jest poprawna i oddzielona od kosztów rzeczywistych? |  |  |
| Wykluczenia | Czy osobisty zakup GBP 75 pozostaje widoczny, ale nie wchodzi do sum? |  |  |
| Dowody | Czy brak syntetycznych dokumentów jest ostrzeżeniem, a nie pozornym potwierdzeniem? |  |  |
| VAT | Czy scenariusz podmiotu niezarejestrowanego do VAT jest jednoznaczny? |  |  |
| Sumy | Czy PDF, XLSX i CSV zgadzają się co do pensa? |  |  |
| Workflow | Czy ostrzeżenia, review, lock, supersede i potwierdzenie ręcznego zgłoszenia są zrozumiałe? |  |  |
| Zakres | Czy jest jasne, że system nie wysyła do UC i nie oblicza świadczenia? |  |  |
| Terminologia | Czy polskie terminy są zrozumiałe dla właściciela i bookkeepera? |  |  |

## Wymagane zatwierdzenie

| Pole | Odpowiedź reviewera |
|---|---|
| Imię i nazwisko reviewera | |
| Rola zawodowa lub właściwe doświadczenie UC | |
| Organizacja, jeśli dotyczy | |
| Data przeglądu | |
| Decyzja: `Zaakceptowano`, `Zaakceptowano z wymienionymi zmianami` albo `Odrzucono` | |
| Podpis lub identyfikowalna referencja zatwierdzenia | |

Istotne uwagi:

1.
2.
3.

## Zasada wersjonowania

Po zatwierdzeniu nie edytuj fixture v1. Każda zmiana dat, przypadków, oczekiwanych sum lub założeń polityki wymaga nowej wersji, nowego SHA-256 i nowej decyzji specjalisty.
