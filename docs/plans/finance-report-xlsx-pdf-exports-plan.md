# UKFIN-512 - Eksport raportów finansowych do XLSX i PDF

**Status:** Zrealizowany lokalnie, gotowy do wdrożenia
**Data realizacji:** 2026-11-22

## Zakres

Rozszerzyć następujące raporty o eksport Excel XLSX i PDF przy zachowaniu dotychczasowego CSV:

- Trial Balance;
- Expense Report;
- Profit and Loss;
- Balance Sheet;
- Bank Reconciliation Report;
- Management Cash Flow;
- Aged Receivables and Payables;
- VAT Detail.

## Założenia wykonawcze

1. Każdy format korzysta z tego samego serwisu raportowego i tych samych filtrów.
2. Istniejący układ i trasy CSV pozostają kompatybilne.
3. XLSX zawiera prawdziwe komórki liczbowe, formaty kwot, nagłówki, podsumowania i ustawienia wydruku.
4. Tekst w XLSX jest zapisywany jawnie jako tekst, a nie formuła arkusza.
5. PDF zawiera nazwę firmy, czas wygenerowania, parametry raportu, tabele i podsumowania.
6. Szerokie raporty używają poziomego A3, a pozostałe poziomego A4.
7. Wszystkie odpowiedzi są prywatne, mają `no-store` i wymagają `view_finance`.
8. Walidacja tenantów i zakresów pozostaje realizowana przez istniejące Form Request i serwisy raportowe.

## Wynik

- Dodano wspólny `FinanceReportDocumentFactory`, który mapuje osiem raportów na jeden kontrolowany model dokumentu.
- Dodano wspólny `FinanceReportExportService` generujący XLSX przez PhpSpreadsheet i PDF przez Dompdf.
- Dodano 16 nowych tras: po jednej XLSX i PDF dla każdego raportu. Osiem istniejących tras CSV pozostaje bez zmian.
- Każda strona raportu pokazuje trzy akcje: CSV, Excel i PDF.
- XLSX ma tytuł, dane firmy, parametry, typowane komórki, formaty kwot, podsumowania, freeze pane i ustawienia wydruku.
- PDF ma spójny układ księgowy, dane firmy, parametry, podsumowania i obsługę pustych zestawów.
- Dodano tłumaczenia PL, EN i PT oraz zaktualizowano instrukcję użytkownika.
- Test przekrojowy generuje i otwiera wszystkie osiem plików XLSX, sprawdza wszystkie osiem PDF oraz ochronę uprawnień.
- Regresja dziewięciu klas raportowych: 46 testów, 605 asercji, bez błędów.
- Pełna regresja Finance: 387 testów zaliczonych, 1 pominięty zgodnie z ograniczeniem SQLite, 2815 asercji.

## Wdrożenie

Zmiana nie wymaga migracji bazy ani nowych pakietów Composer. Po wdrożeniu należy odbudować cache aplikacji i wykonać smoke test pobrania CSV, XLSX oraz PDF z jednego raportu okresowego i jednego raportu opartego na zakresie dat.
