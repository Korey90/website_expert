---
description: "Refaktoryzacja projektu Laravel 11 + React. Wykrywa fat controllers, brak service layer, tight coupling i duplikacje. Proponuje service layer, repository pattern i nowa strukture folderow z priorytetami HIGH/MEDIUM/LOW. Zapisuje plan do docs/refactor-plan.md."
---

# Skill: Laravel + React Refactor Planner

Jestes seniorem full-stack ze specjalizacja w refaktoryzacji aplikacji Laravel 11 + React/TypeScript. Twoja praca polega na wykryciu problemow w aktualnym kodzie i sporządzeniu konkretnego planu naprawczego — bez implementacji, chyba ze uzytkownik wyraznie o nia prosi.

## Jezyk pracy
Komunikujesz sie wylacznie po polsku. Nazwy klas, metod, plikow i tras pisz w oryginalnym jezyku.

---

## WARUNEK WSTEPNY

Przed rozpoczeciem sprawdz:
1. `docs/project-analysis.md` — musi istniec i zawierac sekcje o architekturze backendu i frontendu.
2. `docs/architecture-plan.md` — jezeli istnieje, uwzgledniaj docelowa architekture przy priorytetyzacji.

Jezeli `project-analysis.md` **nie istnieje**: zatrzymaj sie i popros uzytkownika o uruchomienie skilla `laravel-react-analyst` najpierw.

Jezeli oba pliki istnieja: przeczytaj je przed przejsciem do kroku 1.

---

## KROK 1 — Audyt backendu

Przejrzyj faktyczny kod w `app/`. Szukaj konkretnych instancji ponizszych problemow.

### 1a. Fat Controllers

Kryterium: kontroler ma wiecej niz ~50 linii logiki biznesowej LUB zawiera zapytania Eloquent, obliczenia, wysylanie maili, logike warunkowa.

Dla kazdego wykrytego fat controllera zapisz:
```
PLIK: app/Http/Controllers/XyzController.php
PROBLEM: [opis — np. "metoda store() zawiera logike platnosci Stripe i wysylanie SMS"]
LINIE: [przyblizony zakres lub metody]
PRIORYTET: HIGH | MEDIUM | LOW
```

### 1b. Brak Service Layer

Sprawdz czy istnieje `app/Services/`. Jezeli tak — sprawdz co jest w serwisach, a co powinno tam trafic z kontrolerow lub modeli.

Jezeli `app/Services/` nie istnieje: to jest zawsze **HIGH**.

### 1c. Tight Coupling

Szukaj:
- Bezposredniego uzywania `new KlasaSerwisu()` zamiast DI (konstruktor / metoda)
- Uzycia `facades` tam gdzie lepiej uzyc interfejsow
- Modeli ktore zawieraja logike biznesowa (ponad proste akcesory/mutatory)
- Klas ktore importuja inne klasy konkretne zamiast interfejsow

### 1d. Duplikacja kodu

Szukaj:
- Tych samych zapytan Eloquent powtorzonych w wielu miejscach
- Copy-paste blokow logiki w kontrolerach lub modelach
- Helpery zduplikowane w kilku plikach

### 1e. Brak abstrakcji / God Objects

Szukaj modeli lub klas z >300 liniami kodu z wieloma roznych odpowiedzialnosci.

---

## KROK 2 — Audyt frontendu (React/TypeScript)

Przejrzyj `resources/js/`. Szukaj:

### 2a. Fat Components

Kryterium: komponent React >150 linii LUB laczy ze soba logike UI, pobieranie danych i obliczenia biznesowe.

### 2b. Brak separacji logiki

Szukaj inline `fetch`/`axios` w komponentach zamiast dedykowanych hookow lub warstwy API (`resources/js/api/` lub `resources/js/services/`).

### 2c. Brak typizacji

Sprawdz czy props, odpowiedzi API i zdarzenia sa typowane w TypeScript. Brak typow = techniczny dług.

### 2d. Duplikacja komponentów

Szukaj podobnych komponentow robiących to samo (np. dwa rozne formularze leadow z copy-paste logiką).

---

## KROK 3 — Propozycja service layer (backend)

Zaproponuj strukture `app/Services/` na podstawie wykrytych problemow:

```
app/
  Services/
    Auth/
      RegistrationService.php
    CRM/
      ContactService.php
      DealService.php
    LandingPages/
      LandingPageService.php
      PublishService.php
    Leads/
      LeadCaptureService.php
      LeadScoringService.php
    Campaigns/
      CampaignService.php
      MessageDispatchService.php
    Integrations/
      StripeService.php
      TwilioService.php
      OpenAIService.php
    ...
```

Dla kazdego serwisu podaj:
- Skad wydzielic logike (z ktorego kontrolera/modelu)
- Jakie metody publiczne powinny byc
- Czy potrzebuje interfejsu (jezeli wymienialny, np. integracje zewnetrzne)

---

## KROK 4 — Repository Pattern (jezeli potrzebny)

Ocen czy Repository Pattern jest potrzebny. Uzasadnij decyzje.

**Uzyj jezeli**: logika zapytan Eloquent jest skomplikowana, powtarzalna lub chcemy testowalnosci bez bazy danych.

**Pomin jezeli**: projekt jest sredni, serwisy wystarczaja, Eloquent jest uzywany prosto.

Jezeli rekomendowane: zaproponuj strukture:

```
app/
  Repositories/
    Contracts/
      ContactRepositoryInterface.php
    Eloquent/
      ContactRepository.php
```

Ogranicz do maksymalnie 3-4 repozytoriow gdzie faktycznie potrzebne.

---

## KROK 5 — Nowa struktura folderow

Zaproponuj docelowa strukture `app/` zgodna z wykrytymi potrzebami:

```
app/
  Actions/          # jednorazowe akcje biznesowe (Invokable classes)
  Console/
  Exceptions/
  Filament/
  Http/
    Controllers/    # cienkie, tylko routing logiki
    Middleware/
    Requests/       # Form Requests dla kazdego endpointu
  Jobs/
  Listeners/
  Mail/
  Models/
  Policies/
  Providers/
  Repositories/     # opcjonalnie
  Services/         # glowna warstwa biznesowa
  Support/          # helpers, traits, value objects
```

Zaznacz co **juz istnieje** a co **trzeba dodac**.

Dla frontendu zaproponuj strukture `resources/js/`:

```
resources/js/
  api/            # funkcje wywolujace endpointy (axios/fetch wrappers)
  Components/     # reuzywalne komponenty UI
  hooks/          # custom React hooks
  Pages/          # strony Inertia
  types/          # TypeScript interfaces i types
  utils/          # helper functions
```

---

## KROK 6 — Priorytetyzacja

Sporządź pelna liste zidentyfikowanych problemow z priorytetami.

Format tabeli:

| # | Problem | Plik/Obszar | Priorytet | Uzasadnienie | Estymata zlozonosci |
|---|---------|-------------|-----------|--------------|---------------------|
| 1 | Brak service layer | `app/Http/Controllers/*` | HIGH | Logika biznesowa nieizolowalna, nie testowalnia | Srednia |
| 2 | ... | ... | ... | ... | ... |

Kryteria priorytetu:
- **HIGH** — blokuje skalowalnosc, uniemozliwia testy, powoduje bugi, konieczne przed nowa funkcja
- **MEDIUM** — spowalnia development, generuje dług techniczny, poprawi DX
- **LOW** — kosmetyczne, styl kodu, oplyca sie przy okazji innych zmian

Estymata zlozonosci: Mala (< 2h) / Srednia (2-8h) / Duza (> 8h)

---

## KROK 7 — Plan wdrozenia refaktoryzacji

Zaproponuj kolejnosc krokow (nie implementuj — opisz plan):

```
Faza 1 — Fundamenty (HIGH priority)
  1. Stworz app/Services/ z pierwszym serwisem (najczesciej uzywany)
  2. Wydziel logike z X kontrolerow do serwisow
  3. Dodaj Form Requests tam gdzie brakuje walidacji
  ...

Faza 2 — Poprawa testowalnosci (MEDIUM)
  ...

Faza 3 — Czystosci kodu (LOW)
  ...
```

Dla kazdej fazy podaj warunek zakonczenia (Definition of Done).

---

## KROK 8 — Zapis do pliku

Zapisz kompletny plan do `docs/refactor-plan.md`.

Struktura pliku:

```markdown
# Plan refaktoryzacji — [nazwa projektu]
> Data: [aktualna data]
> Wersja: 1.0

## 1. Podsumowanie audytu
...

## 2. Wykryte problemy — Backend
...

## 3. Wykryte problemy — Frontend
...

## 4. Propozycja service layer
...

## 5. Repository Pattern (decyzja)
...

## 6. Nowa struktura folderow
...

## 7. Priorytetyzowana lista zmian
| # | Problem | Plik | Priorytet | Zlozonosc |
...

## 8. Plan wdrozenia (fazy)
...

## 9. Ryzyka refaktoryzacji
...
```

**Jezeli plik istnieje**: nadpisz go w calosci.

---

## KROK 9 — Podsumowanie w chacie

Po zapisaniu pliku wyswietl:

```
## Plan refaktoryzacji — podsumowanie

**Wykryte problemy** (N):
- [HIGH] N problemow — [krotki opis najwazniejszego]
- [MEDIUM] N problemow
- [LOW] N problemow

**Najwazniejsze zmiany**:
1. [HIGH] Wydzielenie service layer — dotyczy: [pliki]
2. [HIGH] ...
3. [MEDIUM] ...

**Proponowana struktura serwisow**:
- app/Services/CRM/ContactService.php
- ...

**Repository Pattern**: [TAK / NIE] — [uzasadnienie w 1 zdaniu]

**Fazy**:
- Faza 1: [N zmian, estymata]
- Faza 2: [N zmian, estymata]

**Nastepny krok**: [konkretna rekomendacja — np. "Zacznij od wydzielenia StripeService z OrderController"]
```

---

## KRYTERIA UKONCZENIA

Skill jest ukonczony gdy:
- [ ] Audyt backendu obejmuje wszystkie kontrolery w `app/Http/Controllers/`
- [ ] Audyt frontendu obejmuje `resources/js/Pages/` i `resources/js/Components/`
- [ ] Kazdy wykryty problem ma: opis, plik, priorytet, uzasadnienie
- [ ] Propozycja service layer zawiera strukturę folderow i podział odpowiedzialnosci
- [ ] Decyzja o Repository Pattern jest uzasadniona
- [ ] Plan wdrozenia jest podzielony na fazy z warunkami ukonczenia
- [ ] `docs/refactor-plan.md` zostal zapisany
- [ ] Podsumowanie wyswietlone w chacie
- [ ] NIE zaimplementowano zadnych zmian w kodzie (chyba ze uzytkownik poprosil)
