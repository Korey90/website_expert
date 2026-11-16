# Plan przebudowy interfejsu panelu administracyjnego

**Status:** etap 6 w toku — lokalna walidacja przedwdrożeniowa zakończona; pozostaje kontrolowane wdrożenie i zebranie uwag
**Zakres:** wszystkie widoki pod `/admin/*`  
**Data utworzenia:** 2026-11-13  
**Ostatnia aktualizacja:** 2026-11-15
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

### Finanse i raporty

- sprzedaż: faktury, wyceny, kontrakty i płatności;
- zakupy: dostawcy, wydatki, korekty i wydatki cykliczne;
- bankowość: konta i uzgadnianie transakcji;
- księgowość i VAT: plan kont, okresy, dzienniki, VAT oraz HMRC.
- raporty finansowe i podatkowe.

Raport konwersji pozostaje przy procesie sprzedaży. Pozostałe raporty są dostępne z huba „Finanse i raporty”, dzięki czemu architektura zachowuje limit siedmiu głównych grup.

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
**Status:** zakończone

- [x] Przypisać wszystkie elementy menu do procesów użytkownika.
- [x] Rozdzielić funkcje codzienne, konfiguracyjne i operatorskie.
- [x] Przygotować warianty menu dla administratora, managera i księgowego.
- [x] Ujednolicić nazwy stron, zasobów i akcji na poziomie architektury informacji.
- [x] Przygotować klucze tłumaczeń dla wspólnej nawigacji.
- [x] Zatwierdzić docelową mapę panelu przed pracą wizualną.

**Rezultat:** zatwierdzona architektura informacji i macierz widoczności według ról.

### Etap 2 — prototypy UX/UI

**Szacunek:** 3–4 dni  
**Status:** zakończone — zatwierdzone przez użytkownika

- [x] Przygotować prototyp menu i topbara na desktopie.
- [x] Przygotować prototyp mobilnego drawera i topbara.
- [x] Zaprojektować dashboard zależny od roli.
- [x] Zaprojektować wzorzec listy rekordów.
- [x] Zaprojektować wzorzec formularza tworzenia i edycji.
- [x] Zaprojektować widok szczegółowy rekordu.
- [x] Zaprojektować pipeline i świadome przewijanie poziome.
- [x] Zaprojektować raporty oraz stany puste, ładowania, błędów i sukcesu.
- [x] Przygotować wersję wizualną powłoki i archetypów stron.
- [x] Zatwierdzić prototyp przed wdrożeniem fundamentu.

**Rezultat:** zaakceptowany prototyp desktopowy i mobilny dla wszystkich głównych archetypów stron.

### Etap 3 — fundament techniczny

**Szacunek:** 3–5 dni  
**Status:** zakończone

- [x] Zdefiniować tokeny kolorów, odstępów, promieni i typografii w motywie Filament.
- [x] Wdrożyć docelowe grupowanie i kolejność menu.
- [x] Dodać widoczny kontekst workspace’u.
- [x] Zaprojektować globalne wyszukiwanie i szybkie akcje.
- [x] Ujednolicić powiadomienia i przypięte notatki.
- [x] Utworzyć wspólne reguły dla nagłówków, toolbarów, formularzy, tabel i pustych stanów.
- [x] Usunąć style inline z komponentów objętych fundamentem.
- [x] Wdrożyć tłumaczenia PL, EN i PT.
- [x] Dodać testy wspólnych elementów i nawigacji według ról.

Walidacja wykonana w ramach etapu: build produkcyjny Vite, kompilacja Blade, test kontraktu siedmiu grup, test użytkownika `panel-only`, kontrola unikalności wyszukiwanych tras oraz istniejąca regresja RBAC/tenant. Smoke test 1440/390 px potwierdził brak przepełnienia dokumentu i kolizji elementów topbara. Sprawdzono też stan ciemny, wyszukiwanie nawigacji, szybkie tworzenie oraz dropdown przypiętych notatek.

**Rezultat:** wspólna powłoka panelu i zestaw stabilnych wzorców UI.

### Etap 4 — pilotaż CRM i projektów

**Szacunek:** 5–7 dni  
**Status:** zakończone

- [x] Dashboard — usunięto duplikat szybkich akcji i metryki operatorskie SaaS, ukryto nieistotne puste widgety, naprawiono dataset wykresu przychodów.
- [x] Klienci.
  - [x] Responsywna lista, opis kontekstu, klikalny rekord i użyteczny pusty stan.
  - [x] Kontekstowe opisy formularza i widoku szczegółowego.
  - [x] Ujednolicenie sekcji formularza i widoku szczegółowego.
- [x] Leady.
  - [x] Responsywna lista, opis kontekstu, klikalny rekord i użyteczny pusty stan.
  - [x] Kontekstowe opisy formularza i widoku szczegółowego oraz pełna szerokość sekcji formularza.
  - [x] Ujednolicenie akcji i widoku szczegółowego oraz serwerowa blokada mutacji dla dostępu tylko do odczytu.
- [x] Pipeline.
  - [x] Czytelny sygnał przewijania, scroll snap, obsługa focusu i lokalizacja działań kart.
  - [x] Ukrycie i serwerowa blokada akcji mutujących dla ról tylko do odczytu.
  - [x] Ujednolicenie modali na natywnej, dostępnej powłoce Filament.
- [x] Projekty.
  - [x] Responsywna lista, opis kontekstu, klikalny rekord i użyteczny pusty stan.
  - [x] Kontekstowe opisy formularza i widoku szczegółowego oraz pełna szerokość sekcji formularza.
  - [x] Ujednolicenie widoku szczegółowego, faz, szybkich działań i zadań oraz serwerowa kontrola mutacji.
- [x] Widoki desktopowe i mobilne.
  - [x] Dashboard i listy pilotażowe przy 1440/390 px.
  - [x] Formularze, szczegóły, zadania i pipeline przy 1440/390 px.
- [x] Test scenariuszy użytkownika po wdrożeniu pilotażu.
- [x] Korekta wzorców przed migracją pozostałych modułów.

**Rezultat:** zweryfikowany wzorzec możliwy do bezpiecznego powielenia.

### Etap 5 — migracja pozostałych modułów

**Szacunek:** 10–15 dni  
**Status:** zakończone

#### Korekta globalnej powłoki po przeglądzie użytkownika

- [x] Przenieść do `/admin/*` geometrię zatwierdzonego prototypu: pełnowysokościowe menu 272 px, belkę 72 px i treść o maksymalnej szerokości 1320 px.
- [x] Wdrożyć prototypowe tokeny powierzchni, koralowego akcentu, obramowań, promieni, cieni, nagłówków, przycisków, formularzy i tabel.
- [x] Zwinąć nieaktywne grupy menu; aktywna sekcja pozostaje rozwinięta, a wszystkie siedem grup zachowuje RBAC.
- [x] Dopasować szufladę mobilną, belkę 64 px, akcje nagłówka i listy kart przy 390 px.
- [x] Zweryfikować dashboard przy 1440 px oraz listę leadów przy 390 px w jasnym i ciemnym motywie: brak overflow i błędów JavaScript.
- [x] Walidacja techniczna: 13 testów / 92 asercje, Pint, Blade, ESLint, build Vite i kontrola różnic poprawne.

#### Fala 1 — finanse i raporty

- [x] Dokumenty sprzedażowe — faktury, wyceny, umowy i korekty mają responsywne listy, kontekst, klikalne rekordy, puste stany i opisy formularzy/szczegółów.
- [x] Zakupy i wydatki — wydatki, dostawcy, wydatki cykliczne i korekty zakupowe mają responsywne listy, kontekst stron, wyszukiwanie rekordów, klikalne wiersze i użyteczne puste stany.
- [x] Bankowość — rachunki, transakcje i płatności mają responsywne listy, wyszukiwanie rekordów, klikalne wiersze, kontekst stron i użyteczne puste stany.
- [x] Księgowość, VAT i HMRC — plan kont, okresy, korekty VAT, dzienniki, deklaracje, księga główna i połączenie HMRC mają spójny kontekst, responsywne listy oraz bezpieczne skróty do rekordów.
- [x] Raporty finansowe — osiem raportów ma spójny kontekst operacyjny, responsywne filtry, podsumowania i bezpiecznie przewijane tabele.

**Fala 1 zakończona:** 26 kluczowych tras finansowych zweryfikowano przy 1440 i 390 px.

#### Fala 2 — marketing, CMS i SEO

- [x] Nawigacja strony — kontekst konfiguracji, responsywna tabela i użyteczny pusty stan.
- [x] Landing pages i CMS — landing pages, strony, sekcje oraz artykuły mają responsywne listy, kontekst stron, wyszukiwanie i puste stany.
- [x] Treści, usługi i portfolio — katalog usług, strony usług oraz realizacje korzystają ze spójnego wzorca list i bezpiecznych skrótów do edycji.
- [x] E-mail i SMS — szablony mają kontekst stron, wyszukiwanie, responsywne listy i czytelne puste stany.
- [x] SEO — monitoring pozycji zachowuje tryb tylko do odczytu, a mapa strony ma przetłumaczony kontekst i opis zakresu.

**Fala 2 zakończona:** 12 kluczowych tras marketingu, CMS i SEO zweryfikowano przy 1440 i 390 px.

#### Fala 3 — automatyzacje

- [x] Reguły — spójny kontekst, responsywna lista, czytelniejszy formularz i kontrolowane testowanie zgodne z RBAC.
- [x] Triggery — kontekst stron, wyszukiwanie, responsywna lista i rozróżnienie zdarzeń systemowych od własnych.
- [x] Logi — audytowalna lista i szczegóły, bezpieczne linki do leadów oraz jednoznaczna akcja czyszczenia wpisów starszych niż 30 dni.

**Fala 3 zakończona:** pięć reprezentatywnych tras automatyzacji zweryfikowano przy 1440 i 390 px.

#### Fala 4 — administracja i narzędzia systemowe

- [x] Użytkownicy, role, uprawnienia i sesje — responsywne listy, kontekst stron, wyszukiwanie rekordów oraz jawnie zabezpieczone akcje unieważniania sesji.
- [x] SaaS Billing — firmy, plany i subskrypcje mają kontekst operacyjny, responsywne listy, wyszukiwanie oraz bezpieczne skróty do szczegółów i Stripe.
- [x] Integracje i ustawienia — poczta/SMS/domeny, płatności, tracking, dane prawne oraz kalkulator mają kontekst operacyjny, dostępne układy mobilne i spójne tłumaczenia.
- [x] Bezpieczeństwo — zdarzenia mają kontekst operacyjny, statystyki, responsywną listę, tłumaczenia i serwerową kontrolę akcji mutujących.
- [x] Log Viewer, Job Manager i pozostałe narzędzia operatorskie — wspólny układ, dostępne filtry i dialogi, lokalnie przewijane tabele oraz jawne bramki uprawnień dla każdej mutacji.

**Fala 4 zakończona:** 18 tras administracji dostępem, SaaS Billing, ustawień, bezpieczeństwa i narzędzi operatorskich zweryfikowano przy 1440 i 390 px. Końcowa regresja administracji: 40 testów / 1314 asercji.

**Rezultat:** wszystkie strony `/admin/*` korzystają ze wspólnej architektury i standardów interfejsu.

### Etap 6 — walidacja i wdrożenie

**Szacunek:** 3–5 dni  
**Status:** w toku — walidacja lokalna zakończona

- [x] Sprawdzić szerokości 390, 768, 1280 i 1440 px.
- [x] Sprawdzić jasny i ciemny motyw.
- [x] Sprawdzić obsługę klawiatury, focus, kontrast i ARIA.
- [x] Zweryfikować wszystkie teksty PL, EN i PT.
- [x] Uruchomić testy ról, uprawnień i izolacji tenantów.
- [x] Uruchomić testy Filament/PHPUnit oraz produkcyjny build.
- [x] Przeprowadzić smoke test najważniejszych procesów.
- [ ] Wdrożyć etapowo z możliwością szybkiego wycofania warstwy UI.
- [ ] Zebrać uwagi po wdrożeniu i zamknąć backlog krytyczny.

Walidacja lokalna objęła 66 renderów przeglądarkowych w czterech szerokościach i obu motywach, 12 audytów dostępności archetypów oraz nawigację klawiaturą. Naprawiono programowe etykiety filtrów Job Managera, spójny fokus klawiaturowy, niewidoczny fokusowalny link topbara i obsługę `Escape` mobilnego drawera z przywróceniem fokusu. Regresja końcowa: 102 testy PHP / 1622 asercje oraz 16 testów frontendowych; kontrakt pilnuje zgodności wszystkich katalogów PL/PT z EN.

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
| 2026-11-13 | Ograniczyć nawigację do siedmiu głównych grup | Raporty domenowe będą dostępne przy procesie sprzedaży lub w hubie finansowym zamiast tworzyć ósmą grupę. |
| 2026-11-13 | Globalne wyszukiwanie obejmuje nawigację i kluczowe rekordy | W pierwszej wersji przeszukiwane są klienci, leady, projekty, wyceny i faktury, zawsze przez istniejące bramki RBAC i tenant scope. |
| 2026-11-13 | Szybkie tworzenie pozostaje w topbarze | Lista akcji jest wyliczana z `canCreate()`, więc użytkownik nie zobaczy operacji bez uprawnień. |
| 2026-11-14 | Niestandardowe mutacje szczegółów i pipeline’u wymagają `canEdit()` również po stronie serwera | Widoczność elementu interfejsu nie jest granicą bezpieczeństwa; bezpośrednie wywołanie Livewire musi kończyć się 403 dla roli tylko do odczytu. |
| 2026-11-14 | Modale pipeline’u korzystają z natywnej powłoki Filament bez teleportacji DOM | Zapewnia to focus trap, Escape, ARIA i stabilne animacje w obrębie niestandardowej strony Livewire. |
| 2026-11-14 | Globalna powłoka `/admin/*` odwzorowuje zatwierdzony prototyp, a nie domyślną geometrię Filament | Po informacji użytkownika, że zmiany są niewidoczne, priorytetem stała się jednoznaczna zgodność wizualna menu, belki i treści; Filament pozostaje fundamentem zachowania i dostępności. |
| 2026-11-15 | Narzędzia operatorskie korzystają ze wspólnego wzorca wewnątrz Administracji, bez osobnego trybu panelu | Spójny układ statusów, filtrów, tabel i dialogów wystarcza do odróżnienia zadań operatorskich, a istniejące uprawnienia nadal ograniczają widoczność i wykonanie każdej mutacji. |
| 2026-11-15 | Rozwinięcie aktywnej grupy nawigacji jest przywracane po każdej nawigacji Livewire | Zapamiętany stan zwinięcia nie może ukrywać bieżącej lokalizacji użytkownika; pozostałe nieaktywne grupy nadal zachowują ustawiony stan. |
| 2026-11-15 | Każdy interaktywny element panelu otrzymuje jednolity koralowy wskaźnik `:focus-visible` | Widoczność fokusu nie może zależeć od lokalnych styli komponentu; globalny wzorzec obejmuje także linki nawigacji i niestandardowe regiony. |
| 2026-11-15 | Mobilny drawer zamyka się klawiszem `Escape` i przywraca fokus wyzwalaczowi | Użytkownik klawiatury musi móc opuścić nawigację bez przechodzenia przez całą jej zawartość; aktywne dialogi zachowują pierwszeństwo obsługi `Escape`. |

## Backlog odkryć

- [x] Ustalić, czy globalne wyszukiwanie ma obejmować tylko strony, czy również rekordy domenowe.
- [ ] Ustalić priorytet domyślnego dashboardu dla każdej roli.
- [x] Zdecydować, czy szablony pozostają przy procesach, czy trafiają do wspólnej sekcji konfiguracji.
- [x] Zweryfikować potrzebę oddzielnego trybu operatorskiego dla SaaS, bezpieczeństwa i narzędzi systemowych — pozostają w Administracji, ale korzystają ze wspólnego wzorca operatorskiego.
- [x] Ustalić sposób komunikowania poziomego przewijania pipeline’u i szerokich tabel — fokusowalny region, widoczny lokalny scroll i brak overflow dokumentu.
- [x] Naprawić błąd inicjalizacji pustych datasetów wykresów dashboardu (`normalizeDatasets`).

## Dziennik zmian

| Data | Zmiana |
|---|---|
| 2026-11-13 | Utworzono dokument na podstawie audytu menu, topbara, dashboardu, list, formularzy, pipeline’u, raportów i widoków mobilnych. |
| 2026-11-13 | Użytkownik zatwierdził plan; rozpoczęto etap architektury informacji i prototypowania. Scalono raporty z właściwymi domenami, aby zachować limit siedmiu głównych grup. |
| 2026-11-13 | Ukończono architekturę informacji, macierz ról i klikalny prototyp siedmiu archetypów. Walidacja 1440/390 px: brak błędów JavaScript i brak przepełnienia dokumentu. |
| 2026-11-13 | Wdrożono fundament produkcyjnego panelu: siedem grup RBAC, wyszukiwanie stron i kluczowych rekordów, szybkie tworzenie, stały workspace, dostępne przypięte notatki, tokeny motywu i tłumaczenia PL/EN/PT. Testy celowane: 18/1220; lint, build i Blade poprawne; smoke test jasny/ciemny 1440/390 bez przepełnienia i kolizji topbara. |
| 2026-11-13 | Rozpoczęto pilotaż: dashboard pokazuje wyłącznie operacyjne sekcje z realnymi danymi, a listy klientów, leadów i projektów mają kontekstowe opisy, responsywne kolumny, klikalne wiersze i puste stany. Naprawiono asocjacyjny dataset wykresu przychodów. Smoke test 1440/390: brak błędów JavaScript i overflow. |
| 2026-11-13 | Dodano kontekstowe opisy formularzy i szczegółów, poprawiono szerokość formularzy leadów/projektów oraz wdrożono dostępny region pipeline’u z widoczną instrukcją przewijania, scroll snapem i fokusem klawiatury. Smoke test pipeline’u 1440/390: lokalny scroll działa bez overflow dokumentu i błędów JavaScript. |
| 2026-11-13 | Domknięto RBAC pipeline’u: role tylko do odczytu widzą historię, ale nie widzą i nie mogą wywołać akcji mutujących; kontrola działa także po stronie metod Livewire. Testy autoryzacji pipeline’u: 3/7. |
| 2026-11-13 | Zweryfikowano cały bieżący przyrost pilota: 24 testy / 1239 asercji, Pint, cache widoków Blade, ESLint, produkcyjny build Vite i kontrola różnic przeszły bez błędów. Do domknięcia etapu 4 pozostają niestandardowe widoki szczegółowe oraz modale pipeline’u. |
| 2026-11-14 | Zakończono etap 4: ujednolicono natywne modale pipeline’u, widok faz i szybkich działań projektu, dodano brakujące bramki mutacji w szczegółach leadów i projektów oraz tłumaczenia PL/EN/PT. Smoke test ośmiu tras przy 1440/390 px: HTTP 200, brak overflow i błędów JavaScript; modale mają dialog/ARIA, focus trap i obsługę Escape. Końcowa regresja: 28 testów / 1251 asercji; Pint, Blade, ESLint, build i zgodność tłumaczeń poprawne. |
| 2026-11-14 | Rozpoczęto etap 5 i ukończono pierwszy pakiet fali finansowej: listy faktur, wycen, umów i faktur korygujących korzystają ze wzorca pilota, a strony tworzenia/edycji/szczegółów mają kontekstowe opisy. Dodatkowo akcje wysyłki wycen i zmiany statusu umów respektują `canEdit()`/`canCreate()`. Testy celowane: 15/1197; smoke test czterech list przy 1440/390 px: HTTP 200, brak overflow i błędów JavaScript. |
| 2026-11-14 | Ukończono pakiet zakupów i wydatków: listy wydatków, dostawców, wydatków cyklicznych i korekt zakupowych są responsywne, klikalne i dostępne w wyszukiwaniu globalnym; wszystkie powiązane strony mają kontekstowe opisy i puste stany. Dla szablonów cyklicznych link do edycji respektuje `canEdit()`. Regresja obu pakietów finansowych i dostępu: 16 testów / 1209 asercji; smoke test czterech nowych list przy 1440/390 px: HTTP 200, brak overflow i błędów JavaScript. |
| 2026-11-14 | Ukończono pakiet bankowości: rachunki bankowe, transakcje i płatności korzystają ze wspólnego wzorca list i kontekstu stron, są responsywne, klikalne oraz dostępne w wyszukiwaniu globalnym bez pustych tytułów rekordów. Regresja całej dotychczasowej fali finansowej i dostępu: 17 testów / 1218 asercji; smoke test trzech list przy 1440/390 px: HTTP 200, brak overflow i błędów JavaScript. |
| 2026-11-14 | Ukończono pakiet księgowości, VAT i HMRC: pięć zasobów tabelarycznych korzysta ze wspólnego wzorca responsywności, wyszukiwania i pustych stanów, a księga główna oraz HMRC mają kontekstowe opisy bez zmian w kontrolowanych workflow. Testy interfejsu pakietu: 2/18; smoke test siedmiu tras przy 1440/390 px: HTTP 200, brak overflow i błędów JavaScript. |
| 2026-11-14 | Zakończono pierwszą falę etapu 5. Osiem raportów finansowych otrzymało spójny kontekst operacyjny; smoke test raportów przy 1440/390 px potwierdził brak overflow i błędów JavaScript. Wspólna regresja pięciu pakietów finansowych oraz RBAC/tenant: 20 testów / 1252 asercje. Łącznie zweryfikowano 26 tras fali finansowej na desktopie i telefonie. |
| 2026-11-14 | Zakończono drugą falę etapu 5: nawigacja publiczna, landing pages, CMS, artykuły, usługi, portfolio, komunikacja e-mail/SMS, monitoring pozycji i mapa strony korzystają ze spójnego kontekstu, responsywnych list, bezpiecznych linków i użytecznych pustych stanów. Mapa strony została przetłumaczona w PL/EN/PT, a monitoring pozycji zachował charakter tylko do odczytu. Smoke test 12 tras przy 1440/390 px: HTTP 200, brak overflow i błędów JavaScript. Regresja interfejsu oraz RBAC/tenant: 18 testów / 1222 asercje; Pint, Blade, ESLint, zgodność tłumaczeń i kontrola różnic poprawne. |
| 2026-11-14 | Zakończono trzecią falę etapu 5: reguły, triggery i logi automatyzacji otrzymały spójny kontekst, responsywne listy, wyszukiwanie i puste stany. Testowanie reguł jest widoczne i wykonywalne wyłącznie z uprawnieniem edycji; czyszczenie starych logów przeniesiono z mylącej akcji zbiorczej do zabezpieczonej akcji nagłówka. Smoke test pięciu tras przy 1440/390 px: HTTP 200, brak overflow i błędów JavaScript. Regresja UI, silnika automatyzacji oraz RBAC/tenant: 33 testy / 1221 asercji; Pint, Blade, ESLint, zgodność tłumaczeń i kontrola różnic poprawne. |
| 2026-11-14 | Rozpoczęto czwartą falę etapu 5 i ukończono pakiet administracji dostępem: użytkownicy, role, uprawnienia i sesje mają spójny kontekst, responsywne listy, wyszukiwanie i puste stany. Akcje usuwania nieaktywnych i gościnnych sesji przeniesiono do czytelnych akcji nagłówka, a wszystkie niestandardowe mutacje sesji respektują `revoke_sessions` także po stronie serwera. Smoke test czterech tras przy 1440/390 px: HTTP 200, brak overflow i błędów JavaScript. Regresja UI oraz RBAC/tenant: 17 testów / 1201 asercji. |
| 2026-11-14 | Ukończono pakiet SaaS Billing: firmy, plany i subskrypcje Stripe mają kontekst operacyjny, responsywne listy, globalne wyszukiwanie, puste stany i klikalne rekordy. Nie zmieniono logiki planów, tenant isolation ani przepływów Stripe. Smoke test trzech list przy 1440/390 px: HTTP 200, brak overflow i błędów JavaScript. Wspólna regresja dwóch pakietów administracyjnych oraz RBAC/tenant: 18 testów / 1211 asercji; Pint, Blade, ESLint, zgodność tłumaczeń i kontrola różnic poprawne. |
| 2026-11-14 | Ukończono pakiet integracji i ustawień: konfiguracja poczty/SMS/domen, płatności, tracking, dane prawne oraz hub kalkulatora mają kontekst operacyjny i spójne tłumaczenia. Zakładki kalkulatora są dostępne i bezpiecznie przewijane na telefonie; trzy bezpośrednie zasoby kalkulatora mają responsywne listy, wyszukiwanie i puste stany, a mutacje widgetów wymagają `manage_calculator`. Smoke test ośmiu tras przy 1440/390 px: HTTP 200, brak overflow i błędów JavaScript. Wspólna regresja trzech pakietów fali 4 oraz RBAC/tenant: 20 testów / 1237 asercji; Pint, Blade, ESLint, zgodność tłumaczeń i kontrola różnic poprawne. |
| 2026-11-14 | Po przeglądzie użytkownika wykonano globalną korektę zgodności z prototypem: sidebar ma 272 px i pełną wysokość, topbar 72 px, nieaktywne grupy są zwinięte, a canvas, karty, tabele, formularze i koralowy akcent korzystają z zatwierdzonych tokenów. Na telefonie działa 64-pikselowa belka i szuflada menu. Dashboard 1440 px oraz leady 390 px w jasnym i ciemnym motywie nie mają overflow ani błędów JavaScript. Walidacja: 13 testów / 92 asercje, Pint, Blade, ESLint, produkcyjny build i `git diff --check` poprawne. |
| 2026-11-15 | Zakończono etap 5 oraz falę 4: Security Events otrzymało statystyki, kontekst, tłumaczenia i zabezpieczone akcje, a Log Viewer i Job Manager wspólny responsywny wzorzec operatorski z dostępnymi dialogami i lokalnym przewijaniem tabel. Naprawiono też utrwalony stan menu, aby aktywna grupa Administracja zawsze pozostawała rozwinięta. Smoke test 1440/390 px oraz jasnego i ciemnego motywu: brak overflow dokumentu i błędów JavaScript; na telefonie tabele przewijają się lokalnie. Regresja administracji: 40 testów / 1314 asercji; Pint, Blade, ESLint, produkcyjny build i kontrola różnic poprawne. |
| 2026-11-15 | Zakończono lokalną walidację przedwdrożeniową etapu 6. Wykonano 66 renderów reprezentatywnych procesów przy 390/768/1280/1440 px w jasnym i ciemnym motywie oraz 12 audytów etykiet, ARIA i kontrastu. Naprawiono etykiety Job Managera, globalny fokus, ukryty link topbara i obsługę `Escape` drawera. Regresja RBAC/tenant/UI: 102 testy / 1622 asercje; frontend 16/16. Kontrakt lokalizacji sprawdza komplet wszystkich katalogów PL i PT względem EN. Do wykonania pozostają wyłącznie kontrolowane wdrożenie i zebranie uwag. |

