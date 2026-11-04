# Current Sprint

**Nazwa:** UK Finance & Tax — foundation
**Data rozpoczęcia:** 2026-11-04
**Status:** 🟡 IN PROGRESS
**Źródło:** `docs/plans/uk-finance-tax-module-plan.md`

## Zakres sprintu

1. Faza 0: izolacja tenantów, audyt danych, uprawnienia i threat model.
2. Początek Fazy 1: Accounting Profile i wersjonowane UK Tax Codes.
3. Wykonane: tax snapshots na pozycjach oraz kalkulacja mixed VAT.
4. Wykonane: UI Accounting Profile, walidacja zależna i bezpieczna edycja identyfikatorów podatkowych.
5. Wykonane: supply date/tax point oraz snapshoty GBP/FX.
6. Wykonane: bezpieczna numeracja invoice/quote per tenant, typ i rok.
7. Wykonane: credit notes, snapshoty z faktury i kontrolowany lifecycle.
8. Następnie: panel/PDF credit notes i aktualizacja kompletnego PDF invoice.

## Definition of Done

- Wszystkie rekordy finansowe mają jednoznacznego tenanta lub są raportowane przez deploy gate.
- Zapytania użytkownika nie zwracają danych innej firmy.
- UTR/PAYE nie są przechowywane jawnie.
- Profil księgowy jest edytowalny tylko przez aktywnego owner/admin, bez ujawniania zapisanych sekretów w UI.
- Faktura walutowa przechowuje niezmienny kurs, źródło, datę oraz VAT w GBP; brak kursu nie jest zastępowany wartością domyślną.
- Numery invoice/quote są przydzielane transakcyjnie i blokowane na poziomie sekwencji tenanta.
- Credit notes nie mogą przekroczyć ilości z faktury, a zaalokowane korekty trwale obniżają saldo.
- Domyślne tax codes są idempotentne, wersjonowane datą i tłumaczone PL/EN/PT.
- Testy domenowe i pełna regresja nie dodają nowych błędów względem baseline.
- Dokumentacja wdrożenia zawiera kolejność migrate → audit → smoke tests.

## Poza zakresem tego sprintu

- Produkcyjna integracja HMRC MTD VAT.
- Payroll/PAYE, Corporation Tax, Self Assessment i CIS.
- Automatyczne składanie jakichkolwiek deklaracji.
