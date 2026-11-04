# Plan migracji: React 18 → 19 oraz Inertia.js v2 → v3

**Status:** ✅ Wykonane i zacommitowane (2026-10-06, 2 commity: `90befc9` React 19, `13d2049` Inertia v3)
**Utworzono:** 2026-10-06
**Kontekst:** Celowo wydzielone z ogólnej aktualizacji zależności (`docs/plans/...` — zob. też wpis w `/memories/repo/agent-skills-notes.md`), ponieważ Inertia v3 wymaga React 19 jako twardego minimum, a to największy skok wersji w tej sesji modernizacji. Zalecane jako osobne, zaplanowane zadanie — nie łączyć z innymi pracami.

## 1. Dlaczego osobno

Dzień wcześniej nieprzemyślany skok wersji Filament/Livewire na produkcji spowodował realną awarię logowania 2FA. Ten sam ostrożny tryb stosujemy tutaj: React 19 to twardy wymóg Inertia v3 (`@inertiajs/react` v3 odmawia współpracy z React 18), co oznacza migrację całego drzewa komponentów na raz — nie da się tego zrobić częściowo ani stopniowo wycofać pojedynczych stron.

## 2. Wersje docelowe

| Pakiet | Obecnie | Cel |
|---|---|---|
| `react` / `react-dom` (npm) | 18.3.1 | ^19.3.0 |
| `@inertiajs/react` (npm) | 2.3.28 | ^3.8.0 |
| `inertiajs/inertia-laravel` (composer) | 2.0.28 | ^3.5.1 |

## 3. Audyt ryzyka — już wykonany

### 3.1 Zależności trzecich stron pod kątem React 19

Sprawdzone bezpośrednio w `node_modules/*/package.json` (`peerDependencies`):

| Pakiet | Deklarowane wsparcie React 19 |
|---|---|
| `@headlessui/react` (v2.x) | ✅ `"react": "^18 \|\| ^19 \|\| ^19.0.0-rc"` |
| `react-google-recaptcha-v3` | ✅ `"react": "^16.3 \|\| ^17.0 \|\| ^18.0 \|\| ^19.0"` |
| `@testing-library/react` (v16.x) | ✅ `"react": "^18.0.0 \|\| ^19.0.0"` |
| `@heroicons/react` | Czyste komponenty SVG, brak zależności od wewnętrznego API Reacta — ryzyko minimalne |

**Wniosek:** żadna z głównych bibliotek UI używanych w projekcie nie blokuje przejścia na React 19. To najważniejsze ryzyko tego typu migracji i jest już rozwiane.

### 3.2 Wzorce kodu łamiące kompatybilność v3 — przeszukano cały `resources/js` i `resources/views`

Szukane wzorce: `Inertia::lazy(`, `router.cancel(`, nasłuchy zdarzeń `'invalid'`/`'exception'`, atrybut `<title inertia>`/`inertia>` w blade, `ReactDOM.render(`, komponenty layout przypisane jako arrow function (`Component.layout = (props) => ...`), bezpośrednie importy `lodash-es`/`qs`.

**Wynik — tylko JEDNO miejsce wymaga zmiany:**

```blade
<!-- resources/views/app.blade.php, linia 7 -->
<title inertia>{{ config('app.name', 'Website Expert') }}</title>
<!-- → -->
<title data-inertia>{{ config('app.name', 'Website Expert') }}</title>
```

Brak użycia `Inertia::lazy()` (deprecated od v2, usunięty w v3), brak `router.cancel()`, brak nasłuchów na przemianowane zdarzenia, brak legacy `ReactDOM.render()` (już używamy `createRoot` w `resources/js/app.jsx` i `resources/js/ssr.jsx`), brak arrow-function layoutów, brak bezpośrednich importów `lodash-es`/`qs`.

### 3.3 Punkty wejścia aplikacji — już w bezpiecznym, współczesnym wzorcu

`resources/js/app.jsx` i `resources/js/ssr.jsx` już używają `createRoot`/`createInertiaApp` w stylu zgodnym z React 19 — nie wymaga przepisania struktury, tylko weryfikacji po podniesieniu wersji.

## 4. Pełna lista breaking changes Inertia v3 (z oficjalnego przewodnika `inertiajs.com/upgrade-guide`)

Do sprawdzenia/zastosowania podczas migracji, nawet jeśli dziś nie wykryto ich w kodzie (biblioteki/community snippets mogą je wprowadzić później):

1. **Wymagania:** PHP 8.2+ (mamy 8.3 ✅), Laravel 11+ (mamy 13.34 ✅), React 19+ (ten plan).
2. **Axios usunięty z rdzenia** — wbudowany klient XHR zastępuje Axios. Jeśli coś w projekcie importuje `axios` bezpośrednio z poziomu Inertia (nie z własnego `package.json`), przestanie działać. U nas `axios` jest osobną, bezpośrednią zależnością (`dependencies` w `package.json`) — zostaje bez zmian, nie jest to re-eksport z Inertii.
3. **`qs` i `lodash-es` nie są już dołączane transytywnie** — jeśli kod gdzieś polega na przechodniej dostępności (bez własnego `npm install qs`/`lodash-es`), trzeba je dodać jawnie. Nie znaleziono takich importów.
4. **Zmiana nazw zdarzeń:** `invalid` → `httpException`, `exception` → `networkError`. Brak użycia w kodzie, ale sprawdzić ponownie tuż przed migracją (`router.on('invalid'`/`router.on('exception'`).
5. **`router.cancel()` → `router.cancelAll()`** — brak użycia w kodzie.
6. **Usunięta przestrzeń `future` w `createInertiaApp`** — brak użycia (nie konfigurujemy `future.*`).
7. **Atrybut `inertia` → `data-inertia`** w elementach `<head>` — jedna zmiana, opisana w 3.2.
8. **Usunięte eksporty `hideProgress()`/`revealProgress()`** — brak użycia (korzystamy z wbudowanego `progress: { color: ... }` w `createInertiaApp`, nie z osobnych importów).
9. **React `<Deferred>` nie resetuje się już do fallbacku przy partial reload** — brak użycia `<Deferred>` w kodzie (sprawdzić ponownie przy migracji, jeśli ktoś doda tę funkcję między teraz a wtedy).
10. **`useForm` resetuje `processing`/`progress` dopiero w `onFinish`, nie natychmiast** — możliwy wpływ na UX formularzy z `processing` sprawdzane poza `onFinish`. **Wymaga przeglądu wszystkich `useForm()` w projekcie pod kątem logiki zależnej od natychmiastowego resetu.**
11. **`Inertia::lazy()`/`LazyProp` usunięte** → zastąpić `Inertia::optional()`. Brak użycia w kodzie.
12. **Restrukturyzacja `config/inertia.php`** (`testing.page_paths` → `pages.paths` itd.) — projekt **nie ma opublikowanego `config/inertia.php`** (celowo, korzysta z domyślnych wartości paczki), więc nie dotyczy, ale po `composer require inertiajs/inertia-laravel:^3.0` warto **opublikować i przejrzeć nowy plik**, żeby celowo zdecydować czy nadal nie potrzebujemy customizacji.
13. **Usunięte `Inertia\Testing\Concerns\*` traity** (deprecated od v1) — brak użycia, testy korzystają z `AssertableInertia` (sprawdzone wcześniej w `ServicePageControllerTest` itp.).
14. **Layout jako arrow function** musi być owinięty w tablicę (`Component.layout = [Layout]`) — brak takiego wzorca w kodzie (sprawdzić ponownie przy migracji).
15. **ES2022 jako target buildu** (wcześniej ES2020) — sprawdzić wsparcie przeglądarek docelowych klienta (prawdopodobnie bez wpływu, strona B2B).
16. **Pakiety tylko ESM** — projekt już używa `"type": "module"` w `package.json` ✅.
17. **Middleware priority** — automatyczne, bez akcji.
18. **SSR w dev działa automatycznie z nowym `@inertiajs/vite`** (opcjonalny plugin) — **nie jest wymagany**, obecny ręczny setup (`ssr.jsx` + `vite build --ssr` + `php artisan inertia:start-ssr` pod systemd na produkcji) nadal działa w v3 bez zmian. Rozważyć adopcję później jako osobne usprawnienie DX, nie jako część tej migracji.

## 5. Kolejność wykonania

### Krok 0 — przygotowanie
- [ ] Upewnić się, że `git status` jest czysty, osobny branch/commit punktu startowego.
- [ ] Jeszcze raz uruchomić grep z sekcji 3.2 tuż przed startem (kod mógł się zmienić od dziś).

### Krok 1 — React 18 → 19 (izolowany krok, commit osobno)
```powershell
npm install react@^19 react-dom@^19 --save
npm run build
npm run build:ssr
npm run lint
npm test
```
- [ ] Sprawdzić manualnie (Playwright/przeglądarka) kluczowe strony: `/`, `/pl/strony-internetowe-uk`, `/calculate`, panel `/admin` (Filament nie korzysta z Reacta bezpośrednio, ale warto potwierdzić że nic się nie posypało globalnie).
- [ ] Zwrócić uwagę na konsolę przeglądarki pod kątem nowych ostrzeżeń React 19 (np. zmiany w obsłudze `ref` jako zwykłego propa, zmiany w `useEffect` w trybie Strict Mode — StrictMode w React 19 nadal podwójnie wywołuje efekty w dev, to oczekiwane).
- [ ] Dopiero po zielonym buildzie/testach/ręcznej weryfikacji — **commit**.

### Krok 2 — Inertia v2 → v3 (po React 19 już ustabilizowanym)
```powershell
composer require inertiajs/inertia-laravel:^3.0 --with-all-dependencies
npm install @inertiajs/react@^3.0 --save
```
- [ ] Opublikować i przejrzeć nowy config: `php artisan vendor:publish --provider="Inertia\ServiceProvider" --force` (punkt 12 wyżej) — zdecydować świadomie czy zostawić domyślny.
- [ ] `php artisan view:clear` (wymagane — zmienił się output dyrektywy `@inertia`).
- [ ] Zmienić `resources/views/app.blade.php`: `<title inertia>` → `<title data-inertia>`.
- [ ] Ponownie przeszukać kod pod kątem punktów 4–5, 9, 14 z sekcji 4 (na wypadek zmian między teraz a wtedy).
- [ ] Przejrzeć WSZYSTKIE użycia `useForm()` pod kątem punktu 10 (timing `processing`/`progress`).

### Krok 3 — Build, testy, weryfikacja SSR
```powershell
npm run build
npm run build:ssr
php artisan test
npm test
npm run lint
```
- [ ] Zweryfikować surowym `curl`/Playwright, że SSR nadal wstrzykuje realny `<h1>`/treść (ten sam test co przy stawianiu SSR na produkcji — zob. `/memories/repo/agent-skills-notes.md`).
- [ ] Sprawdzić w przeglądarce konsolę pod kątem `httpException`/`networkError` jeśli gdziekolwiek dodano nasłuchy zdarzeń Inertia między dziś a wtedy.

### Krok 4 — Deploy (dopiero gdy Krok 1–3 w pełni zielone)
- [ ] Użyć tej samej checklisty deployu co przy dzisiejszej aktualizacji (`composer install --no-dev`, `npm install --legacy-peer-deps`, `npm run build && npm run build:ssr`, `php artisan optimize:clear`, **restart `php8.3-fpm`**, **restart `inertia-ssr`**, **`php artisan queue:restart`**).
- [ ] Przetestować na produkcji w pierwszej kolejności: logowanie z 2FA (niezwiązane z Inertią, ale to był wczorajszy punkt zapalny — zawsze sprawdzać po każdym większym deployu), następnie stronę główną + jedną stronę z formularzem korzystającym z `useForm()`.

## 6. Plan awaryjny

Jeśli po Kroku 1 (React 19) coś się posypie w sposób trudny do szybkiego naprawienia:
```powershell
npm install react@^18 react-dom@^18 --save
npm run build && npm run build:ssr
```
Jeśli po Kroku 2 (Inertia v3) coś się posypie:
```powershell
composer require inertiajs/inertia-laravel:^2.0 --with-all-dependencies
npm install @inertiajs/react@^2.0 --save
git checkout -- resources/views/app.blade.php   # cofnąć <title data-inertia> → <title inertia>
npm run build && npm run build:ssr
```
React 19 może zostać nawet jeśli Inertia v3 zostanie wycofana — to dwa niezależne kroki (Inertia v2 współpracuje z React 19 bez problemu, to właśnie dlatego dzielimy to na dwa osobne commity).

## 8. Rzeczywisty przebieg wdrożenia (2026-10-06) — co plan przewidział, a co odkryto dodatkowo

**Zgodnie z planem:**
- Audyt z sekcji 3 potwierdzony tuż przed startem — bez zmian.
- Krok 1 (React 19) i Krok 2 (Inertia v3) wykonane jako dwa osobne commity, każdy z pełną weryfikacją (build, build:ssr, lint, vitest, manualna weryfikacja w przeglądarce).
- Jedyna zmiana kodu przewidziana planem (`<title inertia>` → `<title data-inertia>`) okazała się niewystarczająca — patrz niżej.

**Odkryte dodatkowo (nieprzewidziane w pierwotnym planie):**
1. **`pages.paths` w nowym `config/inertia.php`** domyślnie wskazuje na `resources/js/pages` (małe „p”), a projekt ma `resources/js/Pages`. W v3 `testing.ensure_pages_exist` domyślnie `true` — bez poprawki złamałoby to **całą** assercję `assertInertia` w PHPUnit. Poprawiono od razu po publikacji configu.
2. **Atrybut `inertia=` występował w DWÓCH miejscach**, nie jednym — oprócz `<title>` także ręcznie wpisany `<meta inertia="description" ...>` dla strony głównej w `app.blade.php` (dodany podczas prac SEO 2026-09-17). Oba przepisane na `data-inertia`.
3. **Fikstura testowa `homepageSeo.test.js`** ręcznie symulowała SSR-owy HTML z atrybutem `inertia="description"` — to też wymagało aktualizacji, mimo że to nie jest kod aplikacji. Test faktycznie wykrył regresję (2 tagi zamiast 1) zanim trafiła na produkcję.
4. **Nowo odkryty, PRE-ISTNIEJĄCY (niezwiązany z migracją) bug SEO**: gdy lokalnie uruchomiono prawdziwy serwer SSR (`php artisan inertia:start-ssr`) i zweryfikowano surowy `curl` strony głównej, znaleziono **2 tagi `<meta name="description">`** zamiast jednego — jeden statyczny z `app.blade.php`, drugi wyrenderowany przez `Welcome.jsx`'s `<Head>` jako część prawdziwego SSR. Ten duplikat najpewniej istniał od włączenia SSR (2026-10-05), po prostu nigdy wcześniej nie zweryfikowano surowym curl liczby tagów description (tylko obecność `<h1>`). **Niezwiązane z wersją Inertii/React — nie naprawione w ramach tej migracji, zgłoszone użytkownikowi jako osobny follow-up.**

## 9. Kiedy NIE zaczynać tej migracji

- Bezpośrednio po innym dużym wdrożeniu/incydencie (zasada "jedna duża zmiana na raz").
- Bez co najmniej 2–3 godzin nieprzerwanego czasu na pełną weryfikację + ewentualny rollback.
- Bez świeżego backupu bazy/kodu produkcji (ta migracja nie dotyka bazy danych, ale zasada ogólna obowiązuje).
