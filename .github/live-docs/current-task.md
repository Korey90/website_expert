# Current Task

**Status:** Brak aktywnego zadania
**Ostatnio zakończone:** UKFIN-509 — VAT return review/lock/snapshot workflow (2026-11-10)
**Następne zadanie:** UKFIN-510 — Accountant pack ZIP
**Plan nadrzędny:** `docs/plans/uk-finance-tax-module-plan.md`

## Stan przekazania

- `/admin/vat-detail` pozwala zapisać gotową kalkulację jako niezmienny snapshot do wewnętrznej weryfikacji; `/admin/vat-returns` obsługuje review, lock i supersede.
- Workflow `draft → reviewed → locked` rozdziela `manage_finance`, `approve_finance` i `close_finance_periods`; draft/reviewed można zastąpić wyłącznie z powodem, locked jest nieodwracalny.
- Review i lock ponownie wyliczają SHA-256 aktualnego ledgeru/profilu/mapowań; drift wymusza supersede i nowy snapshot.
- Locked VAT period blokuje nowe journale w swoim zakresie, ale zachowuje bezpieczne retry istniejącego idempotency key oraz pozwala księgować w późniejszym okresie.
- Snapshot zawiera 9 pól, checks, warnings, tax-code mappings oraz source journal/line IDs; jawny VAT number nie jest zapisywany (tylko ostatnie 4 znaki i SHA-256).
- Migracja `2026_11_10_000025_create_vat_returns_table` została wykonana na lokalnym MySQL.
- Dedykowane: 5 passed / 44 assertions; Finance: 264 passed / 1 skipped / 1883 assertions.
- Pełny pakiet: 773 passed / 9 znanych baseline failures / 11 skipped / 3327 assertions.
- Tenant audit: zero nieprawidłowości; PL/EN/PT: po 985 identycznych kluczy `finance.php`.
- Równoległe zmiany użytkownika w `resources/js/Pages/Landing/PlStronyInternetowe.tsx` i `package*.json` pozostały nietknięte.

## Następny krok

Rozpocząć UKFIN-510 od kontraktu pakietu księgowego ZIP: zakres CSV/XLSX/PDF, manifest załączników, hashe integralności, redakcja danych wrażliwych i spójność z locked VAT Return snapshotem.
