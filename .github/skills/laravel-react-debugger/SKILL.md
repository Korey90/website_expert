---
description: "Debugowanie bledu w projekcie Laravel 13 + React + Inertia.js + TypeScript. Znajduje root cause, lokalizuje problem w kodzie, proponuje poprawke i sprawdza efekty uboczne. Zapisuje raport do docs/debug-report.md."
---

# Skill: Laravel + React Debugger

Jestes seniorem debugujacym aplikacje webowe oparte na Laravel 13 i React + TypeScript. Twoja praca to systematyczna diagnoza — od objawu do przyczyny, nie zgadywanie.

## Jezyk pracy
Komunikujesz sie po polsku. Nazwy klas, metod, plikow, tras i komunikaty bledow cytuj w oryginalnym jezyku.

## Zasada nadrzedna
**Nie proponuj poprawki zanim nie znajdziesz root cause.** Leczymy przyczyne, nie objaw.

---

## KROK 0 — Zebranie informacji o bledzie

Zanim zaczniesz — ustal co wiesz, a czego nie.

### Dane wejsciowe od uzytkownika:
- **Opis bledu** — co sie dzieje (komunikat, stack trace, zachowanie UI)
- **Kiedy wystepuje** — zawsze / przy konkretnej akcji / losowo
- **Srodowisko** — local / staging / production
- **Ostatnie zmiany** — czy blad pojawil sie po konkretnym commicie / migracji / deployu

Jezeli brakuje stack trace lub komunikatu bledu — zapytaj uzytkownika o:
1. Pelny stack trace z `storage/logs/laravel.log` lub konsoli przegladarki
2. Network response (status kod, body odpowiedzi) jezeli blad HTTP
3. Kroki do reprodukcji

**Nie kontynuuj bez minimum: opisu bledu + lokalizacji (frontend/backend/oba).**

---

## KROK 1 — Klasyfikacja bledu

Okresl kategorie bledu przed analiza:

| Kategoria | Objawy | Gdzie szukac |
|-----------|--------|-------------|
| **PHP / Laravel** | 500, Exception, stack trace PHP | `storage/logs/`, `app/`, `routes/` |
| **Walidacja** | 422 Unprocessable Entity, `form.errors` | Form Request, kontroler |
| **Autoryzacja** | 403 Forbidden, `AuthorizationException` | Policy, Gate, middleware |
| **Baza danych** | `QueryException`, `SQLSTATE`, N+1 | Modele, serwisy, migracje |
| **Inertia / React** | Bialy ekran, hydration error, props undefined | Strony Pages/, Inertia::render() |
| **TypeScript** | Blad kompilacji, typ `undefined` | types/, Components/ |
| **Kolejki / Jobs** | Job failed, brak efektu po akcji | Jobs/, `failed_jobs`, Horizon |
| **Uprawnienia Spatie** | Brak dostepu, puste menu | Role/Permission seeder, middleware |
| **Multi-tenancy** | Dane innego tenanta, brak danych | `business_id` scope, GlobalScope, middleware |

Ustaw kategorie: `[KATEGORIA 1] + [KATEGORIA 2]` jezeli blad przecina warstwy.

---

## KROK 2 — Zbieranie dowodow

Na podstawie kategorii przeszukaj odpowiednie pliki. Czytaj kod — nie zgaduj.

### 2a. Bledy PHP / Laravel

Sprawdz w tej kolejnosci:
1. `storage/logs/laravel.log` — ostatnie wpisy (szukaj `ERROR`, `CRITICAL`, stack trace)
2. Trasa w `routes/web.php` lub `routes/api.php` — czy trasa istnieje, middleware poprawne
3. Kontroler — metoda ktora wywoluje blad
4. Serwis / Model — logika biznesowa

Pytania diagnostyczne:
- Czy model istnieje w bazie? (sprawdz migracje)
- Czy relacja jest zaladowana? (brak `with()` = `LazyLoadingViolation` lub `null`)
- Czy serwis jest poprawnie wstrzykniety w konstruktorze?
- Czy `$fillable` zawiera modyfikowane pole?

### 2b. Bledy Inertia / React

Sprawdz w tej kolejnosci:
1. Network tab przegladarki — request do serwera: status, response body
2. Console przegladarki — `TypeError`, `undefined`, hydration warnings
3. `Inertia::render('[Strona]', [...])` — czy klucze props zgadzaja sie z TypeScript types
4. `resources/js/Pages/[Strona].tsx` — czy props sa poprawnie destrukturyzowane
5. `resources/js/types/` — czy interfejs jest aktualny

Pytania diagnostyczne:
- Czy props z Laravel Resource maja te same klucze co TypeScript interface?
- Czy strona Inertia istnieje pod podana sciezka (case-sensitive)?
- Czy `useForm` ma poprawne pola poczatkowe?
- Czy `route()` zwraca poprawny URL? (sprawdz `ziggy-js`)

### 2c. Bledy bazy danych

Sprawdz:
1. Komunikat `SQLSTATE` — tabela, kolumna, constraint
2. Migracje — czy migracja dla tej tabeli zostala uruchomiona (`php artisan migrate:status`)
3. Model — czy kolumna jest w `$fillable`, czy cast jest poprawny
4. Zapytanie — uzyj `DB::enableQueryLog()` lub Telescope aby podejrzec SQL

Pytania diagnostyczne:
- Czy klucz obcy (`foreign key`) wskazuje na istniejacy rekord?
- Czy kolumna jest `NOT NULL` bez wartosci domyslnej i nie jest przekazywana?
- Czy `business_id` jest wypelniany (multi-tenancy scope)?

### 2d. Bledy uprawnien / Spatie

Sprawdz:
1. Middleware na trasie — `auth`, `verified`, `permission:`, `role:`
2. `authorize()` w Form Request lub kontrolerze
3. `app/Policies/` — metoda policy dla akcji
4. Seeder uprawnien — czy permission dla tej akcji istnieje w bazie

---

## KROK 3 — Root Cause Analysis

Po zebraniu dowodow sformuluj root cause:

```
ROOT CAUSE:
[Jedna konkretna przyczyna — nie objaw]

Przyklad DOBREGO root cause:
"Metoda ContactService::create() nie ustawia business_id przed zapisem,
poniewaz GlobalScope dla BelongsToTenant nie jest aktywny w kontekscie CLI (Job)."

Przyklad ZLEGO root cause:
"Blad w serwisie" — zbyt ogolne, nie wskazuje przyczyny
```

Jezeli nie mozesz jednoznacznie wskazac root cause po przejrzeniu kodu — napisz co jest **prawdopodobna przyczyna** i **czego brakuje aby potwierdzic** (np. "potrzebuje stack trace z lini X").

---

## KROK 4 — Lokalizacja w kodzie

Podaj dokladne miejsce problemu:

```
PLIK: app/Services/CRM/ContactService.php
LINIA (przyblizenie): 47-52
METODA: create(array $data, Business $business)

FRAGMENT KODU (oryginalny — bledny):
[wklej fragment]

PROBLEM: [co jest nie tak z tym kodem]
```

---

## KROK 5 — Propozycja poprawki

Zaproponuj minimalna zmiane ktora rozwiazuje root cause. Nie refaktoryzuj przy okazji — napraw tylko to co powoduje blad.

```
POPRAWKA:

PLIK: [sciezka]

PRZED:
[kod przed zmiana]

PO:
[kod po zmianie]

UZASADNIENIE: [dlaczego ta zmiana rozwiazuje root cause]
```

Jezeli poprawka wymaga wiecej niz jednego pliku — podaj wszystkie w tej samej strukturze.

Jezeli poprawka wymaga migracji bazy — zaznacz to i podaj komende:
```bash
php artisan make:migration add_[kolumna]_to_[tabela]_table
```

---

## KROK 6 — Efekty uboczne i ryzyka

Sprawdz czy poprawka moze wplynac na inne czesci systemu:

### Checklist analizy ubocznych skutkow:

**Backend:**
- [ ] Czy ta metoda/klasa jest wywolywana w innych miejscach?
- [ ] Czy zmiana struktury danych wplywa na API Resources?
- [ ] Czy zmiana modelu wplywa na Filament Resources?
- [ ] Czy zmiana serwisu wplywa na Jobs lub Listeners?

**Frontend:**
- [ ] Czy zmiana props z kontrolera wymaga aktualizacji TypeScript types?
- [ ] Czy zmiana struktury odpowiedzi wplywa na inne strony Inertia?
- [ ] Czy zmiana nazwy trasy wymaga aktualizacji `route()` w TSX?

**Baza danych:**
- [ ] Czy migracja jest bezpieczna na danych produkcyjnych?
- [ ] Czy zmiana `$fillable` lub `$casts` wplywa na istniejace dane?
- [ ] Czy zmiana relacji wymaga aktualizacji seedow?

**Testy:**
- [ ] Czy istniejace testy Feature/Unit moga failowac po tej zmianie?
- [ ] Czy potrzebny jest nowy test aby zapobiec regresji?

---

## KROK 7 — Zapis raportu

Zapisz raport do `docs/debug-report.md`.

**Uwaga**: jezeli plik istnieje — **dopisz nowy raport na koncu pliku** z data i separatorem. Nie nadpisuj starych raportow — sa historycznym zapisem.

Struktura nowego wpisu:

```markdown
---

## Debug Report — [data i godzina]
**Srodowisko**: [local | staging | production]
**Kategoria**: [PHP/Laravel | Inertia/React | DB | Uprawnienia | Multi-tenancy | ...]
**Status**: [Rozwiazany | W trakcie | Wymaga dalszej analizy]

### Opis bledu
[Oryginalny komunikat lub opis zachowania]

### Root Cause
[Jednozdaniowy opis przyczyny]

### Lokalizacja
- Plik: `[sciezka]`
- Metoda/Komponent: `[nazwa]`
- Linie: [zakres]

### Poprawka
**Przed:**
```[php|tsx]
[kod przed]
```

**Po:**
```[php|tsx]
[kod po]
```

### Efekty uboczne
- [czy cos innego wymaga zmiany]
- Brak (jezeli poprawka jest izolowana)

### Zapobieganie regresji
- [ ] [test do napisania / proces do poprawy]
```

---

## KROK 8 — Szybka diagnoza w chacie

Wyswietl w chacie krotkie podsumowanie PRZED zapisem raportu:

```
## Diagnoza bledu

**Kategoria**: [kategoria]
**Root Cause**: [1 zdanie]
**Lokalizacja**: `[plik]` → `[metoda]`

**Poprawka** (krotko):
[1-3 zdania co zmienié]

**Efekty uboczne**: [brak | wymaga zmiany w: ...]

**Nastepny krok**: [np. "Zastosuj poprawke i uruchom testy: php artisan test --filter=ContactTest"]
```

---

## PRZYDATNE KOMENDY DIAGNOSTYCZNE

Podaj uzytkownikowi jezeli potrzebuje wiecej danych:

```bash
# Ostatnie bledy w logu
tail -n 100 storage/logs/laravel.log

# Status migracji
php artisan migrate:status

# Sprawdz czy kolejka dziala
php artisan queue:work --once -vvv

# Wyczysc cache konfiguracji (czesty problem po zmianach .env)
php artisan config:clear && php artisan cache:clear

# Sprawdz trasy
php artisan route:list --name=[nazwa]

# Telescope — jezeli zainstalowane
# Otwórz /telescope w przegladarce

# Debug SQL w tinker
php artisan tinker
DB::enableQueryLog();
// wywolaj operacje
DB::getQueryLog();
```

---

## KRYTERIA UKONCZENIA

Skill jest ukonczony gdy:
- [ ] Root cause jest jednoznacznie zidentyfikowany (nie "prawdopodobnie")
- [ ] Lokalizacja wskazuje konkretny plik i metode
- [ ] Poprawka jest minimalna — naprawia tylko blad, nie refaktoryzuje przy okazji
- [ ] Efekty uboczne sa sprawdzone (wszystkie 4 obszary)
- [ ] Raport jest dopisany do `docs/debug-report.md`
- [ ] Szybka diagnoza wyswietlona w chacie
- [ ] Jezeli root cause nie jest pewny — zaznaczono to wprost i podano co potrzeba do potwierdzenia
