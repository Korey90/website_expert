# UKFIN-703 - harmonogramy amortyzacji książkowej

**Status:** Delta analysis zakończone, plan gotowy do realizacji
**Data:** 2026-11-22
**Źródło:** `docs/plans/uk-finance-tax-module-plan.md`
**Grupa docelowa:** Sole trader

## Cel

Dodać przewidywalny harmonogram oraz kontrolowane księgowanie amortyzacji książkowej aktywnych środków trwałych. Posting ma korzystać wyłącznie z istniejącego silnika double-entry, respektować blokady okresów i VAT Return oraz aktualizować skumulowane umorzenie używane przez workflow zbycia UKFIN-702.

## Granice etapu

UKFIN-703 obejmuje:

- miesięczny harmonogram amortyzacji książkowej;
- metody `straight_line`, `reducing_balance` i `none`;
- snapshot kosztu, wartości rezydualnej i parametrów każdej pozycji harmonogramu;
- kontrolowany posting `Dr depreciation expense / Cr accumulated depreciation`;
- odwrócenie ostatniego księgowania amortyzacji przez dokładny journal lustrzany;
- zatrzymanie harmonogramu przy zbyciu i uwzględnienie skumulowanego umorzenia w UKFIN-702;
- UI PL/EN oraz parytet PT, RBAC, tenant isolation, audit i testy.

Poza zakresem pozostają:

- UK capital allowances, Annual Investment Allowance i writing-down allowances;
- Self Assessment i MTD Income Tax;
- impairments, revaluation, komponentyzacja i częściowe zbycie;
- automatyczne księgowanie bez jawnej akcji użytkownika;
- podatkowa decyzja, czy dany zakup jest środkiem trwałym.

## Delta analysis

### Anchor files

- `FixedAsset` przechowuje koszt historyczny, datę oddania do użytkowania, metodę, okres użytkowania, residual value oraz skumulowane umorzenie.
- `FixedAssetTransaction` dostarcza datowane nabycie, ulepszenia i zbycie.
- `FixedAssetCategory` mapuje konto aktywa, umorzenia i kosztu amortyzacji.
- `PostJournalEntryAction` jest jedynym silnikiem nowych księgowań.
- `ReverseJournalEntryAction` tworzy dokładne i audytowalne reversals.
- `AccountingPeriod` i `VatReturnPeriodGuard` blokują niedozwolone daty.
- `DisposeFixedAssetAction` odczytuje `accumulated_depreciation_gbp` i jest gotowe na wartość dostarczoną przez ten etap.

### Luki

- brak pozycji harmonogramu i snapshotu naliczenia per miesiąc;
- brak konwencji daty rozpoczęcia i końca naliczania;
- brak parametru stopy dla metody reducing balance;
- brak posting action i journal source dla amortyzacji;
- brak kontrolowanego reversal oraz aktualizacji skumulowanego umorzenia;
- UKFIN-702 nie sprawdza jeszcze zależnych księgowań amortyzacji przy odwracaniu nabycia lub ulepszenia;
- brak UI pokazującego plan, zaksięgowane miesiące i remaining depreciable value.

## Decyzje wykonawcze

### Konwencja okresowa

- Harmonogram jest miesięczny.
- Pierwszy okres kończy się w ostatnim dniu miesiąca oddania do użytkowania.
- Pierwszy i ostatni miesiąc są naliczane proporcjonalnie do liczby dni użytkowania w miesiącu.
- Ulepszenie zwiększa podstawę od dnia zdarzenia i wpływa na bieżący miesiąc proporcjonalnie.
- Zbycie kończy naliczanie w dniu zbycia; po dacie zbycia nie wolno tworzyć pozycji.
- Suma pozycji nie może obniżyć NBV poniżej wartości rezydualnej.

### Metody

- `straight_line`: pozostała kwota podlegająca amortyzacji jest rozdzielana na pozostałe dni okresu użytkowania, a miesięczny wynik jest zaokrąglany do pensów. Ostatnia pozycja wyrównuje różnicę groszową.
- `reducing_balance`: wymagany będzie jawny roczny procent amortyzacji zapisany jako snapshot na kategorii i aktywie. Metoda nalicza stawkę dzienną od otwierającego NBV, z limitem wartości rezydualnej.
- `none`: harmonogram pozostaje pusty i posting jest niedostępny.

Nie należy wyprowadzać reducing-balance rate automatycznie z useful life. Stopa jest istotną decyzją księgową i musi być jawna.

## Model danych

### Rozszerzenie kategorii i aktywa

- `default_depreciation_rate_percent` na kategorii;
- `depreciation_rate_percent` na aktywie;
- walidacja: wymagane tylko dla `reducing_balance`, zakres większy od 0 i nie większy niż 100;
- po aktywacji parametry pozostają snapshotem i nie mogą być edytowane poza kontrolowanym workflow.

### `fixed_asset_depreciation_entries`

- tenant i `fixed_asset_id`;
- `period_start`, `period_end` i `posting_date`;
- status `planned`, `posted` albo `reversed`;
- snapshot opening cost, accumulated depreciation, NBV, residual value i metody;
- `amount_gbp`, closing accumulated depreciation i closing NBV;
- `journal_entry_id`, `reversal_journal_entry_id`;
- created/posted/reversed actor, czas i powód;
- unikalność aktywa i `period_end`;
- indeksy tenant, aktywo, status i posting date.

Pozycja po posted jest niezmienna. Zmiana historii odbywa się wyłącznie przez reversal. Planowane pozycje można bezpiecznie przeliczyć, jeżeli nie istnieją po nich zaksięgowane pozycje.

## Workflow

1. `GenerateFixedAssetDepreciationScheduleAction` waliduje aktywo i odbudowuje wyłącznie niezaksięgowane przyszłe pozycje.
2. Kalkulator pracuje w integer pennies i zapewnia sumę do wartości rezydualnej bez driftu.
3. `PostFixedAssetDepreciationAction` blokuje aktywo i pozycję, wymaga `post_finance`, otwartego okresu i poprawnego następstwa.
4. Journal zawiera `Dr category.depreciation_expense_account / Cr category.accumulated_depreciation_account`, bez tax code.
5. Po sukcesie pozycja staje się `posted`, a `fixed_assets.accumulated_depreciation_gbp` zwiększa się atomowo.
6. `ReverseFixedAssetDepreciationAction` działa wyłącznie dla najnowszej posted pozycji, tworzy dokładne lustro i zmniejsza accumulated depreciation.
7. Nabycia, ulepszenia i zbycia nie można odwrócić, jeżeli istnieje późniejsze zależne księgowanie amortyzacji.
8. Zbycie używa wartości umorzenia po ostatniej pozycji z datą nie późniejszą niż disposal date.

## UI

- Sekcja aktywa pokazuje koszt historyczny, umorzenie, NBV, residual value i remaining depreciable amount.
- Osobna historia pokazuje planowane, zaksięgowane i odwrócone miesiące oraz journale.
- Akcje: wygeneruj/przelicz harmonogram, zaksięguj następną pozycję, odwróć ostatnią amortyzację.
- Podgląd przed postingiem pokazuje datę, kwotę, oba konta i wpływ na NBV.
- `post_finance` jest wymagane dla posting i reversal; `manage_finance` dla generowania planu.
- Selektory i akcje nie mogą ujawnić aktywów innego tenanta.

## Plan implementacji

1. Dodać migrację `000039` z rate fields i `fixed_asset_depreciation_entries`.
2. Rozszerzyć walidację kategorii, aktywa, Actions i formularze o reducing-balance rate.
3. Dodać model, factory, relacje i ochronę niezmienności pozycji harmonogramu.
4. Dodać kalkulator straight-line i reducing-balance pracujący w pensach.
5. Dodać generowanie i bezpieczne przeliczanie wyłącznie przyszłych planned entries.
6. Dodać payload factory oraz posting pojedynczej następnej pozycji.
7. Dodać reversal ostatniej amortyzacji i aktualizację accumulated depreciation.
8. Powiązać guardy UKFIN-702 z zależnymi depreciation entries i datą zbycia.
9. Rozszerzyć tenant audit, UI PL/EN/PT i instrukcję użytkownika.
10. Dodać testy kalkulacji, rounding, locked periods, reversal, disposal, raportów i tenant isolation.

## Testy akceptacyjne

- straight-line z częściowym pierwszym i ostatnim miesiącem kończy dokładnie na residual value;
- reducing balance używa jawnej stopy i nigdy nie schodzi poniżej residual value;
- ulepszenie zmienia tylko niezaksięgowaną część harmonogramu od daty zdarzenia;
- `none` nie tworzy pozycji;
- tylko następna chronologiczna pozycja może zostać zaksięgowana;
- retry nie tworzy drugiego journalu;
- zamknięty okres, locked VAT range i brak `post_finance` powodują pełny rollback;
- reversal jest dokładnym lustrem i aktualizuje accumulated depreciation oraz NBV;
- zbycie używa umorzenia do dnia sprzedaży i blokuje późniejsze postingi;
- nie można odwrócić nabycia lub ulepszenia przy zależnej amortyzacji;
- P&L pokazuje koszt amortyzacji, a Balance Sheet aktywo i contra asset;
- tenant audit wykrywa każdą cross-tenant reference;
- panel PL/EN działa, a zestaw kluczy PT pozostaje identyczny.

## Definition of Done

- harmonogram jest deterministyczny, pensowy i kończy na wartości rezydualnej;
- posted entries są immutable i odwracalne wyłącznie przez journal lustrzany;
- accumulated depreciation aktywa zawsze odpowiada sumie nieodwróconych pozycji;
- zbycie oraz reversal z UKFIN-702 respektują zależności amortyzacji;
- Finance, raporty, Filament, audit, Pint, Blade i tłumaczenia przechodzą;
- dokumentacja jasno oddziela book depreciation od UK capital allowances;
- produkcja nie jest modyfikowana bez osobnej decyzji wdrożeniowej.
