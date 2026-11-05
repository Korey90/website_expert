# Current Sprint

**Nazwa:** UK Finance & Tax — bank reconciliation
**Data rozpoczęcia:** 2026-11-04
**Status:** 🟡 IN PROGRESS
**Źródło:** `docs/plans/uk-finance-tax-module-plan.md`

## Zakres sprintu

1. Wykonane: tenantowe rachunki bankowe i bezpieczne maskowanie danych.
2. Wykonane: importer CSV z mapowaniem kolumn, limitami i dry-run.
3. Wykonane: `BankTransaction`, audyt partii oraz deduplikacja provider ID/SHA-256.
4. Następnie: reguły dopasowania referencja/kwota/data/kontrahent (`UKFIN-404`).
5. Następnie: UI uzgodnień exact/partial/split/many-to-many (`UKFIN-405`).

## Definition of Done

- Wszystkie rekordy finansowe mają jednoznacznego tenanta lub są raportowane przez deploy gate.
- Zapytania użytkownika nie zwracają danych innej firmy.
- UTR/PAYE nie są przechowywane jawnie.
- Profil księgowy jest edytowalny tylko przez aktywnego owner/admin, bez ujawniania zapisanych sekretów w UI.
- Faktura walutowa przechowuje niezmienny kurs, źródło, datę oraz VAT w GBP; brak kursu nie jest zastępowany wartością domyślną.
- Numery invoice/quote są przydzielane transakcyjnie i blokowane na poziomie sekwencji tenanta.
- Credit notes nie mogą przekroczyć ilości z faktury, a zaalokowane korekty trwale obniżają saldo.
- Domyślne tax codes są idempotentne, wersjonowane datą i tłumaczone PL/EN/PT.
- Dry-run importu bankowego nie zmienia stanu domeny, a import właściwy nie przechowuje raw CSV.
- Duplikaty transakcji są odrzucane po provider ID lub stabilnym hashu również przy równoległym imporcie.
- Testy domenowe i pełna regresja nie dodają nowych błędów względem baseline.
- Dokumentacja wdrożenia zawiera kolejność migrate → audit → smoke tests.

## Poza zakresem tego sprintu

- Produkcyjna integracja HMRC MTD VAT.
- Payroll/PAYE, Corporation Tax, Self Assessment i CIS.
- Automatyczne składanie jakichkolwiek deklaracji.
