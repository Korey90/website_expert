# Analiza projektu web-dev-app na podstawie kodu źródłowego

> Data analizy: 27.03.2026
> Srodowisko pracy: Visual Studio Code + AI Chat
> Podstawa analizy: kod aplikacji, struktura modułów, testy uruchomione lokalnie
> Zakres: backend Laravel, panel Filament, frontend React/Inertia, portal klienta, finanse, automatyzacje, jakość techniczna

## Streszczenie

`web-dev-app` jest rozbudowanym systemem dla agencji web development, a nie tylko klasyczna strona firmowa. Z samego kodu wynika, ze aplikacja laczy kilka rol w jednym produkcie: pozyskanie leada, obsluge CRM, realizacje projektu, dokumenty finansowe, portal klienta, platnosci online, automatyzacje oraz raportowanie.

Najmocniejsza cecha projektu to spojny model procesu biznesowego. Klient trafia przez marketing lub kalkulator do CRM, dalej przechodzi przez lead, quote, contract, project i invoice, a czesc interakcji domyka w portalu klienta. To jest realnie widoczne w trasach, modelach, kontrolerach i zasobach Filament.

Jednoczesnie analiza kodu pokazuje wyrazne nierownosci jakosciowe. Czesci produktu sa juz dojrzale i przemyslane, ale obok nich istnieja obszary tymczasowe, niedokonczone albo ryzykowne utrzymaniowo. Najwazniejsze problemy nie dotycza wyboru stacku, tylko rosnacej zlozonosci logiki, duzych klas, niespojnosci UX oraz slabej niezawodnosci srodowiska testowego.

## Obecny stan

### Co aplikacja faktycznie oferuje

Na podstawie kodu projekt dostarcza nastepujace obszary funkcjonalne:

- publiczna warstwa marketingowa oparta o Inertia i React
- kalkulator wyceny zasilany danymi z bazy
- CRM i panel administracyjny w Filament
- obsluge leadow, klientow, projektow, quotes, contracts, invoices i payments
- portal klienta z samoobslugowymi ekranami
- integracje platnosci Stripe i PayU
- automatyzacje oparte o eventy Eloquent i kolejke
- raporty eksportowane do HTML, PDF, XLSX i CSV

To nie wynika z dokumentacji, tylko z realnej implementacji w:

- `routes/web.php`
- `app/Http/Controllers/`
- `app/Filament/Resources/`
- `app/Jobs/ProcessAutomationJob.php`
- `resources/js/Pages/Portal/`
- `resources/js/Components/Marketing/`

### Główny model produktu widoczny w kodzie

Przeplyw systemu jest zaszyty bezposrednio w modelach, kontrolerach i trasach:

1. Lead powstaje przez `ContactController` lub `CalculatorLeadController`.
2. Lead jest prowadzony przez `PipelineStage` i `LeadResource`.
3. Po stronie operacyjnej pojawiaja sie `Quote`, `Contract`, `Project` i `Invoice`.
4. Portal klienta daje klientowi dostep do projektow, dokumentow, komunikacji i platnosci.
5. Zmiany statusow uruchamiaja automatyzacje przez `AutomationEventListener` i `ProcessAutomationJob`.

To jest mocny sygnal, ze architektura byla projektowana wokol calego workflow agencji, a nie wokol pojedynczych CRUD-ow.

## Analiza modułów

### 1. Backend i warstwa aplikacyjna Laravel

Kod backendu jest oparty na klasycznym Laravelowym podziale: modele, kontrolery, middleware, joby, listenery, serwisy i maile. To daje przewidywalna strukture i dobra baze do rozwoju.

Widoczne mocne strony:

- `AppServiceProvider` dynamicznie podmienia konfiguracje integracji z ustawien zapisanych w bazie
- `HandleInertiaRequests` centralnie dostarcza propsy wspolne dla frontendu
- modele takie jak `Client`, `Lead`, `Quote`, `Project`, `Invoice`, `Contract` odzwierciedlaja realne byty biznesowe
- warstwa kontrolerow nie jest cienkim API do pustych widokow, tylko faktycznie orkiestruje proces

Wniosek:

Backend jest dobrze osadzony w domenie biznesowej i ma sensowna strukture bazowa. Problemem nie jest brak architektury, tylko rosnaca liczba odpowiedzialnosci skupionych w kilku miejscach.

### 2. Panel administracyjny Filament

Panel w Filament jest jednym z najmocniejszych elementow aplikacji. Z kodu w `AdminPanelProvider` i zasobach Filament wynika, ze to nie jest tylko panel CRUD, ale operacyjne centrum pracy zespolu.

Mocne strony:

- logiczny podzial na grupy: CRM, Projects, Finance, Marketing, Settings
- duza liczba zasobow biznesowych i dedykowanych stron administracyjnych
- dashboard z widgetami operacyjnymi
- rozbudowane formularze i akcje w zasobach takich jak `ProjectResource`, `ContractResource`, `InvoiceResource`, `AutomationRuleResource`
- notyfikacje bazodanowe i quick actions

Slabsze strony:

- `AdminPanelProvider` zawiera duzy blok inline JavaScript odpowiedzialny za dzwieki i obsluge notyfikacji
- czesc logiki UI jest wstrzykiwana bezposrednio w provider, co pogarsza czytelnosc i utrzymanie
- niektore komponenty Filament generuja bardzo bogate widoki, ale robia to poprzez duze fragmenty HTML budowane w PHP, co z czasem bedzie trudniejsze w utrzymaniu

Wniosek:

Filament realnie przyspiesza development i daje duza wartosc biznesowa, ale warstwa panelowa zaczyna zbierac dlug techniczny w obszarach customowego UI i zachowan klientowych.

### 3. Portal klienta

Portal klienta jest jednym z najbardziej konkretnych modulow projektu. `PortalController` oraz strony w `resources/js/Pages/Portal/` pokazuja, ze klient moze nie tylko przegladac dane, ale rzeczywiscie pracowac z systemem.

Co dziala:

- dashboard klienta z projektami, fakturami i quotes
- widok projektu z postepem faz, taskow i komunikacja z zespolem
- widoki invoices, quotes, contracts i ekran wyboru platnosci
- podpis kontraktu przez signature pad lub elektroniczna akceptacje
- ustawienia notyfikacji

Co widac w kodzie:

- portal jest oparty o osobny layout i wyraznie wydzielona nawigacje
- autoryzacja opiera sie na relacji `portal_user_id` po stronie klienta
- poszczegolne ekrany sa rozpisane czytelnie, z sensownymi komponentami i prostymi przeplywami akcji

Ograniczenia:

- `PortalController` obsluguje bardzo szeroki zakres scenariuszy, od dashboardu przez kontrakty po platnosci i ustawienia
- w UI portalu widac sporo angielskiego copy i lokalnych decyzji tekstowych osadzonych bezposrednio w komponentach
- system jest funkcjonalny, ale nie wszedzie widac jeszcze pelne dopracowanie stanów przejsciowych, pustych ekranow i scenariuszy bledu

Wniosek:

Portal klienta jest realna funkcja produktu, nie tylko dodatkiem. To jeden z obszarow o najwiekszym potencjale wartosci biznesowej, ale wymaga dalszego szlifowania UX i lepszej separacji logiki po stronie backendu.

### 4. Marketing frontend i lead generation

Strona marketingowa nie jest statycznym frontem. `WelcomeController` sklada dynamiczne sekcje z bazy, a `CostCalculatorV2` opiera sie na danych pricing, strings i steps pobranych z modeli. To oznacza, ze marketing jest w duzej mierze DB-driven.

Mocne strony:

- dynamiczne sekcje strony zarzadzane przez `SiteSection`
- kalkulator wyceny zasilany rekordami `CalculatorPricing`, `CalculatorString`, `CalculatorStep`
- locale i tresci sa przekazywane przez backend, bez hardcodowania calego flow po stronie React
- zgody trackingowe i dane integracyjne sa wspoldzielone przez middleware

Problemy widoczne w kodzie:

- `Welcome.jsx` i czesc komponentow frontendowych zawieraja sporo angielskiego copy, mimo istnienia mechanizmow locale
- w `CostCalculatorV2.jsx` formularz po fetchu ustawia sukces w bloku `finally`, czyli rowniez wtedy, gdy request zakonczy sie bledem
- w `package.json` nie ma skryptow typu `lint`, `test` ani `typecheck`, co oslabia workflow frontendowy

Wniosek:

Marketing frontend ma dobra baze architektoniczna, ale krytyczne elementy konwersyjne nadal wymagaja lepszej niezawodnosci i procesu jakosciowego.

### 5. Finanse, dokumenty i raportowanie

Warstwa finansowa jest szeroka i dobrze zintegrowana z reszta systemu. Z kodu wynika, ze aplikacja obsluguje invoices, payments, PDF-y, Stripe, PayU oraz raporty eksportowane do kilku formatow.

Mocne strony:

- `StripeWebhookController` obsluguje kluczowe zdarzenia platnicze i potwierdzenia
- `PayuService` korzysta z ustawien z bazy, co ulatwia konfiguracje runtime
- `ContractResource` zawiera interpolacje szablonow kontraktow i wygodny workflow pracy w panelu
- `ReportController` umozliwia szybkie raporty operacyjne w HTML, PDF, XLSX i CSV

Problemy wykryte w kodzie:

- `ReportController` filtruje leady po `status`, mimo ze logika leadow w projekcie jest oparta glownie o `pipeline_stage_id` i `PipelineStage`
- w eksporcie faktur uzywane jest pole `tax_amount`, podczas gdy model faktury operuje na `vat_amount`; to wyglada jak realny blad danych eksportowych
- raporty sa praktyczne, ale nadal mocno proceduralne i nie maja oddzielonej warstwy przygotowania danych

Wniosek:

Modul finansowy jest jednym z najmocniejszych skladnikow systemu, ale zawiera juz sygnaly, ze bez dodatkowego porzadkowania bedzie generowal subtelne regresje biznesowe.

### 6. Automatyzacje i komunikacja

Automatyzacje sa faktycznie wdrozone, a nie tylko zaplanowane. `AutomationEventListener` nasluchuje zdarzen modelowych, a `ProcessAutomationJob` wykonuje reguly w tle.

Mocne strony:

- eventy `lead.created`, `lead.stage_changed`, `project.status_changed`, `invoice.sent`, `quote.accepted`, `contract.signed` i inne sa podlaczone do workflow
- mechanizm delay per regula jest juz gotowy
- `ClientNotificationGate` pilnuje preferencji klienta dla e-maili i SMS
- system tworzy powiadomienia panelowe i obsluguje dostep portalowy

Ograniczenia:

- `ProcessAutomationJob` laczy dobieranie regul, walidacje warunkow i wykonanie wielu typow akcji w jednej klasie
- przy rosnacej liczbie akcji ten wzorzec bedzie coraz trudniejszy do testowania i debugowania

Wniosek:

Automatyzacje sa realna przewaga systemu, ale wymagaja dalszej modularyzacji, jesli maja pozostac stabilne wraz ze wzrostem zlozonosci.

## Ocena jakosci

### UX

#### Mocne strony UX

- portal klienta jest konkretny i uzyteczny
- projekt pokazuje postep, fazy, zadania i komunikacje w czytelny sposob
- ekran kontraktu zawiera dobrze przemyslany mechanizm podpisu
- ekran platnosci jest prosty i zrozumialy
- dashboard klienta grupuje informacje w logiczny sposob

#### Problemy UX wykryte w kodzie

- kalkulator wyceny pokazuje sukces nawet przy nieudanej wysylce formularza
- spora czesc copy w marketingu i portalu jest twardo wpisana po angielsku, mimo istnienia mechanizmow lokalizacji
- glowny `resources/js/Pages/Dashboard.jsx` dla zalogowanego uzytkownika poza portalem nadal jest praktycznie szablonowym placeholderem Breeze
- czesc pustych stanów i bledow jest obsluzona tylko podstawowo

Ocena:

UX jest dobry tam, gdzie produkt dostal juz rzeczywisty przeplyw biznesowy, szczegolnie w portalu klienta. Najslabsze miejsca to nie tyle layout, co wiarygodnosc interakcji i niespojnosc komunikatow.

### DX

#### Mocne strony DX

- przewidywalna struktura Laravel
- dobre wykorzystanie Filament do szybkiej budowy panelu
- sensowny podzial na modele, kontrolery, joby, serwisy i zasoby
- istnieja testy feature, w tym dosc szeroki `FullLeadWorkflowTest`

#### Problemy DX wykryte w kodzie i testach

- brak nowoczesnego workflow frontendowego: w `package.json` sa tylko `build` i `dev`
- duze klasy o szerokiej odpowiedzialnosci, zwlaszcza `PortalController` i `ProcessAutomationJob`
- duplikacja w procesie tworzenia leadow przez formularz i kalkulator
- inline skrypty w providerze panelu utrudniaja utrzymanie
- srodowisko testowe nie przechodzi nawet podstawowego zestawu testow bez poprawek migracji i zaleznosci od settings

Ocena:

DX jest mocny w szybkości developmentu, ale slabszy w utrzymaniu i przewidywalnosci zmian. Projekt jest wygodny do rozbudowy funkcjonalnej, ale coraz mniej wygodny do bezpiecznej refaktoryzacji.

### Skalowalnosc i utrzymanie

#### Co wspiera skalowanie

- kolejki i webhooki
- DB-driven konfiguracja sekcji, cennika, ustawien i automatyzacji
- duza czesc systemu jest oparta o relacje biznesowe zamiast ad hoc struktur
- panel i portal sa wydzielone warstwami funkcjonalnymi

#### Co ogranicza skalowanie

- szerokie klasy orkiestrujace wiele scenariuszy
- brak wystarczajaco mocnej sieci testow regresyjnych dla zlozonych flow
- zaleznosc niektorych komponentow od runtime DB bez bezpiecznych fallbackow w testach
- proceduralny styl w czesci raportowania i automatyzacji

Ocena:

Projekt ma dobra baze do skalowania funkcjonalnego, ale wymaga dalszej modularyzacji, jesli ma dobrze skalowac sie zespolowo i utrzymaniowo.

## Faktyczne problemy wykryte podczas analizy

### 1. Testy nie przechodza

Uruchomienie `php artisan test` wykazalo realne problemy srodowiska testowego:

- migracja `2026_03_22_194527_update_project_phases_status_enum.php` wykonuje `ALTER TABLE ... MODIFY COLUMN ...`, co nie dziala na SQLite in-memory
- przez to pada wiele testow auth i feature jeszcze na etapie migracji
- dodatkowo `ExampleTest` konczy sie bledem 500, bo `HandleInertiaRequests` odwoluje sie do tabeli `settings`, ktora nie istnieje w tym scenariuszu testowym

To jest istotna obserwacja, bo pokazuje, ze projekt ma aktywny problem z niezawodnoscia pipeline'u testowego.

### 2. Niespojnosc w raportowaniu leadow i faktur

W `ReportController` widac dwa sygnaly ryzyka:

- raport leadow operuje na `status`, podczas gdy reszta systemu prowadzi lead przez `pipeline_stage_id`
- eksport faktur korzysta z `tax_amount`, mimo ze model faktury posluguje sie `vat_amount`

To wyglada jak potencjalny blad raportowania biznesowego.

### 3. Niewiarygodny sukces w kalkulatorze

`CostCalculatorV2.jsx` ustawia stan sukcesu w `finally`, czyli niezaleznie od wyniku requestu. To jest problem produktowy, bo uzytkownik moze dostac falszywe potwierdzenie wyslania zapytania.

### 4. Nierowny poziom dopracowania ekranow

Portal klienta jest relatywnie rozbudowany, ale ogolny dashboard zalogowanego uzytkownika poza portalem nadal pozostaje domyslnym placeholderem. To sygnal, ze produkt jest rozwijany mocno domenowo, ale nie wszedzie rownomiernie.

## Rekomendacje

### Uproszczenie pracy deweloperskiej

- wydzielic akcje domenowe dla tworzenia leada z formularza i kalkulatora
- rozbic `PortalController` na mniejsze kontrolery lub klasy akcji per modul
- rozbic `ProcessAutomationJob` na warstwe orkiestracji i wykonawcow konkretnych akcji
- wyniesc klientowy kod notyfikacji z `AdminPanelProvider` do osobnych assetow
- dodac do frontendu `lint`, `format` i docelowo `typecheck`
- uporzadkowac kontrakt dla danych raportowych, aby eksporty nie opieraly sie na polach nieuzywanych gdzie indziej

### Poprawa UX

- naprawic logike sukcesu i bledu w kalkulatorze wyceny
- ujednolicic jezyk i copy w marketingu, portalu i panelu
- rozbudowac widoki pustych stanow, bledow i scenariuszy przejsciowych
- dopracowac podstawowy dashboard zalogowanego uzytkownika, aby nie odstawal od reszty systemu
- mocniej eksponowac nastepne kroki klienta przy quotes, contracts i invoices

### Potencjalne nowe funkcjonalnosci

- centrum aktywnosci klienta z osi czasu zdarzen
- raporty konwersji i skutecznosci zrodel leadow oparte o dane CRM
- bardziej rozbudowany customer health view dla projektow i opoznien
- automatyczne przypomnienia o braku odpowiedzi klienta lub zaleglosciach
- dashboard operacyjny zespolu z priorytetami i SLA

### Optymalizacje techniczne

- poprawic kompatybilnosc migracji z SQLite lub oddzielic test database strategy od SQL specyficznego dla MySQL
- dodac fallbacki albo guardy przy odczytach `Setting::get()` w testowych scenariuszach bootstrappingu
- rozszerzyc testy na portal, webhooki, raporty i kontrakty
- oddzielic przygotowanie danych raportowych od kontrolera HTTP

## Priorytety

### Priorytet 1

- naprawa srodowiska testowego
- naprawa falszywego sukcesu kalkulatora
- korekta niespojnosci w raportowaniu leadow i faktur

### Priorytet 2

- refaktoryzacja `PortalController` i `ProcessAutomationJob`
- uporzadkowanie frontendowego workflow jakosciowego

### Priorytet 3

- ujednolicenie copy i lokalizacji
- rozwój dashboardow i raportow operacyjnych

## Wniosek koncowy

Analiza kodu pokazuje, ze `web-dev-app` jest realnym systemem operacyjnym dla agencji, a nie zlepkiem ekranow. Najwieksza wartoscia projektu jest jego procesowa spojność: marketing, CRM, portal, finanse i automatyzacje pracuja na wspolnym modelu danych.

Najwieksze ryzyka nie leza w stacku technologicznym. Leza w utrzymaniu: w szerokich klasach, niedomknietych standardach jakosci frontendowej, niespojnosciach raportowych i niestabilnym srodowisku testowym. To sa problemy naprawialne bez zmiany architektury bazowej. Projekt ma bardzo dobra baze, ale powinien wejsc w etap porzadkowania i utwardzania.

## Zrodla analizy

- `app/Providers/AppServiceProvider.php`
- `app/Providers/Filament/AdminPanelProvider.php`
- `app/Http/Middleware/HandleInertiaRequests.php`
- `app/Http/Controllers/WelcomeController.php`
- `app/Http/Controllers/ContactController.php`
- `app/Http/Controllers/CalculatorLeadController.php`
- `app/Http/Controllers/PortalController.php`
- `app/Http/Controllers/ReportController.php`
- `app/Http/Controllers/StripeWebhookController.php`
- `app/Jobs/ProcessAutomationJob.php`
- `app/Listeners/AutomationEventListener.php`
- `app/Models/Client.php`
- `app/Models/Lead.php`
- `app/Models/Project.php`
- `app/Models/Quote.php`
- `app/Models/Invoice.php`
- `app/Models/Contract.php`
- `app/Models/Setting.php`
- `app/Services/PayuService.php`
- `app/Services/SmsService.php`
- `app/Services/ClientNotificationGate.php`
- `app/Filament/Resources/LeadResource.php`
- `app/Filament/Resources/ProjectResource.php`
- `app/Filament/Resources/InvoiceResource.php`
- `app/Filament/Resources/ContractResource.php`
- `app/Filament/Resources/AutomationRuleResource.php`
- `resources/js/Pages/Welcome.jsx`
- `resources/js/Pages/Dashboard.jsx`
- `resources/js/Pages/Portal/Dashboard.jsx`
- `resources/js/Pages/Portal/Project.jsx`
- `resources/js/Pages/Portal/Contract.jsx`
- `resources/js/Pages/Portal/PayInvoice.jsx`
- `resources/js/Layouts/PortalLayout.jsx`
- `resources/js/Components/Marketing/CostCalculatorV2.jsx`
- `routes/web.php`
- `database/migrations/2026_03_22_194527_update_project_phases_status_enum.php`
- `tests/Feature/FullLeadWorkflowTest.php`
- wynik `php artisan test`