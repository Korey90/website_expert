# Architektura informacji panelu administracyjnego

**Status:** propozycja do walidacji w prototypie  
**Data:** 2026-11-13  
**Powiązany plan:** `docs/plans/admin-ui-redesign-plan.md`

## Cel

Zmniejszyć obciążenie poznawcze nawigacji bez usuwania funkcji i bez zmiany istniejącego modelu uprawnień. Menu ma odpowiadać procesom użytkownika, a nie technicznemu podziałowi klas Filament.

## Reguły

- maksymalnie siedem głównych grup;
- maksymalnie sześć bezpośrednich wejść w grupie;
- strony specjalistyczne są dostępne przez hub domeny zamiast jako kilkanaście równorzędnych linków;
- bieżący workspace jest zawsze widoczny w topbarze;
- aktywna grupa otwiera się automatycznie, pozostałe zachowują stan użytkownika;
- menu nadal filtruje elementy przez istniejące permissions;
- nazwy i opisy korzystają z tłumaczeń PL, EN i PT.

## Docelowe grupy

### 1. Start

Bezpośrednie wejścia:

- Pulpit;
- Powiadomienia.

Moje konto pozostaje w menu użytkownika w topbarze, nie jako równorzędna pozycja głównej nawigacji.

### 2. Sprzedaż

Bezpośrednie wejścia:

- Pipeline;
- Leady;
- Klienci;
- Briefingi;
- Oferty;
- Konfiguracja sprzedaży.

Hub „Konfiguracja sprzedaży” zawiera etapy pipeline, szablony briefingów i szablony ofert. Raport konwersji jest dostępny z Pipeline oraz huba sprzedaży.

### 3. Realizacja

Bezpośrednie wejścia:

- Projekty;
- Kalendarz;
- Domeny;
- Konfiguracja realizacji.

Huby zawierają odpowiednio zadania i fazy projektu, zdarzenia kalendarza, zamówienia domen i cennik TLD oraz szablony projektów.

### 4. Finanse i raporty

Bezpośrednie wejścia:

- Przegląd finansów;
- Sprzedaż;
- Zakupy;
- Bankowość;
- Księgowość i VAT;
- Raporty.

Mapowanie podsekcji:

| Podsekcja | Obecne strony |
|---|---|
| Sprzedaż | Invoices, Quotes, Contracts, Credit notes, Contract Templates, Payments |
| Zakupy | Suppliers, Expenses, Purchase credits, Recurring expenses |
| Bankowość | Bank accounts, Bank reconciliation |
| Księgowość i VAT | Chart of accounts, Accounting periods, VAT adjustments, Journals, VAT Return reviews, General ledger, HMRC Connection |
| Raporty | Trial balance, Expense report, Profit and loss, Balance sheet, Bank reconciliation report, Management cash flow, AR/AP ageing, VAT detail |

### 5. Strona i marketing

Bezpośrednie wejścia:

- Przegląd treści;
- Landing pages;
- Strony i sekcje;
- Komunikacja;
- Portfolio i usługi;
- SEO.

Mapowanie podsekcji:

| Podsekcja | Obecne strony |
|---|---|
| Przegląd treści | Navigation Menu |
| Landing pages | Landing Pages |
| Strony i sekcje | CMS Pages, Front-end Sections, Blog Articles |
| Komunikacja | Email Templates, SMS Templates |
| Portfolio i usługi | Services, Service Pages, Portfolio Projects |
| SEO | Google Rank Checker, Sitemap |

### 6. Automatyzacje

Bezpośrednie wejścia:

- Reguły;
- Triggery;
- Logi.

### 7. Administracja

Bezpośrednie wejścia:

- Użytkownicy i dostęp;
- Firmy i subskrypcje;
- Integracje;
- Ustawienia firmy;
- Bezpieczeństwo;
- Narzędzia systemowe.

Mapowanie podsekcji:

| Podsekcja | Obecne strony |
|---|---|
| Użytkownicy i dostęp | Users, Roles & Permissions, Permissions, Active Sessions |
| Firmy i subskrypcje | Plans, Businesses, Stripe Subscriptions |
| Integracje | Integrations, Payment Settings, Tracking & Analytics |
| Ustawienia firmy | Legal & Company, Calculator |
| Bezpieczeństwo | Security Events |
| Narzędzia systemowe | Log Viewer, Job Manager |

## Macierz ról

Widoczność nadal wynika z permissions; poniższa macierz określa oczekiwany profil, nie zastępuje RBAC.

| Grupa | Administrator | Manager | Księgowy |
|---|:---:|:---:|:---:|
| Start | pełna | pełna | pełna |
| Sprzedaż | pełna | według permissions | ukryta domyślnie |
| Realizacja | pełna | według permissions | ukryta domyślnie |
| Finanse i raporty | pełna | ograniczona | pełna według permissions |
| Strona i marketing | pełna | według permissions | ukryta domyślnie |
| Automatyzacje | pełna | według permissions | ukryta domyślnie |
| Administracja | pełna | bez narzędzi platformowych | ukryta poza profilem i sesjami |

## Topbar

Docelowa kolejność od lewej do prawej:

1. przycisk menu na małych ekranach;
2. globalne wyszukiwanie i paleta poleceń;
3. szybkie tworzenie rekordu;
4. zawsze widoczny aktywny workspace;
5. powiadomienia;
6. przypięte notatki, jeśli użytkownik ma dostęp do leadów;
7. menu użytkownika z profilem, motywem i wylogowaniem.

## Wzorce treści

### Pulpit

- zadania wymagające reakcji przed metrykami informacyjnymi;
- szybkie akcje dopasowane do uprawnień;
- maksymalnie jeden wykres w pierwszym ekranie;
- moduły SaaS widoczne tylko dla operatora platformy;
- widgety finansowe widoczne księgowemu, operacyjne managerowi.

### Lista

- nagłówek, główne CTA i alternatywny widok w jednym miejscu;
- najważniejsze 4–6 kolumn domyślnych;
- pozostałe kolumny dostępne w managerze kolumn;
- filtry aktywne pokazane jako czytelne chipy;
- mobilny widok kart lub jawnie oznaczone przewijanie poziome;
- pusty stan z opisem i akcją następnego kroku.

### Formularz

- sekcja podstawowa otwarta;
- dane dodatkowe w pełnoszerokich sekcjach zwijanych;
- jeden główny czasownik dla zapisu;
- pasek akcji pozostaje dostępny w długich formularzach;
- walidacja i tekst pomocy umieszczone przy polu.

### Widok szczegółowy

- tożsamość i status rekordu w nagłówku;
- najczęstsze akcje w nagłówku, pozostałe w menu;
- metryki i alerty przed historią;
- relacje w zakładkach lub logicznych sekcjach;
- brak kolorów i stylów zależnych tylko od ciemnego motywu.

## Elementy poza główną nawigacją

- My Account: menu użytkownika;
- ukryte zasoby kalkulatora: dostępne z huba Calculator;
- szczegółowe strony konfiguracji: linkowane z hubów domenowych;
- akcje techniczne i eksporty: pozostają akcjami kontekstowymi, nie pozycjami menu.

## Warunki akceptacji architektury

- użytkownik znajduje Pipeline, Projekty, Faktury i Użytkowników bez znajomości terminologii technicznej;
- żadna grupa nie przekracza sześciu bezpośrednich wejść;
- aktywny workspace jest widoczny również przy jednej firmie;
- role nie widzą pustych grup;
- wszystkie obecne funkcje mają wskazaną ścieżkę docelową;
- prototyp działa przy szerokości 390 i 1440 px.
