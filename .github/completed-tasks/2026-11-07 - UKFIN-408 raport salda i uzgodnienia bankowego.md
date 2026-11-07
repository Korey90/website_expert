# 2026-11-07 - UKFIN-408 raport salda i uzgodnienia bankowego

**Status:** Zakończone sukcesem  
**Data realizacji:** 2026-11-07  
**Czas trwania:** jeden przyrost roboczy

## Cel zadania

Udostępnić sprawdzalny, tenantowy raport salda rachunków bankowych oraz stanu ich uzgodnienia, z widokiem szczegółów i eksportem CSV.

## Zakres wykonanych prac

- Dodano `BankReconciliationReportService` z walidacją okresu i tenantowego filtra rachunku.
- Saldo otwarcia wynika z `opening_balance` oraz transakcji od daty bazowej do początku okresu.
- Per rachunek raport pokazuje wpływy, wypływy, saldo końcowe, aktywnie uzgodnioną kwotę, resztę i procent pokrycia.
- Szczegóły pokazują datę, rachunek, referencję, kontrahenta, kwotę, alokację, resztę i przeliczony status.
- Wyłącznie alokacje batchy `active` zmniejszają resztę; batch `reversed` pozostaje w audycie, ale nie wpływa na pokrycie.
- Waluty rachunków nie są sumowane; każda pozostaje w natywnej walucie.
- Dodano stronę Filament z filtrem okresu i rachunku oraz linkami do transakcji bankowych.
- Dodano dwusekcyjny eksport CSV: podsumowanie rachunków i szczegóły transakcji.
- Form Request i permission registry wymagają `view_finance` i ograniczają rachunek do bieżącego tenanta.
- Dodano komplet tłumaczeń PL/EN/PT.

## Główne pliki

- `app/Services/Finance/BankReconciliationReportService.php`
- `app/Filament/Pages/BankReconciliationReportPage.php`
- `resources/views/filament/pages/bank-reconciliation-report.blade.php`
- `app/Http/Controllers/Finance/BankReconciliationReportExportController.php`
- `app/Http/Requests/Finance/BankReconciliationReportRequest.php`
- `tests/Feature/Finance/BankReconciliationReportTest.php`
- `lang/{pl,en,pt}/finance.php`

## Walidacja końcowa

- ✅ Laravel Pint dla wszystkich zmienionych plików PHP.
- ✅ Tłumaczenia: 555/555/555 kluczy liściowych PL/EN/PT.
- ✅ Dedykowane testy: 5 passed / 34 assertions.
- ✅ Regresja UKFIN-404–408: 33 passed / 206 assertions.
- ✅ Finance regression: 209 passed / 1 skipped / 1285 assertions.
- ✅ Full PHPUnit: 718 passed / 9 znanych baseline failures / 11 skipped / 2729 assertions.
- ✅ `git diff --check` bez błędów.

## Granice bieżącego etapu

- Raport prezentuje saldo wynikające z opening balance i importowanych transakcji; porównanie banku z General Ledger należy do raportów księgowych fazy 5.
- Nie jest tworzona wspólna suma pieniężna dla rachunków w różnych walutach.
- Wydajność i skrajne scenariusze całej fazy zostaną dodatkowo objęte przez UKFIN-409.

## Wdrożenie

1. Nie ma nowej migracji dla UKFIN-408.
2. Nadać użytkownikowi `view_finance` i otworzyć `/admin/bank-reconciliation-report`.
3. Sprawdzić rachunek GBP i walutowy oraz pobrać CSV dla wybranego okresu.

## Next steps

- UKFIN-409 — testy duplicates, split, FX, reversal i tenant isolation.

**Raport wygenerowany automatycznie przez Website Expert NI**
