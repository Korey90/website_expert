# 2026-11-20 - UKFIN-202R1 precyzja ceny jednostkowej kosztu

**Status:** Zakończone sukcesem
**Data realizacji:** 2026-11-20
**Czas trwania:** poniżej 1 dnia

## Cel zadania

Naprawić błędne obliczanie kosztu dla cen jednostkowych wymagających więcej niż 2 miejsc dziesiętnych oraz dodać podgląd netto, VAT i brutto przed zapisaniem dokumentu.

## Zakres wykonanych prac

- Ustalono, że `0.0333333333` było zaokrąglane w akcji do `0.03`, a następnie utrwalane w kolumnie `DECIMAL(15,2)`.
- Zwiększono precyzję `unit_price` do `DECIMAL(24,10)` dla pozycji kosztów, szablonów kosztów cyklicznych i zakupowych not kredytowych.
- Zmieniono casty modeli na `decimal:10`.
- Usunięto przedwczesne zaokrąglenie ceny do 2 miejsc w synchronizacji kosztów i szablonów cyklicznych.
- Dodano walidację od 0 do 10 miejsc dziesiętnych.
- Rozszerzono pola formularza tak, aby przyjmowały krok `0.0000000001`.
- Dodano podgląd netto, VAT i brutto aktualizowany po zmianie ilości, ceny albo kodu podatkowego.
- Podgląd i zapis korzystają ze wspólnej funkcji obliczającej pozycję i zaokrąglają kwoty pieniężne do najmniejszej jednostki waluty.
- Dodano tłumaczenia EN, PL i PT.
- Dodano regresję dokładnie dla `1250 x 0.0333333333`, walidacji skali oraz generowania kosztu cyklicznego.
- Wykonano migrację `2026_11_20_000034` na lokalnej bazie MySQL.

## Użyte agenty i skille

- Agent główny Codex
- Skill: multi-language-check
- Skill: task-completion-report
- Nie używano agentów pomocniczych

## Zmodyfikowane / utworzone pliki

- `app/Actions/Finance/SyncExpenseItemsAction.php`
- `app/Actions/Finance/SyncRecurringExpenseTemplateItemsAction.php`
- `app/Filament/Resources/ExpenseResource.php`
- `app/Filament/Resources/RecurringExpenseTemplateResource.php`
- `app/Http/Requests/Finance/ExpenseRequest.php`
- `app/Models/ExpenseItem.php`
- `app/Models/RecurringExpenseTemplateItem.php`
- `app/Models/PurchaseCreditNoteItem.php`
- `app/Services/Finance/PurchaseTaxCalculator.php`
- `database/migrations/2026_11_20_000034_expand_purchase_unit_price_precision.php`
- `resources/views/filament/forms/components/expense-totals-preview.blade.php`
- `lang/en/finance.php`
- `lang/pl/finance.php`
- `lang/pt/finance.php`
- `tests/Feature/Finance/ExpenseWorkflowTest.php`
- `tests/Feature/Finance/ExpenseRequestTest.php`
- `tests/Feature/Finance/RecurringExpenseGenerationTest.php`
- `docs/plans/uk-finance-tax-module-plan.md`
- `.github/live-docs/current-task.md`
- `.github/live-docs/current-sprint.md`
- `.github/live-docs/status-dashboard.md`

## Walidacja końcowa

- Laravel Pint przechodzi dla wszystkich zmienionych plików PHP.
- Blade cache został poprawnie zbudowany.
- Lokalna migracja MySQL zakończyła się poprawnie, a `expense_items.unit_price` ma typ `DECIMAL(24,10)`.
- Test scenariusza potwierdza zapis `0.0333333333` oraz wynik `41.67 GBP` netto, `8.33 GBP` VAT i `50.00 GBP` brutto.
- Targeted: 29 testów, 109 asercji.
- Pełny moduł Finance: 365 testów zaliczonych, 1 pominięty, 2565 asercji.
- Multi-language check: po 1287 identycznych kluczy w EN, PL i PT, bez braków i kluczy nadmiarowych.
- Multi-tenancy pozostaje zachowane, ponieważ podgląd pobiera kody podatkowe wyłącznie dla aktywnego `business_id`.

## Uwagi i rekomendacje

- Migracja zmienia strukturę, ale nie może odzyskać cyfr wcześniej utraconych przez zapis do 2 miejsc. Istniejący błędny szkic trzeba edytować i ponownie wpisać `0.0333333333`.
- Migracja nie została jeszcze wdrożona na serwer.
- Niezwiązana migracja RBAC `000033` pozostała oczekująca w lokalnej bazie i nie została uruchomiona w ramach tego zadania.

## Next steps

- Wdrożyć kod i migrację `000034` na serwer testowy lub produkcyjny.
- Edytować istniejący szkic kosztu, ponownie wpisać dokładną cenę jednostkową i sprawdzić podgląd `41.67 / 8.33 / 50.00 GBP` przed zapisem.
- Po zapisie potwierdzić, że baza przechowuje `0.0333333333`.

**Raport wygenerowany automatycznie przez WebsiteExpert**
