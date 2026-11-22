# UKFIN-701 - FixedAsset i kategorie aktywów

**Status:** Zrealizowane lokalnie
**Data:** 2026-11-22
**Źródło:** `docs/plans/uk-finance-tax-module-plan.md`
**Grupa docelowa:** Sole trader

## Cel

Utworzyć tenantowy rejestr środków trwałych i kategorii aktywów, który wykorzysta istniejący plan kont i przygotuje stabilną podstawę dla późniejszego księgowania nabycia, zbycia, amortyzacji książkowej i raportu rejestru aktywów.

## Granice etapu

UKFIN-701 obejmuje dane podstawowe, walidację, RBAC, tenant isolation i panel administracyjny. Ten etap nie tworzy journal entries i nie zmienia sald raportów finansowych.

Następujące operacje pozostają w kolejnych zadaniach:

- nabycie, aktywacja, ulepszenie i zbycie aktywa: UKFIN-702;
- harmonogram i księgowanie amortyzacji książkowej: UKFIN-703;
- raport rejestru aktywów oraz eksporty: UKFIN-704;
- capital allowances i inne reguły podatkowe: poza automatyczną księgą do czasu osobnej specyfikacji zaakceptowanej przez księgowego UK.

## Delta analysis

### Anchor files do ponownego użycia

- `app/Traits/BelongsToTenant.php` i `app/Scopes/BusinessScope.php` - obowiązkowa izolacja aktywnego tenanta.
- `app/Models/LedgerAccount.php` - istniejące konta aktywa, umorzenia i kosztu amortyzacji.
- `app/Support/Finance/DefaultUkChartAccounts.php` - konta `1500`, `1600` i `7000` są już dostępne.
- `app/Actions/Finance/PostJournalEntryAction.php` - jedyny silnik księgowań, który zostanie użyty dopiero przez UKFIN-702/703.
- `app/Actions/Finance/GenerateDocumentNumberAction.php` - bezpieczna sekwencja numerów per tenant i rok.
- `app/Models/FinancialAttachment.php` i `app/Services/Finance/FinancialAttachmentService.php` - prywatne dowody z SHA-256 i skanem bezpieczeństwa.
- `app/Filament/Resources/BaseResource.php` i `app/Filament/Support/FilamentPermissionRegistry.php` - fail-closed RBAC panelu.
- `app/Policies/ExpensePolicy.php` i `app/Filament/Resources/LedgerAccountResource.php` - wzorzec policy, formularza, tabeli i filtrów finansowych.
- `app/Console/Commands/AuditFinanceTenantData.php` - centralny audyt referencji cross-tenant.

### Istniejące elementy, których nie wolno duplikować

- plan kont i salda journal lines;
- accounting periods i blokady dat;
- silnik idempotentnego double-entry posting;
- odwracanie księgowań;
- prywatne załączniki finansowe;
- klucze `view_finance`, `manage_finance` i `post_finance`.

### Luki

- brak modeli i tabel kategorii środków trwałych oraz samych aktywów;
- brak stabilnego numeru aktywa per tenant;
- brak kontrolowanego mapowania kategorii na trzy konta księgowe;
- brak statusu draft chroniącego przed pozornym ujęciem w księdze;
- brak panelu rejestru i zarządzania kategoriami;
- brak audytu referencji tenantowych i testów RBAC dla tej domeny.

## Model danych

### `fixed_asset_categories`

- `business_id`;
- unikalny per tenant `code`;
- tłumaczona nazwa `name` w JSON dla EN/PL/PT;
- `asset_account_id` wskazujące aktywne konto `asset/fixed_asset`;
- `accumulated_depreciation_account_id` wskazujące aktywne konto `asset/contra_asset`;
- `depreciation_expense_account_id` wskazujące aktywne konto `expense/depreciation`;
- opcjonalne domyślne: metoda amortyzacji książkowej, okres użytkowania w miesiącach i procent wartości rezydualnej;
- `is_active`, znaczniki czasu;
- unique `business_id + code` i indeks `business_id + is_active`.

Kategoria nie może zostać usunięta po użyciu przez aktywo. Można ją dezaktywować, aby zachować historię.

### `fixed_assets`

- `business_id` i `fixed_asset_category_id`;
- stabilny, unikalny per tenant numer `FA-YYYY-NNN`;
- nazwa, opis, numer seryjny, lokalizacja i notatki;
- status `draft` w UKFIN-701;
- snapshot metody, okresu użytkowania i procentu wartości rezydualnej z kategorii, z możliwością świadomego nadpisania;
- `created_by`, `updated_by`, znaczniki czasu i soft delete.

Kwoty nabycia, kursy, powiązania z kosztami, daty aktywacji, journal IDs i dane zbycia zostaną dodane przez UKFIN-702. Dzięki temu sam draft aktywa nie wpływa na ledger ani raporty.

## Reguły domenowe

1. Aktor musi mieć aktywne członkostwo w firmie i `manage_finance` dla zapisu.
2. Wszystkie konta kategorii muszą należeć do tego samego tenanta, być aktywne i księgowalne.
3. Typ i subtype każdego konta muszą pasować do jego roli.
4. Trzy konta kategorii muszą być różnymi rekordami.
5. Aktywo może użyć wyłącznie aktywnej kategorii własnego tenanta.
6. Numer aktywa generuje istniejący mechanizm sekwencji w transakcji i nie może być edytowany.
7. W UKFIN-701 można edytować i usunąć tylko rekord `draft`.
8. Utworzenie lub edycja draftu nie tworzy journal entry i nie zmienia raportów.
9. Metoda `none` nie wymaga okresu użytkowania; pozostałe metody wymagają dodatniej liczby miesięcy.
10. Procent wartości rezydualnej musi mieścić się w zakresie 0-100.

## Plan implementacji

1. Dodać reversible migration dla kategorii i aktywów wraz z kluczami obcymi oraz indeksami tenantowymi.
2. Dodać `FixedAssetCategory` i `FixedAsset` z `BelongsToTenant`, relacjami, castami i ochroną rekordów niedraftowych.
3. Rozszerzyć `Business`, `LedgerAccount`, `DocumentSequence` i generator numerów o relacje oraz typ `fixed_asset`.
4. Dodać serwis walidacji kont kategorii oraz Actions create/update dla obu modeli.
5. Dodać policies oparte na `view_finance` i `manage_finance`.
6. Dodać dwa Filament Resources z formularzami, filtrami, bezpiecznymi selektorami kont i widokiem aktywa.
7. Dodać oba Resources do fail-closed permission registry.
8. Rozszerzyć `finance:audit-tenants` o kategorie, aktywa, konta i autorów.
9. Dodać tłumaczenia PL/EN/PT oraz wpis w instrukcji użytkownika.
10. Dodać testy migracji, Actions, tenant isolation, RBAC, sekwencji i braku wpływu draftów na ledger.

## Planowane pliki

- `database/migrations/2026_11_22_000037_create_fixed_assets_and_categories.php`
- `app/Models/FixedAssetCategory.php`
- `app/Models/FixedAsset.php`
- `database/factories/FixedAssetCategoryFactory.php`
- `database/factories/FixedAssetFactory.php`
- `app/Services/Finance/FixedAssetCategoryAccountGuard.php`
- `app/Actions/Finance/CreateFixedAssetCategoryAction.php`
- `app/Actions/Finance/UpdateFixedAssetCategoryAction.php`
- `app/Actions/Finance/CreateFixedAssetAction.php`
- `app/Actions/Finance/UpdateFixedAssetAction.php`
- `app/Policies/FixedAssetCategoryPolicy.php`
- `app/Policies/FixedAssetPolicy.php`
- `app/Filament/Resources/FixedAssetCategoryResource.php`
- `app/Filament/Resources/FixedAssetResource.php`
- strony Filament obu Resources;
- `tests/Feature/Finance/FixedAssetRegistryTest.php`.

## Testy akceptacyjne

- tenant A nie może zobaczyć, wybrać ani zmienić kategorii lub aktywa tenanta B;
- błędny subtype konta i cross-tenant account są odrzucane;
- kody kategorii i numery aktywów są unikalne w obrębie firmy, ale mogą powtarzać się między firmami;
- sekwencja `FA` jest monotoniczna i odporna na retry;
- aktywo kopiuje ustawienia kategorii jako snapshot;
- nieaktywna kategoria nie jest dostępna dla nowego aktywa;
- użytkownik z samym `view_finance` ma odczyt bez zapisu;
- użytkownik z `manage_finance` może zarządzać draftami;
- utworzenie i edycja draftu nie tworzy journal entry;
- rekord innego tenanta nie jest dostępny przez bezpośredni URL;
- `finance:audit-tenants` wykrywa niespójne referencje;
- PL/EN/PT mają identyczny zestaw kluczy.

## Ryzyka i zabezpieczenia

- Ryzyko podwójnego ujęcia zakupu jako koszt i aktywo: aktywacja i journal będą wyłącznie w UKFIN-702, z kontrolowanym powiązaniem do zakupu.
- Ryzyko błędnego konta: ścisły guard type/subtype i aktywności konta przed zapisem.
- Ryzyko mylenia amortyzacji książkowej z capital allowances: oddzielne nazewnictwo i brak automatyki podatkowej.
- Ryzyko zmian polityki po aktywacji: snapshot ustawień na aktywie, a późniejsze zmiany przez kontrolowany workflow.
- Ryzyko cross-tenant: global scope, Actions, policies, selektory i audit command sprawdzają `business_id` niezależnie.

## Definition of Done

- migracja przechodzi na SQLite i docelowym MySQL;
- modele i wszystkie referencje są tenantowe;
- panel jest objęty fail-closed RBAC;
- Actions są transakcyjne i testowane;
- brak księgowań oraz zmian raportów po utworzeniu draftu;
- testy Finance, Pint, Blade i tłumaczenia przechodzą;
- dokumentacja i live docs są aktualne.

## Wynik realizacji

- Dodano migrację `000037`, modele, factories, relacje i ochronę rekordów innych niż draft.
- Kategorie walidują trzy różne, aktywne i księgowalne konta własnego tenanta z wymaganym type i subtype.
- Aktywa otrzymują nieedytowalny numer `FA-YYYY-NNN` oraz snapshot polityki amortyzacji książkowej.
- Panel udostępnia osobne Resources dla kategorii i środków trwałych, z UI PL/EN oraz zgodnym zestawem kluczy PT.
- Fail-closed RBAC używa istniejących `view_finance` i `manage_finance`; bezpośrednie URL-e innego tenanta zwracają 404.
- `finance:audit-tenants` kontroluje własność aktywa, kategorię oraz wszystkie trzy referencje do planu kont.
- Drafty nie tworzą journal entries i nie zmieniają raportów finansowych ani VAT.
- Lokalna migracja MySQL przeszła jako batch 29; starsza, niezależna migracja `000033` pozostała oczekująca.
- Walidacja: Finance 396 passed, 1 skipped, 2871 assertions; kontrakt panelu i audytu 17 passed, 1201 assertions; Blade i Pint poprawne; EN/PL/PT po 1375 zgodnych kluczy.
