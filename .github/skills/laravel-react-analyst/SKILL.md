---
description: "Dokladna analiza projektu Laravel 13 + FilamentPHP 3 + Inertia.js + React + TypeScript. Wykrywa istniejace funkcje, architekture, integracje, role/uprawnienia Spatie, ocenia jakosc kodu i ryzyka skalowania. Zapisuje raport do docs/project-analysis.md."
---

# Skill: Laravel + React Project Analyst

Jestes ekspertem analizy kodu aplikacji webowych opartych na Laravel 11, FilamentPHP 3, Inertia.js i React + TypeScript.

## Jezyk pracy
Komunikujesz sie wylacznie po polsku. Nazwy klas, metod, plikow, tras i bibliotek pisz w oryginalnym jezyku.

---

## CEL

Przeprowadz dokladna analize projektu i zapisz raport do `docs/project-analysis.md`.

**Zasada nadrzedna**: NIE zgaduj. Analizuj faktyczny kod z repozytorium.

---

## KROK 1 — Zbieranie kontekstu

Przeczytaj w tej kolejnosci:

1. `README.md`
2. `composer.json` — zainstalowane pakiety PHP, wersje
3. `package.json` — zainstalowane pakiety JS/TS
4. `docs/` — wszystkie pliki (plany, notatki, poprzednia analiza)
5. `routes/web.php`, `routes/auth.php`, `routes/api.php` (jezeli istnieje)
6. `app/` — struktura folderow (Models, Http, Services, Filament, Livewire, Jobs, Listeners, Actions, Automation)
7. `database/migrations/` — lista tabel, relacje
8. `database/seeders/` — szczegolnie seedy ról i uprawnien Spatie
9. `resources/js/` — struktura Pages/, Components/, hooks/, store/
10. `szablon/` — jezeli istnieje (szablony marketingowe)
11. `.github/agents/` — istniejace agenty (kontekst projektu)

**Nie pomijaj zadnego z tych katalogów.** Jezeli katalog nie istnieje, odnotuj to w raporcie.

---

## KROK 2 — Feature Inventory

Na podstawie tras (`routes/`) i kontrolerow/Livewire/Filament wygeneruj liste **faktycznie istniejacych** funkcjonalnosci.

Grupuj wedlug obszarow:
- **Autentykacja i autoryzacja** (login, rejestracja, role, polityki)
- **CRM** (kontakty, firmy, pipeline sprzedazowy)
- **Powiadomienia** (typy, kanaly — email/SMS/push/in-app)
- **Integracje zewnetrzne** (Stripe, Twilio, OpenAI i inne)
- **Panel administracyjny** (Filament resources, dashboardy)
- **Frontend publiczny** (Inertia pages, szablony)
- **Automatyzacje i kolejki** (Jobs, Events, Listeners, Automation)
- **Wielojezycznosc** (jezyki, pliki lang/)

Dla kazdej funkcji podaj:
- Skrot opisu
- Kluczowe pliki/klasy (np. `app/Http/Controllers/CrmController.php`)
- Status: `zaimplementowane` / `czesciowe` / `stub`

---

## KROK 3 — Architektura backendu

Opisz:

1. **Modele i relacje** — kluczowe modele z `app/Models/`, ich relacje (jeden do wielu, wielka tabela piwotek itp.)
2. **Service layer** — czy istnieje `app/Services/`? Co jest w serwisach?
3. **Kontrolery** — cienkie czy grube? Czy logika biznesowa jest w kontrolerach?
4. **Filament** — jakie Resources, Pages, Widgets sa zaimplementowane?
5. **Kolejki i zdarzenia** — Jobs, Events, Listeners, harmonogramy (`Console/Kernel.php` lub `routes/console.php`)
6. **Wzorce** — czy uzyto Repository Pattern, Actions, DTO?

---

## KROK 4 — Architektura frontendu

Ustal **jednoznacznie**: Inertia.js + React, Livewire, czy oba?

Sprawdz:
- `resources/js/` — czy sa Pages/ i Components/?
- `composer.json` — `inertiajs/inertia-laravel`, `livewire/livewire`
- `package.json` — `@inertiajs/react`, `react`, `typescript`
- widoki Blade vs pliki `.tsx`/`.jsx`

Opisz:
- Struktura komponentow React (jezeli uzyty)
- Zarzadzanie stanem (Zustand, Context, Redux, useReducer)
- TypeScript — czy typy sa zdefiniowane? Gdzie?
- Uzycie Tailwind: klasy utility, dark mode, responsywnosc

---

## KROK 5 — Integracje zewnetrzne

Dla kazdej integracji podaj:
- Nazwa i cel
- Pakiet (composer/npm)
- Kluczowe pliki implementacji
- Status: `dziala` / `czesciowe` / `nieuzywane`

Sprawdz minimum:
- **Stripe** — platnosci, subskrypcje (Laravel Cashier?)
- **Twilio** — SMS, komunikacja
- **OpenAI** — generowanie tresci, AI features
- **Mail** — SMTP, Mailgun, SES?
- **Reverb/Pusher** — WebSockets, real-time
- **Meta Ads / Google Ads** — jezeli wzmianki w kodzie

---

## KROK 6 — Role i uprawnienia (Spatie)

Sprawdz:
- `database/seeders/` — `RoleSeeder`, `PermissionSeeder` lub podobne
- `app/Models/` — czy modele uzywaja `HasRoles`?
- `config/permission.php` — konfiguracja
- polityki (`app/Policies/`) — jakie modele maja polityki?

Sporządź tabele:

| Rola | Uprawnienia (kluczowe) | Plik seedera |
|------|------------------------|--------------|
| ... | ... | ... |

Jezeli Spatie nie jest uzywane, odnotuj to wprost.

---

## KROK 7 — Ocena jakosci kodu

Oceń w skali: **DOBRY / SREDNI / WYMAGA POPRAWY**

| Aspekt | Ocena | Uzasadnienie |
|--------|-------|--------------|
| Separacja warstw (MVC, service layer) | | |
| Pokrycie testami (Feature/Unit) | | |
| Typizacja TypeScript | | |
| Konsekwencja konwencji nazewniczych | | |
| Wielojezycznosc (i18n) | | |
| Dokumentacja kodu | | |
| Obsluga bledow i logowanie | | |

---

## KROK 8 — Ryzyka skalowania

Zidentyfikuj konkretne ryzyka. Dla kazdego podaj:
- **Opis ryzyka** — co moze byc problemem przy wzroscie
- **Lokalizacja** — konkretne pliki/klasy
- **Priorytet**: HIGH / MEDIUM / LOW
- **Propozycja rozwiazania** (ogolna, bez implementacji)

Sprawdz szczegolnie:
- Brak multi-tenancy (izolacja danych miedzy klientami)
- Tight coupling (logika biznesowa w kontrolerach)
- N+1 queries (brak eager loading)
- Brak cache'owania
- Zbyt duze klasy / God Objects

---

## KROK 9 — Zapis raportu

Zapisz kompletny raport do `docs/project-analysis.md`.

Struktura pliku:

```markdown
# Analiza projektu — [nazwa projektu]
> Data: [aktualna data]

## 1. Feature Inventory
...

## 2. Architektura backendu
...

## 3. Architektura frontendu
...

## 4. Integracje zewnetrzne
...

## 5. Role i uprawnienia
...

## 6. Ocena jakosci kodu
...

## 7. Ryzyka skalowania
...

## 8. Rekomendacje i priorytety
...
```

**Jezeli plik istnieje**: nadpisz go calkowicie. Nie dopisuj do starego.

---

## KROK 10 — Podsumowanie w chacie

Po zapisaniu pliku wyswietl w chacie krotkie podsumowanie:

```
## Podsumowanie analizy

**Stack**: Laravel X.X + FilamentPHP X + [Inertia+React | Livewire] + TypeScript

**Istniejace funkcje** (N):
- [lista skrotowa]

**Integracje**: Stripe, Twilio, ...

**Kluczowe ryzyka**:
1. [HIGH] ...
2. [MEDIUM] ...

**Nastepne kroki** (rekomendacja):
1. ...
```

---

## KRYTERIA UKONCZENIA

Skill jest ukonczony gdy:
- [ ] Wszystkie 8 sekcji raportu jest wypelnione konkretnymi danymi z kodu
- [ ] Brak stwierdzen "prawdopodobnie", "mozliwe ze", "zakladam ze" bez podstawy w kodzie
- [ ] `docs/project-analysis.md` zostal zapisany
- [ ] Podsumowanie wyswietlone w chacie
- [ ] Jezeli cos bylo niejasne — zadano pytanie uzytkownikowi PRZED zapisem raportu
