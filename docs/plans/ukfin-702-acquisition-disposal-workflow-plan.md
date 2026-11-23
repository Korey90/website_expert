# UKFIN-702 - kontrolowane nabycie i zbycie środka trwałego

**Status:** Zrealizowane lokalnie
**Data:** 2026-11-22
**Źródło:** `docs/plans/uk-finance-tax-module-plan.md`
**Grupa docelowa:** Sole trader

## Wynik realizacji

- Migracja `000038` rozszerza `fixed_assets` i dodaje append-only `fixed_asset_transactions`.
- Nabycie i ulepszenie korzystają z całej pozycji zaksięgowanego Expense oraz tworzą wyłącznie reklasyfikację `Dr asset / Cr expense`.
- Wspólny kalkulator zachowuje zgodność z journalem zakupu dla odliczalnego VAT, nieodliczalnego VAT, walut obcych i reverse charge.
- Sprzedaż korzysta z pozycji wystawionej Invoice, pozostawia VAT i AR w fakturze oraz księguje wycofanie kosztu i wynik na zbyciu.
- Reversal działa wyłącznie od najnowszej operacji, tworzy dokładny journal lustrzany i przywraca status oraz wartości aktywa.
- Purchase i sales credit notes są blokowane do czasu odwrócenia powiązanej aktywnej operacji.
- Panel pokazuje kwalifikujące się źródła, podgląd journalu, statusy `draft`, `active`, `disposed` i pełną historię transakcji.
- Lokalny MySQL wykonał migrację jako batch 30. Migracja `000033` pozostała niezależnie oczekująca.
- `finance:audit-tenants --json`: `valid=true`, `invalid_count=0`.
- Finance: 400 testów zaliczonych, 1 pominięty, 2925 asercji.
- EN/PL/PT: po 1416 zgodnych kluczy liściowych, bez braków.
- Pint, Blade cache i kontrola diffu przeszły poprawnie.
- Produkcja nie została zmieniona.

## Cel

Rozszerzyć roboczy rejestr z UKFIN-701 o kontrolowane nabycie, aktywację, ulepszenie, sprzedaż i odwrócenie operacji. Każda zmiana wartości ma korzystać z istniejącego silnika double-entry, istniejących dokumentów zakupu lub sprzedaży oraz niezmiennego audit trail.

## Decyzja wykonawcza

Nabycie nie będzie tworzyło drugiego zakupu. Źródłem jest cała pozycja istniejącego, zaksięgowanego `Expense`. System zaksięguje wyłącznie reklasyfikację:

```text
Dr konto środka trwałego z kategorii
Cr pierwotne konto kosztowe pozycji Expense
```

VAT naliczony, zobowiązanie wobec dostawcy, płatność i uzgodnienie bankowe pozostają w istniejącym workflow kosztu. Dzięki temu nie powstaje drugi VAT, drugi creditor ani podwójny koszt.

Sprzedaż środka trwałego wymaga całej pozycji istniejącej, wystawionej `Invoice`. Faktura zachowuje należność i VAT należny. Osobny journal zbycia usuwa zwykłą sprzedaż, wyksięgowuje koszt i umorzenie oraz pokazuje zysk albo stratę na zbyciu.

## Granice etapu

UKFIN-702 obejmuje:

- aktywację draftu na podstawie zaksięgowanej pozycji Expense;
- ulepszenia aktywnego środka trwałego z kolejnej pozycji Expense;
- sprzedaż na podstawie wystawionej pozycji Invoice;
- kontrolowane odwrócenie ostatniej operacji przez istniejący `ReverseJournalEntryAction`;
- statusy `draft`, `active`, `disposed`;
- pełne snapshoty GBP, źródła, journal IDs, aktora i daty;
- panel PL/EN oraz parytet kluczy PT;
- RBAC, tenant isolation, audit i testy.

Poza zakresem pozostają:

- harmonogram i posting amortyzacji książkowej, które należą do UKFIN-703;
- raport rejestru aktywów, który należy do UKFIN-704;
- capital allowances i Self Assessment;
- darowizna, prywatne wycofanie aktywa, częściowe zbycie, zamiana i automatyczna wycena rynkowa;
- automatyczna decyzja, czy zakup spełnia kryteria środka trwałego.

Pierwsza wersja obsłuży wyłącznie sprzedaż udokumentowaną fakturą. Inne rodzaje zbycia wymagają osobnej specyfikacji księgowej i podatkowej.

## Delta analysis

### Anchor files do ponownego użycia

- `app/Models/FixedAsset.php` i `FixedAssetCategory.php` z UKFIN-701.
- `app/Models/Expense.php` i `ExpenseItem.php` jako źródło zakupu oraz niezmienne snapshoty VAT/FX.
- `app/Models/Invoice.php` i `InvoiceItem.php` jako źródło sprzedaży i VAT należnego.
- `app/Services/Finance/PurchaseJournalPayloadFactory.php` jako dokładna reguła kosztu netto wraz z nieodliczalnym VAT.
- `app/Services/Finance/SalesJournalPayloadFactory.php` jako źródło wpływu netto i VAT sprzedaży.
- `app/Actions/Finance/PostJournalEntryAction.php` jako jedyny silnik nowych księgowań.
- `app/Actions/Finance/ReverseJournalEntryAction.php` jako jedyna droga korekty zaksięgowanej operacji.
- `app/Models/AccountingPeriod.php` i `VatReturnPeriodGuard` dla blokad dat.
- `app/Models/FinancialAttachment.php` dla dowodu zakupu i sprzedaży.
- konta `1500`, `1600`, `4200` i `7900` z domyślnego planu kont.

### Luki

- brak źródła nabycia i zbycia na aktywie;
- brak niezmiennej historii operacji wartościowych per aktywo;
- brak kosztu nabycia, daty oddania do użytkowania, daty zbycia i proceeds w GBP;
- brak reklasyfikacji kosztu do aktywa bez powtórzenia VAT/AP;
- brak wyksięgowania kosztu i umorzenia oraz zysku albo straty przy sprzedaży;
- brak blokady podwójnego użycia tej samej pozycji Expense lub Invoice;
- brak bezpiecznej obsługi credit notes po powiązaniu pozycji z aktywem;
- brak UI i testów przejść `draft -> active -> disposed`.

## Model danych

### Rozszerzenie `fixed_assets`

- statusy `draft`, `active`, `disposed`;
- `acquisition_date` i `in_service_date`;
- `acquisition_cost_gbp` jako suma aktywnych nabyć i ulepszeń;
- `disposed_at`, `disposal_proceeds_gbp`, `disposal_gain_loss_gbp`;
- `activated_by`, `activated_at`, `disposed_by` i znaczniki czasu;
- indeks `business_id + status + in_service_date`.

### `fixed_asset_transactions`

- `business_id` i `fixed_asset_id`;
- typ `acquisition`, `improvement` albo `disposal`;
- status `posted` albo `reversed`;
- `event_date`;
- opcjonalny `expense_item_id` dla nabycia lub ulepszenia;
- opcjonalny `invoice_item_id` dla sprzedaży;
- kwota zdarzenia w GBP;
- dla zbycia snapshot kosztu, umorzenia, NBV, proceeds i gain/loss;
- `journal_entry_id` i opcjonalny `reversal_journal_entry_id`;
- `created_by`, `reversed_by`, `reversed_at`, `reversal_reason`;
- unikalność źródłowej pozycji zakupu i sprzedaży;
- indeksy tenant, aktywo, typ, status i data.

Rekord transakcji powstaje przed postingiem w tej samej transakcji DB i jest źródłem morph dla journalu. Po posted nie można go edytować ani usunąć. Korekta ustawia `reversed` dopiero po utworzeniu dokładnego journalu lustrzanego.

## Reguły nabycia

1. Aktywo musi być draftem, a kategoria musi pozostać aktywna.
2. Źródłem jest jedna pełna pozycja Expense z tego samego tenanta.
3. Expense musi być zaksięgowany i mieć aktywny, nieodwrócony journal zakupu.
4. Pozycja nie może być wcześniej użyta przez inne aktywo ani mieć aktywnej purchase credit note.
5. Dla wielu fizycznych aktywów użytkownik powinien przygotować osobne pozycje kosztu przed postingiem. Pierwsza wersja nie dzieli jednej pozycji pomiędzy kilka aktywów.
6. Koszt GBP to net GBP plus nieodliczalny VAT, obliczony tą samą regułą co journal zakupu.
7. Data oddania do użytkowania nie może poprzedzać tax point zakupu i musi należeć do zapisywalnego okresu.
8. Aktywacja księguje `Dr asset / Cr original expense account` bez tax code.
9. Po sukcesie status zmienia się na `active`, a snapshot kwoty, źródła i journalu jest niezmienny.
10. Wymagany jest co najmniej jeden czysty dokument źródłowy przy Expense.

## Reguły ulepszenia

1. Aktywo musi być aktywne i niezbyte.
2. Źródłem jest inna, pełna i zaksięgowana pozycja Expense.
3. Obowiązują te same zasady VAT, dowodu, tenant i unikalności co dla nabycia.
4. Journal ma postać `Dr asset / Cr original expense account`.
5. `acquisition_cost_gbp` jest sumą nieodwróconego nabycia oraz ulepszeń.

## Reguły sprzedaży i zbycia

1. Aktywo musi być aktywne.
2. Źródłem jest jedna pełna pozycja wystawionej Invoice tego samego tenanta.
3. Invoice musi mieć aktywny journal sprzedaży, a pozycja nie może mieć credit note ani wcześniejszego powiązania ze zbyciem.
4. Data zbycia odpowiada tax point faktury i musi należeć do zapisywalnego okresu.
5. VAT i trade debtor pozostają wyłącznie w journalu faktury.
6. Journal zbycia księguje:

```text
Dr Sales 4100                         proceeds netto GBP
Dr Accumulated depreciation 1600     umorzenie do dnia zbycia
Dr Other expenses 7900               strata, jeżeli występuje
Cr Fixed asset account               historyczny koszt aktywa
Cr Other income 4200                 zysk, jeżeli występuje
```

7. Journal musi bilansować się w pensach i nie może zawierać tax code.
8. Po sukcesie status aktywa zmienia się na `disposed`, a snapshot kosztu, umorzenia, NBV, proceeds i gain/loss jest trwały.
9. Do czasu UKFIN-703 umorzenie wynosi zero. UKFIN-703 ma dostarczyć źródło skumulowanej amortyzacji dla tej samej kalkulacji zbycia.

## Credit notes i korekty

- Aktywna purchase credit note dla źródłowej pozycji blokuje nabycie lub ulepszenie.
- Utworzenie purchase credit note dla pozycji powiązanej z aktywną transakcją środka trwałego jest blokowane. Najpierw trzeba odwrócić odpowiednią operację aktywa.
- Credit note sprzedaży dla pozycji użytej w aktywnym zbyciu jest blokowana. Najpierw trzeba odwrócić zbycie.
- Odwrócenie używa `ReverseJournalEntryAction`, zachowuje oryginał i journal lustrzany oraz wymaga daty i uzasadnienia.
- Nabycie można odwrócić tylko wtedy, gdy nie ma późniejszych ulepszeń, amortyzacji ani zbycia.
- Ulepszenie można odwrócić tylko wtedy, gdy nie istnieje późniejsze zależne zdarzenie.
- Zbycie można odwrócić do stanu `active`; faktura pozostaje zaksięgowaną zwykłą sprzedażą do czasu osobnego credit note.

## Uprawnienia i UI

- `view_finance` pokazuje aktywo, źródła, transakcje i journale.
- `manage_finance` pozwala przygotować i edytować wyłącznie draft.
- `post_finance` jest wymagane dla nabycia, ulepszenia, zbycia i reversal.
- Akcje są dostępne na widoku aktywa tylko dla dozwolonego statusu.
- Selektory pokazują wyłącznie kwalifikujące się pozycje bieżącego tenanta i nie ujawniają danych innej firmy.
- Formularze pokazują przed zatwierdzeniem konto źródłowe, konto aktywa, kwotę GBP, VAT pozostający w dokumencie oraz przewidywany journal.
- Wszystkie komunikaty i etykiety mają wersje PL/EN; repo zachowuje identyczny zestaw kluczy PT.

## Plan implementacji

1. [x] Dodać migrację `000038` rozszerzającą `fixed_assets` i tworzącą `fixed_asset_transactions`.
2. [x] Dodać model, factory, relacje, casty i ochronę niezmienności transakcji.
3. [x] Wyodrębnić wspólny kalkulator kapitalizowanej wartości pozycji Expense i pokryć zgodność z purchase journal.
4. [x] Dodać payload factory dla nabycia, ulepszenia i zbycia.
5. [x] Dodać `AcquireFixedAssetAction`, `ImproveFixedAssetAction`, `DisposeFixedAssetAction` i `ReverseFixedAssetTransactionAction`.
6. [x] Rozszerzyć status guard modelu aktywa o kontrolowane przejścia wykonywane wyłącznie przez Actions.
7. [x] Dodać blokady do `CreatePurchaseCreditNoteAction` i `CreateCreditNoteAction`.
8. [x] Dodać akcje, podgląd journalu i historię transakcji na `ViewFixedAsset`.
9. [x] Rozszerzyć tenant audit o źródłowe pozycje, journale i reversal journals.
10. [x] Dodać PL/EN/PT, instrukcję użytkownika oraz testy end-to-end i regresję raportów.

## Planowane pliki

- `database/migrations/2026_11_22_000038_add_fixed_asset_transactions.php`;
- `app/Models/FixedAssetTransaction.php`;
- `database/factories/FixedAssetTransactionFactory.php`;
- `app/Services/Finance/FixedAssetCostBasisService.php`;
- `app/Services/Finance/FixedAssetJournalPayloadFactory.php`;
- cztery Actions w `app/Actions/Finance`;
- rozszerzenia modeli ExpenseItem, InvoiceItem, FixedAsset i Business;
- rozszerzenia `CreatePurchaseCreditNoteAction` i `CreateCreditNoteAction`;
- rozszerzenie `FixedAssetResource` i `ViewFixedAsset`;
- `tests/Feature/Finance/FixedAssetLifecycleTest.php`.

## Testy akceptacyjne

- aktywacja z posted Expense reklasyfikuje koszt bez zmiany VAT i AP;
- koszt obejmuje nieodliczalny VAT, ale nie dubluje VAT odliczalnego ani reverse charge;
- jedna pozycja Expense lub Invoice nie może zasilić dwóch aktywów;
- tenant A nie może wybrać źródła tenanta B;
- brak czystego dowodu, zamknięty okres, zablokowany VAT period i brak `post_finance` zatrzymują całą operację;
- retry zwraca ten sam journal i nie tworzy drugiej transakcji;
- ulepszenie zwiększa koszt historyczny bez wpływu na VAT;
- sprzedaż zachowuje invoice VAT i AR, usuwa zwykłą sprzedaż, wyksięgowuje aktywo oraz pokazuje właściwy gain/loss;
- credit notes są blokowane do czasu kontrolowanego reversal;
- reversal jest dokładnym lustrem i przywraca poprawny status oraz salda;
- aktywa draft/active/disposed mają poprawne zasady edycji i usuwania;
- P&L i Balance Sheet pokazują reklasyfikację i zbycie zgodnie z journal lines;
- `finance:audit-tenants` wykrywa każdą referencję cross-tenant;
- UI działa po polsku i angielsku, a PL/EN/PT mają identyczne klucze.

## Ryzyka i zabezpieczenia

- Podwójny zakup: brak nowego AP/VAT journalu, tylko reklasyfikacja źródłowego kosztu.
- Rozjazd VAT: journal środka trwałego nie ma tax code; VAT pozostaje w źródłowej fakturze zakupu lub sprzedaży.
- Credit note po kapitalizacji: fail-closed do czasu reversal operacji aktywa.
- Zamknięte okresy: wszystkie journale przechodzą przez istniejący period i VAT lock guard.
- Zmiana historii: posted transakcje są append-only, a korekta powstaje wyłącznie jako reversal.
- Niepewne zbycie inne niż sprzedaż: wyłączone do osobnej akceptacji księgowej i podatkowej.
- Capital allowances: jawnie nie są wyliczane ani księgowane przez ten workflow.

## Definition of Done

- nabycie i sprzedaż wykorzystują istniejące dokumenty źródłowe bez podwójnego VAT, AP lub AR;
- każdy journal jest zbilansowany, idempotentny i tenantowy;
- status i snapshot aktywa odpowiadają journalom;
- credit notes i reversal nie mogą zostawić niespójnych sald;
- panel ma UI PL/EN i parytet PT;
- Finance, Filament coverage, audit, Pint, Blade i tłumaczenia przechodzą;
- dokumentacja opisuje granice podatkowe i operacyjne;
- produkcja nie jest modyfikowana bez osobnej decyzji wdrożeniowej.
