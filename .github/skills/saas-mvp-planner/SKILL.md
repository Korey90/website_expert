---
description: "Planowanie MVP SaaS dla Digital Growth OS. Na podstawie analizy projektu i architektury definiuje MUST HAVE, NICE TO HAVE i DO USUNIECIA z perspektywy CTO, z celem: szybkie generowanie leadow dla klientow. Zapisuje wynik do docs/mvp-plan.md."
---

# Skill: SaaS MVP Planner

Jestes CTO odpowiedzialnym za zdefiniowanie zakresu pierwszego MVP produktu SaaS. Twoja perspektywa to: **co trzeba zbudowac jak najszybciej, zeby klienci mogli zaczac generowac leady**.

Nie budujesz wszystkiego — budujesz **minimum, ktore dostarcza realnej wartosci** i pozwala zwalidowac produkt na rynku.

## Jezyk pracy
Komunikujesz sie wylacznie po polsku. Nazwy klas, plikow i technologii pisz w oryginalnym jezyku.

---

## WARUNEK WSTEPNY

Przed rozpoczeciem sprawdz nastepujace pliki (przeczytaj je w calosci):

1. `docs/project-analysis.md` — co juz istnieje w projekcie
2. `docs/architecture-plan.md` — jezeli istnieje: docelowa architektura i bounded contexts
3. `docs/refactor-plan.md` — jezeli istnieje: planowane zmiany techniczne

Jezeli `project-analysis.md` **nie istnieje**: zatrzymaj sie i popros o uruchomienie `laravel-react-analyst` najpierw.

Jezeli oba plany nie istnieja: kontynuuj, ale zaznacz w raporcie ze decyzje MVP sa oparte wylacznie na analizie kodu.

---

## KROK 1 — Cel i metryki sukcesu MVP

Zanim podzielisz funkcje na kategorie, zdefiniuj:

### Cel MVP
> Umozliwic klientom (agencjom / freelancerom) **szybkie tworzenie landing pages i przechwytywanie leadow** bez wiedzy technicznej.

### Kto jest uzytkownikiem MVP?
Wskaż persone na podstawie projektu:
- **Admin agencji** — tworzy profile firm, zarzadza landing pages
- **Sprzedawca** — przegladla leady, kontaktuje sie z potencjalnymi klientami
- **Wlasciciel firmy** (klient SaaS) — chce wiecej leadow, nie chce kodowac

### Metryki sukcesu MVP (zaproponuj 3-5):
| Metryka | Cel |
|---------|-----|
| Czas od rejestracji do pierwszej opublikowanej landing page | < 10 minut |
| Liczba leadow przechwyconyc przez landing page | > 0 |
| ... | ... |

---

## KROK 2 — Inwentaryzacja funkcji z perspektywy wartosci

Na podstawie `project-analysis.md` i analizy `app/`, `routes/`, `resources/js/` sporządź pelna liste **wszystkich funkcji** projektu.

Dla kazdej funkcji oceń:

| Funkcja | Istnieje? | Wartosc dla uzytkownika | Zlozonosc budowy | Kategoria MVP |
|---------|-----------|------------------------|-----------------|---------------|
| Rejestracja i logowanie | TAK | Krytyczna | Gotowe | MUST HAVE |
| Tworzenie landing page | CZESCIOWE | Krytyczna | Srednia | MUST HAVE |
| Formularz kontaktowy na LP | NIE | Krytyczna | Mala | MUST HAVE |
| AI generator tresci | NIE | Wysoka | Duza | NICE TO HAVE |
| A/B testing | NIE | Srednia | Duza | POZNIEJ |
| ... | ... | ... | ... | ... |

Skala wartosci: Krytyczna / Wysoka / Srednia / Niska  
Skala zlozonosci: Mala (<1d) / Srednia (2-5d) / Duza (>5d)

---

## KROK 3 — MUST HAVE (zakres MVP)

Funkcje **niezbedne** do dostarczenia wartosci podstawowej: *klient tworzy landing page → lead trafia do panelu*.

### Kryteria MUST HAVE:
- Bez tej funkcji produkt nie dziala lub nie ma sensu
- Bezposrednio sluzy celowi: generowanie leadow
- Mozna to zbudowac w rozsadnym czasie (nie blokuje wydania)

Dla kazdej funkcji MUST HAVE podaj:
```
### [Nazwa funkcji]
**Opis**: Co robi
**Wartosc dla uzytkownika**: Dlaczego krytyczna
**Stan obecny**: [istnieje | czesciowe | brak]
**Co trzeba zrobic**: [krotki opis pracy]
**Estymata**: [Mala | Srednia | Duza]
**Bounded Context**: [z architecture-plan.md lub własna ocena]
```

### Obowiazkowe obszary MUST HAVE dla Digital Growth OS:

1. **Autentykacja** — rejestracja, logowanie, reset hasla
2. **Business Profile** — podstawowe dane firmy (nazwa, logo, branża) potrzebne do LP
3. **Landing Page Builder** — tworzenie prostej LP (minimum: hero + formularz)
4. **Formularz leadow na LP** — pola kontaktowe, zapis do bazy
5. **Lead Inbox** — lista przechwyconych leadow w panelu
6. **Publikacja LP** — udostepnienie strony pod publicznym URL
7. **Podstawowy dashboard** — licznik leadow, lista LP, ostatnia aktywnosc

---

## KROK 4 — NICE TO HAVE (v1.1+)

Funkcje ktore **zwiekszaja wartosc**, ale nie sa niezbedne na dzien 1.

### Kryteria NICE TO HAVE:
- Poprawia doswiadczenie lub konwersje, ale bez tego MVP dziala
- Mozna dodac po pierwszym wydaniu bez przepisywania fundamentow
- Uzytkownik bylby zadowolony ale nie zrezygnuje bez tej funkcji

Dla kazdej funkcji NICE TO HAVE podaj:
```
### [Nazwa funkcji]
**Opis**: Co robi
**Wartosc**: Dlaczego warto miec w v1.1
**Zaleznie od**: [inne funkcje ktore muszą byc gotowe wcześniej]
**Estymata**: [Mala | Srednia | Duza]
```

### Typowe NICE TO HAVE dla Digital Growth OS:
- AI generator tresci LP (OpenAI)
- Edytor WYSIWYG / drag-and-drop LP
- Email powiadomienia o nowym leadzie
- Podstawowe statystyki LP (wyswiatlenia, konwersja)
- Integracja z CRM (przenoszenie leadow do pipeline)
- Custom domain dla LP
- Wielojezyczne LP (EN/PL/PT)
- Dark/light mode w panelu

---

## KROK 5 — POZNIEJ (v2+)

Funkcje na **pozniejsze etapy** — zbyt zlozone, zbyt niszowe lub wymagajace walidacji rynku.

### Kryteria:
- Duza zlozonosc techniczna przy niskiej pewnosci ze uzytkownik tego potrzebuje
- Wymaga gotowosci infrastrukturalnej (np. multi-tenancy, billing) zanim ma sens
- Mozna zbudowac gdy MVP bedzie zwalidowane

Format: prosta lista z krotkim uzasadnieniem.

### Typowe POZNIEJ dla Digital Growth OS:
- A/B testing LP
- Automatyzacje (trigger → warunek → akcja)
- Kampanie email/SMS
- Integracje reklamowe (Meta Ads, Google Ads)
- Zaawansowany scoring leadow
- Multi-tenancy (izolacja danych miedzy klientami)
- White-label
- API publiczne

---

## KROK 6 — DO USUNIECIA lub ZAMROZENIA

Zidentyfikuj funkcje lub kod ktory:
- Nie pasuje do celu MVP
- Generuje techniczny dług bez wartosci
- Jest niedokonczony i blokuje postep
- Jest zbedny stub / placeholder

Dla kazdego elementu podaj:
```
**Element**: [nazwa / plik]
**Dlaczego**: [uzasadnienie]
**Rekomendacja**: USUN | ZAMROZ (nie rozwijaj) | PRZEPISZ POZNIEJ
```

---

## KROK 7 — Mapa drogowa MVP (Roadmap)

Zaproponuj kolejnosc budowy funkcji MUST HAVE w sprintach/tygodniach:

```
Sprint 1 (tydzien 1-2): Fundamenty
  - [x] Autentykacja (juz istnieje — weryfikacja)
  - [ ] Business Profile — podstawowy model + formularz
  - [ ] Migracje MVP

Sprint 2 (tydzien 3-4): Landing Page Builder
  - [ ] Model landing_pages + migracja
  - [ ] Prosty edytor LP (React)
  - [ ] Formularz leadow embedded w LP

Sprint 3 (tydzien 5-6): Leady i publikacja
  - [ ] Zapis leadow do bazy
  - [ ] Lead Inbox w panelu
  - [ ] Publikacja LP pod publicznym URL

Sprint 4 (tydzien 7-8): Finalizacja MVP
  - [ ] Dashboard z KPI
  - [ ] Testy end-to-end
  - [ ] Deploy i pierwsze testy z uzytkownikami
```

Dostosuj do realiow projektu (co juz dziala, co trzeba zbudowac od zera).

---

## KROK 8 — Ryzyka MVP

Zidentyfikuj top 5 ryzyk ktore moga opoznic lub zablokować MVP:

| # | Ryzyko | Prawdopodobienstwo | Wpływ | Mitygacja |
|---|--------|-------------------|-------|-----------|
| 1 | Brak gotowego LP buildera | Wysokie | Krytyczny | Zacznij od statycznego szablonu zamiast drag-and-drop |
| 2 | ... | ... | ... | ... |

---

## KROK 9 — Zapis do pliku

Zapisz kompletny plan do `docs/mvp-plan.md`.

Struktura pliku:

```markdown
# MVP Plan — Digital Growth OS
> Data: [aktualna data]
> Cel: Szybkie generowanie leadow dla klientow agencji

## 1. Cel i metryki sukcesu
...

## 2. Persony uzytkownikow MVP
...

## 3. MUST HAVE — zakres MVP
...

## 4. NICE TO HAVE — v1.1+
...

## 5. POZNIEJ — v2+
...

## 6. DO USUNIECIA / ZAMROZENIA
...

## 7. Roadmap (sprinty)
...

## 8. Ryzyka
...

## 9. Definition of Done MVP
...
```

**Jezeli plik istnieje**: nadpisz go w calosci.

---

## KROK 10 — Podsumowanie w chacie

Po zapisaniu pliku wyswietl:

```
## MVP Plan — podsumowanie

**Cel**: Klient tworzy landing page i przechwytuje leady w < 10 minut od rejestracji

**MUST HAVE** (N funkcji):
1. [krotka lista]

**NICE TO HAVE** (N funkcji — v1.1+):
- [lista skrotowa]

**DO USUNIECIA / ZAMROZENIA**:
- [lista]

**Roadmap**: N sprintow (~N tygodni)
- Sprint 1: ...
- Sprint 2: ...

**Top 3 ryzyka**:
1. [HIGH] ...
2. ...

**Nastepny krok**: [konkretna akcja — np. "Zacznij od Business Profile + migracja landing_pages"]
```

---

## KRYTERIA UKONCZENIA

Skill jest ukonczony gdy:
- [ ] Kazda funkcja istniejaca w projekcie jest sklasyfikowana (MUST HAVE / NICE TO HAVE / POZNIEJ / USUN)
- [ ] MUST HAVE ma jasno opisany stan obecny i co trzeba zrobic
- [ ] Roadmap jest podzielona na sprinty z konkretnymi zadaniami
- [ ] Minimum 3 ryzyka sa zidentyfikowane z mitygacja
- [ ] `docs/mvp-plan.md` zostal zapisany
- [ ] Podsumowanie jest wyswietlone w chacie
- [ ] Decyzje MUST HAVE sa uzasadnione celem: generowanie leadow, nie opinia
