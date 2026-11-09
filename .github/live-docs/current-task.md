# Current Task

**Status:** Brak aktywnego zadania
**Ostatnio zakończone:** UKFIN-503 — Balance Sheet (2026-11-09)
**Następny planowany etap:** UKFIN-504 — Cash Flow w wersji zarządczej
**Plan nadrzędny:** `docs/plans/uk-finance-tax-module-plan.md`

## Wynik ostatniego etapu

- Tenantowy bilans w GBP na koniec wybranego okresu księgowego.
- Aktywa, zobowiązania i kapitał z ledgeru `posted`/`reversed` wraz z jawną różnicą kontrolną.
- Wynik sprzed okresu i wynik bieżący ujęte jako niezamknięty wynik w kapitale własnym.
- Opcjonalny okres porównawczy i wariancja per konto, sekcja oraz suma.
- Konta korygujące i historycznie nieaktywne pozostają widoczne z właściwym znakiem.
- Strona Filament i prywatny CSV chronione przez `view_finance` oraz tenant validation.
- Tłumaczenia PL/EN/PT i testy obliczeń, reversal, RBAC, izolacji tenantów, strony oraz eksportu.

## Walidacja

- Dedykowane testy UKFIN-503: 6 passed / 61 assertions.
- Balance Sheet + P&L + Trial Balance + General Ledger: 25 passed / 207 assertions.
- Finance regression: 230 passed / 1 skipped / 1481 assertions.
- Pełna regresja: 739 passed / 9 znanych baseline failures / 11 skipped / 2925 assertions.
- `finance:audit-tenants --json`: `valid: true`, zero nieprawidłowości.
- Pint, self-review, parity tłumaczeń 673/673/673, skan nazw firmy i `git diff --check`: bez nowych problemów.

## Następny krok

- [ ] `UKFIN-504` — Cash Flow w wersji zarządczej.
