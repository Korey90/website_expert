# Current Task

**Status:** Brak aktywnego zadania
**Ostatnio zakończone:** UKFIN-508 — VAT adjustments z reason, attachment i approval (2026-11-10)
**Następne zadanie:** UKFIN-509 — VAT return review/lock/snapshot workflow
**Plan nadrzędny:** `docs/plans/uk-finance-tax-module-plan.md`

## Stan przekazania

- `/admin/vat-adjustments` obsługuje szkic, edycję, prywatny dowód, akceptację i atomowe księgowanie korekty VAT.
- Normalne korekty i błędy poprzednich deklaracji są rozdzielone; deliberate i separate notification nie trafiają do bieżącego VAT Return.
- Błędy current-return są oceniane łącznie netto według progów £10,000, 1% box 6 i £50,000.
- Migracja `2026_11_10_000024_create_vat_adjustments_table` została wykonana na lokalnym MySQL.
- Finance: 259 passed / 1 skipped / 1839 assertions.
- Pełny pakiet: 768 passed / 9 znanych baseline failures / 11 skipped / 3279 assertions; po pełnym przebiegu wzmocniono wyłącznie test progu 1% o 4 dodatkowe asercje.
- Tenant audit: `valid: true`, zero nieprawidłowości; PL/EN/PT: po 909 identycznych kluczy `finance.php`.

## Następny krok

Rozpocząć UKFIN-509 od modelu niezmiennego VAT Return snapshotu: review, lock okresu, utrwalenie mapowań tax codes i źródłowych journal lines, deklaracja użytkownika oraz kontrolowany unlock/replacement bez wysyłki do HMRC.
