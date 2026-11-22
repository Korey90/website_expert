# UKFIN-701 - rejestr środków trwałych i kategorii

**Status:** Zakończone lokalnie
**Data:** 2026-11-22
**Grupa docelowa:** Sole trader
**Wdrożenie produkcyjne:** Nie wykonano w tym zadaniu

## Zakres

- tenantowe kategorie środków trwałych z nazwą EN/PL i bezpiecznym fallbackiem PT;
- mapowanie kategorii na konto środka trwałego, umorzenia i kosztu amortyzacji;
- draft-only rejestr aktywów z numeracją `FA-YYYY-NNN`;
- snapshot metody, okresu użytkowania i wartości rezydualnej;
- transakcyjne Actions create/update, policies i fail-closed registry;
- panel Filament dla kategorii i aktywów;
- rozszerzony tenant audit i ochrona kont użytych przez kategorie;
- instrukcja użytkownika i tłumaczenia PL/EN/PT.

## Granica etapu

Draft aktywa nie tworzy journal entry i nie zmienia ledgeru, P&L, Balance Sheet ani VAT. Nabycie, aktywacja, ulepszenie i zbycie są zakresem UKFIN-702. Harmonogram oraz posting amortyzacji są zakresem UKFIN-703. UK capital allowances pozostają poza automatyczną księgą do osobnej specyfikacji zatwierdzonej przez księgowego UK.

## Walidacja

- migracja `000037` przeszła na SQLite i lokalnym MySQL;
- lokalny MySQL: batch 29, `finance:audit-tenants` valid, `invalid_count=0`;
- Finance: 396 passed, 1 skipped, 2871 assertions;
- panel i istniejący tenant audit: 17 passed, 1201 assertions;
- Pint: passed;
- Blade cache: passed;
- EN/PL/PT: po 1375 identycznych kluczy liściowych.

## Uwagi operacyjne

- Lokalnie wykonano wyłącznie migrację `000037`.
- Starsza niezależna migracja `000033_harden_admin_module_permissions` pozostała oczekująca.
- Kod nie został w tym zadaniu wdrożony na `website-expert.uk`.
