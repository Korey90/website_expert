# 2026-11-20 - UKFIN-402R2 laczenie transakcji z istniejacym kosztem

**Status:** Zakończone sukcesem
**Data realizacji:** 2026-11-20
**Czas trwania:** poniżej 1 dnia

## Cel zadania

Umożliwić połączenie wychodzącej transakcji z zaimportowanego wyciągu bankowego z kosztem utworzonym wcześniej w `/admin/expenses`, bez tworzenia drugiego dokumentu kosztowego.

## Zakres wykonanych prac

- Dodano domenową akcję `LinkExistingExpenseToBankTransactionAction`.
- Dodano przycisk `Połącz z istniejącym kosztem` na widoku wychodzącej transakcji bankowej.
- Lista wyboru pokazuje koszty tego samego tenanta i waluty z kwotą do zapłaty nie mniejszą niż pozostała kwota transakcji.
- Obsłużono koszty w statusie szkic, zatwierdzony, zaksięgowany i częściowo opłacony.
- Szkic wymaga `approve_finance`, a cały workflow wymaga `manage_finance` i `post_finance`.
- Akcja zatwierdza i księguje koszt, jeśli jest to wymagane.
- Tworzona płatność używa rachunku bankowego źródłowej transakcji.
- Płatność jest księgowana, a następnie automatycznie uzgadniana z transakcją bankową.
- Cały proces działa w jednej transakcji DB i nie pozostawia częściowych danych po błędzie.
- Zablokowano połączenia z kosztem innego tenanta, innej waluty, nieobsługiwanego statusu albo zbyt małego salda do zapłaty.
- Dodano lokalizacje EN, PL i PT.
- Dodano testy braku duplikatu, częściowej płatności, księgowania na właściwy rachunek, RBAC, tenant isolation, waluty oraz rollbacku zamkniętego okresu.

## Użyte agenty i skille

- Agent główny Codex
- Skill: Task Completion Report
- Nie używano agentów pomocniczych

## Zmodyfikowane / utworzone pliki

- `app/Actions/Finance/LinkExistingExpenseToBankTransactionAction.php`
- `app/Filament/Resources/BankTransactionResource/Pages/ViewBankTransaction.php`
- `lang/en/finance.php`
- `lang/pl/finance.php`
- `lang/pt/finance.php`
- `tests/Feature/Finance/BankTransactionRecordCreationTest.php`
- `.github/live-docs/current-task.md`
- `.github/live-docs/current-sprint.md`
- `.github/live-docs/status-dashboard.md`

## Walidacja końcowa

- PHP Coding Standard: Laravel Pint przechodzi dla zmienionych plików.
- PHP syntax check przechodzi dla akcji, strony Filament i testu.
- Tłumaczenia EN, PL i PT mają po 1285 zgodnych kluczy.
- Test scenariusza: 10 testów, 69 asercji.
- Powiązana bank suite: 56 testów, 392 asercje.
- Pełny moduł Finance: 361 testów zaliczonych i 1 test pominięty z powodu braku blokad wierszy w SQLite.
- Multi-tenancy, uprawnienia, waluta, właściwy rachunek bankowy i atomowy rollback są objęte testami.
- `git diff --check` przechodzi.

## Uwagi i rekomendacje

- Nie była potrzebna migracja bazy danych.
- Zmiana jest lokalna i nie została jeszcze wdrożona na serwer.
- Pierwszy test użytkownika powinien dotyczyć kosztu w GBP z kwotą do zapłaty równą lub większą od pozostałej kwoty transakcji bankowej.
- Po sukcesie transakcja będzie uzgodniona, a koszt otrzyma status opłacony albo częściowo opłacony.

## Next steps

- Wdrożyć zmianę na środowisko używane do testów.
- Otworzyć transakcję `-50 GBP` i wybrać `Połącz z istniejącym kosztem`.
- Wybrać utworzony wcześniej koszt i zatwierdzić operację.
- Sprawdzić status kosztu, płatność, rachunek bankowy oraz alokację uzgodnienia.

**Raport wygenerowany automatycznie przez WebsiteExpert**
