# 2026-11-10 - UKFIN-510 Accountant pack ZIP

**Status:** Zakończone sukcesem
**Data realizacji:** 2026-11-10
**Czas trwania:** około 1 godziny w bieżącej sesji implementacyjnej

## Cel zadania

Udostępnić bezpieczny pakiet dla księgowego UK generowany bezpośrednio z zablokowanego VAT Return snapshotu. ZIP ma umożliwiać niezależny przegląd wartości, źródeł i dowodów, lecz nie jest submission do HMRC.

## Zakres wykonanych prac

- Dodano `GenerateAccountantPackAction`, który przyjmuje wyłącznie locked VAT Return, sprawdza aktywny tenant membership i `view_finance` oraz weryfikuje SHA-256 snapshotu przed generowaniem.
- Wszystkie liczby VAT pochodzą z zapisanego snapshotu; generator nie uruchamia ponownie bieżącej kalkulacji VAT.
- Pakiet zawiera angielski README, PDF podsumowania, XLSX z sześcioma arkuszami, CSV dla dziewięciu pól, VAT detail i source journals oraz manifest załączników.
- Źródłowe dowody są wyszukiwane przez polymorphic source journals. Tylko status `clean` trafia do ZIP po ponownym odczycie z prywatnego storage i zgodności SHA-256 oraz rozmiaru; `pending`/`rejected` pozostają w manifeście jako excluded.
- Dodano `manifest.sha256.json` z klasyfikacją, biznesem, VAT Return, snapshot hashem, aktorem, czasem generacji oraz rozmiarem i hashem każdego pozostałego wpisu ZIP.
- Pełny numer VAT nie trafia do eksportu; nazwy wpisów są sanityzowane, storage paths ukryte, a wartości CSV/XLSX zabezpieczone przed formula injection.
- Tymczasowy staging jest zawsze usuwany. ZIP ma losową nazwę storage i jest kasowany przez odpowiedź HTTP po wysłaniu.
- Dodano cienki kontroler z Gate/policy, nagłówkami private/no-store i hashami kontrolnymi oraz trasę pobrania.
- Na widoku locked VAT Return dodano akcję pobrania pakietu; nie jest widoczna dla innych statusów lub bez uprawnienia.
- Dodano limity liczby i łącznego rozmiaru załączników oraz komplet komunikatów i etykiet PL/EN/PT.
- Nie dodano tabeli ani migracji: pakiet jest efemerycznym, deterministycznym eksportem istniejącego audytowalnego rekordu.
- Zachowano bez zmian równoległą pracę użytkownika w `resources/js/Pages/Landing/PlStronyInternetowe.tsx` oraz `package*.json`.

## Użyte agenty i skille

- @BackendEngineer
- @DatabaseEngineer
- @TestingEngineer
- Skill: delta-analysis
- Skill: database-migration
- Skill: filament-resource
- Skill: test-generation
- Skill: multi-language-check
- Skill: task-completion-report

## Zmodyfikowane / utworzone pliki

- `app/Actions/Finance/GenerateAccountantPackAction.php`
- `app/Http/Controllers/Finance/AccountantPackController.php`
- `app/Filament/Resources/VatReturnResource/Pages/ViewVatReturn.php`
- `resources/views/pdf/accountant-pack.blade.php`
- `routes/web/admin.php`
- `config/finance.php`
- `lang/{en,pl,pt}/finance.php`
- `tests/Feature/Finance/AccountantPackTest.php`
- `docs/plans/uk-finance-tax-module-plan.md`
- `.github/live-docs/{current-task,current-sprint,status-dashboard}.md`

## Walidacja końcowa

- ✅ PHP Coding Standard: Laravel Pint dla wszystkich plików PHP UKFIN-510.
- ➖ JavaScript / TypeScript: brak zmian JS/TS; równolegle edytowane pliki użytkownika pozostawiono bez ingerencji.
- ✅ Multi-language check: po 1064 identyczne klucze liściowe `finance.php` w PL/EN/PT; 0 missing i 0 extra.
- ✅ Multi-tenancy i RBAC: action, policy/Gate, route model binding oraz testy statusu, membership i permission.
- ✅ Bezpieczeństwo eksportu: redakcja VAT number, clean-only evidence, ponowna weryfikacja SHA-256/bytes, formula injection guard, bezpieczne nazwy, brak storage paths, private/no-store i automatyczne cleanup.
- ✅ Test dedykowany: 3 passed / 70 assertions.
- ✅ Finance regression: 267 passed / 1 skipped / 1953 assertions.
- ✅ Full regression: 776 passed / 11 skipped / 3397 assertions; pozostało dokładnie 9 znanych baseline failures niezwiązanych z finansami.
- ✅ `git diff --check` i rejestracja trasy eksportu bez błędów.

## Uwagi i rekomendacje

- Pakiet jest materiałem do wewnętrznego review księgowego i jest jawnie oznaczony jako `NOT SUBMITTED TO HMRC`.
- Manifest nie hashuje samego siebie, ponieważ samoreferencyjny SHA-256 jest niemożliwy; pole `manifest_scope` dokumentuje, że obejmuje wszystkie pozostałe wpisy ZIP.
- Generator świadomie nie utrwala historii pobrań w nowej tabeli. Audit lineage znajduje się w locked VAT Return oraz wewnątrz manifestu pakietu.
- Znane baseline failures dotyczą SocialAuth, meta strony głównej, waluty Billing, starych tras Profile oraz jednego testu Inertia ServicePage; UKFIN-510 nie dodał nowych regresji.

## Next steps (opcjonalnie)

- UKFIN-511: wersjonowany golden dataset, automatyczne expected-vs-actual i formalny review prawdziwego księgowego UK.
- Po uzyskaniu akceptacji księgowego zachować podpisaną wersję checklisty i hash zaakceptowanego datasetu jako release gate fazy 5.

**Raport wygenerowany automatycznie przez WebsiteExpert**
