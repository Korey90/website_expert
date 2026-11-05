# 2026-11-05 - Scoring dopasowania transakcji bankowych

**Status:** Zakończone sukcesem  
**Data realizacji:** 2026-11-05  
**Czas trwania:** jeden etap implementacyjny

## Cel zadania

Zrealizować `UKFIN-404`: deterministyczny i tenantowy silnik proponujący kandydatów uzgodnienia transakcji bankowej na podstawie kwoty, daty, referencji oraz kontrahenta, bez przedwczesnego zapisywania uzgodnienia.

## Zakres wykonanych prac

- Dodano typowany `BankTransactionMatchCandidate` z identyfikatorem źródła, kierunkiem, kwotami oryginalną i porównawczą, różnicami, scoringiem i powodami.
- Dodano scoring 0–100: kwota 50, data 20, referencja 20 i kontrahent 10.
- Dodano klasy confidence: `exact`, `high`, `medium`, `low`.
- Obsłużono wpłaty klientów, zwroty klientom, płatności dostawców i zwroty od dostawców.
- Kandydaci przeciwnego kierunku są odrzucani przed scoringiem.
- Kwoty są porównywane w pensach; dla rachunku GBP i dokumentu walutowego używany jest wyłącznie zapisany snapshot `amount_gbp`.
- Dodano normalizację tekstu, dopasowanie exact/contains/token overlap i deterministyczne sortowanie.
- Okno dat 30 dni, próg 40 oraz maksymalnie 50 kandydatów są konfigurowalne.
- Dodano akcję wymagającą aktywnego membership, `view_finance` i statusu transakcji `unmatched`.
- Świadomie nie zapisano match records — exact/partial/split/many-to-many pozostaje zakresem `UKFIN-405`.

## Użyte agenty i skille

- @BackendEngineer
- @TestingEngineer
- Skill: delta-analysis
- Skill: laravel-action
- Skill: test-generation
- Skill: multi-language-check
- Skill: task-completion-report

## Zmodyfikowane / utworzone pliki

- `app/Actions/Finance/FindBankTransactionMatchesAction.php`
- `app/Data/Finance/BankTransactionMatchCandidate.php`
- `app/Services/Finance/BankTransactionMatchingService.php`
- `config/finance.php`
- `tests/Feature/Finance/BankTransactionMatchingTest.php`
- `docs/plans/uk-finance-tax-module-plan.md`
- `docs/plans/uk-finance-tax-module-plan.html`
- `.github/live-docs/current-task.md`
- `.github/live-docs/current-sprint.md`
- `.github/live-docs/status-dashboard.md`

## Walidacja końcowa

- ✅ Pint na zmienionych plikach.
- ✅ `git diff --check`.
- ✅ UKFIN-404: 7 passed / 46 assertions.
- ✅ Finance regression: 179 passed / 1 skipped / 984 assertions.
- ✅ Full regression: 688 passed / 11 skipped / 9 znanych baseline failures / 2428 assertions.
- ✅ Multi-tenancy: jawny `business_id`, aktywny membership, permission i test rekordu obcego tenanta.
- ✅ Multi-language: brak nowego UI; istniejące zestawy kluczy PL/EN/PT pozostają zgodne.

## Uwagi i rekomendacje

- Wynik jest sugestią, nie automatycznym uzgodnieniem. Nawet confidence `exact` wymaga kontrolowanego zapisu przez workflow UKFIN-405.
- Próg 40 celowo utrzymuje częściowe płatności tego samego dnia bez zgodnej referencji; słabe kandydatury nadal są odfiltrowywane.
- Silnik nie pobiera historycznych kursów i nie przelicza ponownie kwot księgowych.
- Błędy pełnej regresji są identyczne z baseline i nie dotyczą finansów.

## Next steps

- `UKFIN-405`: `bank_transaction_matches`, kontrola sum alokacji i UI exact/partial/split/many-to-many.
- Następnie `UKFIN-406`: tworzenie wydatku lub płatności z nieuzgodnionej transakcji.

**Raport wygenerowany automatycznie przez WebsiteExpert**
