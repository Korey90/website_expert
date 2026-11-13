# Plan przebudowy interfejsu panelu administracyjnego

**Status:** planowanie  
**Zakres:** wszystkie widoki pod `/admin/*`  
**Data utworzenia:** 2026-11-13  
**Ostatnia aktualizacja:** 2026-11-13  
**Właściciel dokumentu:** zespół projektu Website Expert  
**Źródło prawdy:** ten dokument

## Cel

Przebudować interfejs panelu administracyjnego tak, aby był intuicyjny, spójny, responsywny i dostosowany do roli użytkownika, bez naruszania istniejącej logiki biznesowej, uprawnień ani izolacji tenantów.

Dokument obejmuje drogę od architektury informacji i prototypów, przez wdrożenie wspólnych wzorców UI, po migrację wszystkich modułów i kontrolowane wdrożenie produkcyjne.

## Zasady utrzymania dokumentu

- Dokument należy aktualizować po zakończeniu każdego etapu lub po zmianie zakresu.
- Każda istotna decyzja projektowa trafia do sekcji „Decyzje”.
- Postęp jest oznaczany w checklistach etapów.
- Nowo odkryte problemy trafiają do backlogu, bez rozszerzania aktywnego etapu bez uzgodnienia.
- Przy każdej aktualizacji należy zmienić pole „Ostatnia aktualizacja” i dopisać wpis do dziennika zmian.
- Dokument opisuje stan faktyczny; ukończenia nie oznaczamy bez weryfikacji i testów.

## Stan wyjściowy

Audyt wykonano na lokalnym panelu `http://127.0.0.1:8000/admin/*`, w kodzie aplikacji oraz na reprezentatywnych widokach desktopowych i mobilnych.

Aktualny zakres panelu:

- 56 zasobów Filament;
- 24 strony Filament;
- 17 widgetów;
- 227 tras `/admin/*`;
- 71 widocznych linków w nawigacji pełnego administratora;
- 11 nazwanych grup menu oraz Dashboard i My Account poza grupami.

Główne pliki sterujące interfejsem:

- `app/Providers/Filament/AdminPanelProvider.php`;
- `resources/css/filament/admin/theme.css`;
- `resources/views/filament/components/business-switcher.blade.php`;
- `resources/views/filament/components/pinned-notes.blade.php`;
- `app/Filament/Pages/AdminDashboard.php`;
- `app/Filament/Resources/*`;
- `app/Filament/Pages/*`;
- `app/Filament/Widgets/*`.

## Wyniki audytu

### Menu boczne

- Grupa Finance zawiera 19 pozycji na jednym poziomie.
- Grupy SEO i System są automatycznie wstawiane poza kolejnością zdefiniowaną w providerze panelu.
- Szablony ofert i briefingów są oddzielone od procesów, w których są używane.
- Notifications znajduje się w raportach.
- Calculator, Sitemap, Log Viewer i Job Manager są połączone w jednej grupie Settings mimo różnych zastosowań.
- Po wejściu na element położony nisko sidebar przewija się automatycznie i ukrywa główne punkty orientacyjne.
- Na telefonie menu działa jako drawer, ale nadal wymaga bardzo długiego przewijania.

### Górna belka

- Brakuje globalnego wyszukiwania stron i rekordów.
- Aktywny workspace jest widoczny tylko wtedy, gdy użytkownik ma dostęp do więcej niż jednej firmy.
- Powiadomienia i przypięte notatki są reprezentowane podobnymi ikonami bez wystarczającego opisu.
- Przycisk przypiętych notatek nie ma pełnego opisu dla technologii asystujących.
- Akcja odpięcia notatki jest dostępna wizualnie wyłącznie po najechaniu kursorem.
- Część tekstów jest wpisana na stałe po angielsku.

### Dashboard

- Dashboard łączy operacje CRM, projekty, finanse i metryki SaaS.
- Zawiera 10 widgetów, przez co jest długi, zwłaszcza na telefonie.
- Puste wykresy i tabele zajmują dużo miejsca.
- Podczas ładowania niektórych widgetów pojawiają się duże puste karty.
- Quick Actions są użyteczne i powinny pozostać elementem docelowego rozwiązania.

### Listy, formularze i widoki procesów

- Bazowe komponenty Filament dają spójny fundament, ale nie istnieje wystarczająca warstwa wspólnych reguł projektu.
- Lista leadów ma 10 kolumn, a przewijanie poziome na telefonie nie jest jasno komunikowane.
- Pipeline ma sześć kolumn i część zawartości jest ukryta również na desktopie.
- Formularz tworzenia leada jest responsywny, ale zwinięta sekcja Calculator Data tworzy pustą kolumnę na desktopie.
- Nazewnictwo stron i akcji jest niespójne, np. `New lead`, `Create Lead`, `Navigation Menu` i `Navbar Settings Page`.
- Część raportów ma puste stany bez wyjaśnienia następnego kroku lub odpowiedniego CTA.
- Niestandardowe widoki projektów używają stylów inline i kolorów projektowanych głównie dla ciemnego motywu.
- Teksty UI nie są konsekwentnie obsługiwane przez tłumaczenia PL, EN i PT.

## Założenia projektowe

- Codzienne funkcje powinny być oddzielone od konfiguracji i narzędzi operatorskich.
- Widoczność menu pozostaje zależna od uprawnień.
- Najczęstsze zadania powinny być osiągalne w maksymalnie dwóch krokach nawigacyjnych.
- Jedna grupa menu nie powinna zawierać więcej niż 6–7 bezpośrednich pozycji.
- Desktop i mobile korzystają z tej samej architektury informacji.
- Wszystkie nowe lub zmodyfikowane teksty wymagają wersji PL, EN i PT.
- Warstwa UI nie może osłabić istniejących zabezpieczeń RBAC i tenant isolation.
- Działających modułów nie przepisujemy od zera; wdrożenie jest prowadzone metodą delta-first.

## Proponowana architektura informacji

Poniższy układ jest hipotezą do zweryfikowania w prototypie.

### Start

- Dashboard;
- Moje konto.

### Sprzedaż

- Klienci;
- Leady;
- Pipeline;
- Briefingi;
- Oferty;
- szablony używane w procesie sprzedaży.

### Realizacja

- Projekty;
- zadania i fazy;
- szablony projektów;
- kalendarz.

### Finanse

- sprzedaż: faktury, wyceny, kontrakty i płatności;
- zakupy: dostawcy, wydatki, korekty i wydatki cykliczne;
- bankowość: konta i uzgadnianie transakcji;
- księgowość i VAT: plan kont, okresy, dzienniki, VAT oraz HMRC.

### Raporty

- raporty operacyjne;
- raporty finansowe;
- raporty podatkowe.

### Strona i marketing

- nawigacja strony;
- landing pages i CMS;
- usługi, portfolio i sekcje strony;
- komunikacja e-mail i SMS;
- SEO.

### Automatyzacje

- reguły;
- triggery;
- logi.

### Administracja

- użytkownicy, role i sesje;
- integracje oraz ustawienia płatności;
- rozliczenia SaaS;
- bezpieczeństwo;
- narzędzia systemowe.

Sekcja Administracja powinna być domyślnie zwinięta i dostępna wyłącznie dla właściwych ról.

## Plan realizacji

### Etap 1 — architektura informacji

**Szacunek:** 1–2 dni  
**Status:** do wykonania

- [ ] Przypisać wszystkie elementy menu do procesów użytkownika.
- [ ] Rozdzielić funkcje codzienne, konfiguracyjne i operatorskie.
- [ ] Przygotować warianty menu dla administratora, managera i księgowego.
- [ ] Ujednolicić nazwy stron, zasobów i akcji.
- [ ] Przygotować klucze tłumaczeń dla wspólnej nawigacji.
- [ ] Zatwierdzić docelową mapę panelu przed pracą wizualną.

**Rezultat:** zatwierdzona architektura informacji i macierz widoczności według ról.

### Etap 2 — prototypy UX/UI

**Szacunek:** 3–4 dni  
**Status:** do wykonania

- [ ] Przygotować prototyp niskiej wierności menu i topbara na desktopie.
- [ ] Przygotować prototyp mobilnego drawera i topbara.
- [ ] Zaprojektować dashboard zależny od roli.
- [ ] Zaprojektować wzorzec listy rekordów.
- [ ] Zaprojektować wzorzec formularza tworzenia i edycji.
- [ ] Zaprojektować widok szczegółowy rekordu.
- [ ] Zaprojektować pipeline i świadome przewijanie poziome.
- [ ] Zaprojektować raporty oraz stany puste, ładowania, błędów i sukcesu.
- [ ] Przygotować wersję wizualną po zatwierdzeniu układu.
- [ ] Zatwierdzić prototyp przed wdrożeniem fundamentu.

**Rezultat:** zaakceptowany prototyp desktopowy i mobilny dla wszystkich głównych archetypów stron.

### Etap 3 — fundament techniczny

**Szacunek:** 3–5 dni  
**Status:** do wykonania

- [ ] Zdefiniować tokeny kolorów, odstępów, promieni i typografii w motywie Filament.
- [ ] Wdrożyć docelowe grupowanie i kolejność menu.
- [ ] Dodać widoczny kontekst workspace’u.
- [ ] Zaprojektować globalne wyszukiwanie i szybkie akcje.
- [ ] Ujednolicić powiadomienia i przypięte notatki.
- [ ] Utworzyć wspólne reguły dla nagłówków, toolbarów, formularzy, tabel i pustych stanów.
- [ ] Usunąć style inline z komponentów objętych fundamentem.
- [ ] Wdrożyć tłumaczenia PL, EN i PT.
- [ ] Dodać testy wspólnych elementów i nawigacji według ról.

**Rezultat:** wspólna powłoka panelu i zestaw stabilnych wzorców UI.

### Etap 4 — pilotaż CRM i projektów

**Szacunek:** 5–7 dni  
**Status:** do wykonania

- [ ] Dashboard.
- [ ] Klienci.
- [ ] Leady.
- [ ] Pipeline.
- [ ] Projekty.
- [ ] Widoki desktopowe i mobilne.
- [ ] Test scenariuszy użytkownika po wdrożeniu pilotażu.
- [ ] Korekta wzorców przed migracją pozostałych modułów.

**Rezultat:** zweryfikowany wzorzec możliwy do bezpiecznego powielenia.

### Etap 5 — migracja pozostałych modułów

**Szacunek:** 10–15 dni  
**Status:** do wykonania

#### Fala 1 — finanse i raporty

- [ ] Dokumenty sprzedażowe.
- [ ] Zakupy i wydatki.
- [ ] Bankowość.
- [ ] Księgowość, VAT i HMRC.
- [ ] Raporty finansowe.

#### Fala 2 — marketing, CMS i SEO

- [ ] Nawigacja strony.
- [ ] Landing pages i CMS.
- [ ] Treści, usługi i portfolio.
- [ ] E-mail i SMS.
- [ ] SEO.

#### Fala 3 — automatyzacje

- [ ] Reguły.
- [ ] Triggery.
- [ ] Logi.

#### Fala 4 — administracja i narzędzia systemowe

- [ ] Użytkownicy, role i sesje.
- [ ] SaaS Billing.
- [ ] Integracje i ustawienia.
- [ ] Bezpieczeństwo.
- [ ] Log Viewer, Job Manager i pozostałe narzędzia operatorskie.

**Rezultat:** wszystkie strony `/admin/*` korzystają ze wspólnej architektury i standardów interfejsu.

### Etap 6 — walidacja i wdrożenie

**Szacunek:** 3–5 dni  
**Status:** do wykonania

- [ ] Sprawdzić szerokości 390, 768, 1280 i 1440 px.
- [ ] Sprawdzić jasny i ciemny motyw.
- [ ] Sprawdzić obsługę klawiatury, focus, kontrast i ARIA.
- [ ] Zweryfikować wszystkie teksty PL, EN i PT.
- [ ] Uruchomić testy ról, uprawnień i izolacji tenantów.
- [ ] Uruchomić testy Filament/PHPUnit oraz produkcyjny build.
- [ ] Przeprowadzić smoke test najważniejszych procesów.
- [ ] Wdrożyć etapowo z możliwością szybkiego wycofania warstwy UI.
- [ ] Zebrać uwagi po wdrożeniu i zamknąć backlog krytyczny.

**Rezultat:** zweryfikowany i wdrożony panel bez regresji funkcjonalnych oraz bezpieczeństwa.

## Kryteria odbioru

- Maksymalnie 7 głównych grup menu.
- Maksymalnie 7 bezpośrednich pozycji w rozwiniętej grupie.
- Najczęstsze zadania osiągalne w najwyżej dwóch krokach nawigacyjnych.
- Brak niekontrolowanego obcinania lub przepełnienia strony przy szerokości 390 px.
- Lokalne przewijanie tabel i pipeline’u ma widoczny sygnał oraz nie blokuje obsługi klawiaturą.
- Brak akcji dostępnych wyłącznie przez hover.
- Aktywny workspace jest zawsze czytelny.
- Wszystkie nowe i zmodyfikowane teksty mają tłumaczenia PL, EN i PT.
- Stany puste, ładowania, błędów i sukcesu są spójne i wskazują następny krok.
- Komponenty niestandardowe działają w jasnym i ciemnym motywie.
- Istniejące testy uprawnień i tenant isolation przechodzą bez regresji.

## Ryzyka

- Duży zakres może prowadzić do niespójności, jeśli moduły będą migrowane równolegle bez zatwierdzonego fundamentu.
- Zmiana nawigacji może utrudnić pracę dotychczasowym użytkownikom; potrzebne jest wdrożenie etapowe i czytelne nazewnictwo.
- Moduły finansowe i HMRC wymagają szczególnej ostrożności oraz zachowania obecnych bramek bezpieczeństwa.
- Niestandardowe widoki Blade mogą wymagać większego nakładu niż standardowe Resources Filament.
- Migracja tekstów do tłumaczeń może ujawnić istniejące różnice znaczeniowe pomiędzy modułami.

## Decyzje

| Data | Decyzja | Uzasadnienie |
|---|---|---|
| 2026-11-13 | Najpierw architektura informacji i prototyp, później wdrożenie | Głównym problemem jest struktura panelu, a nie tylko jego warstwa wizualna. |
| 2026-11-13 | Wdrożenie modułami, zaczynając od CRM i projektów | Pozwala zweryfikować wzorce na najczęściej używanych procesach przed migracją całego panelu. |
| 2026-11-13 | Zachować Filament jako fundament | Obecna baza komponentów jest stabilna; przebudowa od zera zwiększyłaby ryzyko i koszt. |

## Backlog odkryć

- [ ] Ustalić, czy globalne wyszukiwanie ma obejmować tylko strony, czy również rekordy domenowe.
- [ ] Ustalić priorytet domyślnego dashboardu dla każdej roli.
- [ ] Zdecydować, czy szablony pozostają przy procesach, czy trafiają do wspólnej sekcji konfiguracji.
- [ ] Zweryfikować potrzebę oddzielnego trybu operatorskiego dla SaaS, bezpieczeństwa i narzędzi systemowych.
- [ ] Ustalić sposób komunikowania poziomego przewijania pipeline’u i szerokich tabel.

## Dziennik zmian

| Data | Zmiana |
|---|---|
| 2026-11-13 | Utworzono dokument na podstawie audytu menu, topbara, dashboardu, list, formularzy, pipeline’u, raportów i widoków mobilnych. |

