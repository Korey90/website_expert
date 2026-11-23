# UKFIN-702 - nabycie i zbycie środków trwałych

**Status:** Zakończone lokalnie
**Data:** 2026-11-22
**Produkcja:** Bez zmian

## Wynik

- Dodano statusy aktywa `draft`, `active` i `disposed` oraz snapshoty kosztu, dat i wyniku zbycia.
- Dodano append-only `fixed_asset_transactions` dla nabycia, ulepszenia, sprzedaży i reversal.
- Nabycie oraz ulepszenie korzystają z całej pozycji zaksięgowanego Expense.
- Journal aktywacji wykonuje wyłącznie `Dr asset / Cr expense`, bez ponownego VAT, AP, płatności lub banku.
- Kapitalizowany koszt odpowiada journalowi zakupu: netto plus nieodliczalny VAT. Odliczalny VAT i neutralny reverse charge nie zwiększają aktywa.
- Sprzedaż korzysta z całej pozycji wystawionej Invoice. Faktura zachowuje VAT i AR, a osobny journal usuwa zwykłą sprzedaż, wyksięgowuje aktywo i pokazuje zysk albo stratę.
- Korekta odwraca najnowszą operację przez istniejący `ReverseJournalEntryAction` i zachowuje pełny audit trail.
- Purchase i sales credit notes są blokowane, dopóki zależna operacja aktywa nie zostanie odwrócona.
- Panel PL/EN pokazuje tylko kwalifikujące się źródła, podgląd journalu i historię operacji. Zachowano pełny parytet PT.
- Tenant audit sprawdza aktywo, pozycje źródłowe, journal oraz reversal journal.

## Granice

- Obsługiwane zbycie to sprzedaż udokumentowana Invoice.
- Darowizna, prywatne wycofanie, częściowe zbycie i zamiana nie są automatycznie księgowane.
- Book depreciation należy do UKFIN-703.
- UK capital allowances i Self Assessment pozostają poza tym workflow.
- Do czasu UKFIN-703 skumulowana amortyzacja wynosi zero.

## Dane i pliki

- Migracja `2026_11_22_000038_add_fixed_asset_transactions.php`.
- Model `FixedAssetTransaction` i factory.
- Actions: `AcquireFixedAssetAction`, `ImproveFixedAssetAction`, `DisposeFixedAssetAction`, `ReverseFixedAssetTransactionAction`.
- Serwisy: `FixedAssetCostBasisService`, `FixedAssetJournalPayloadFactory`, `FixedAssetLifecycleGuard`.
- UI: `FixedAssetResource` i `ViewFixedAsset`.
- Testy: `tests/Feature/Finance/FixedAssetLifecycleTest.php`.
- Plan i wynik: `docs/plans/ukfin-702-acquisition-disposal-workflow-plan.md`.

## Weryfikacja

- Lokalny MySQL: migracja `000038` wykonana jako batch 30.
- Niezwiązana migracja `000033` pozostała oczekująca.
- `finance:audit-tenants --json`: `valid=true`, `invalid_count=0`.
- Finance: 400 passed, 1 skipped, 2925 assertions.
- Dedykowany lifecycle: 4 passed, 54 assertions.
- Credit notes i purchase/sales posting: 25 passed, 125 assertions.
- EN/PL/PT: po 1416 kluczy, brak różnic.
- Pint, Blade cache i `git diff --check`: poprawne.

## Następny etap

UKFIN-703 ma dodać harmonogram i księgowanie amortyzacji książkowej, aktualizację skumulowanego umorzenia oraz bezpieczne powiązanie z reversal i zbyciem.
