# 2026-06-29 - Korekta zakresu ESLint

**Status:** Zakończone sukcesem
**Data realizacji:** 2026-06-29
**Czas trwania:** poniżej 1 godziny po analizie baseline

## Cel zadania

Usunąć 11 809 fałszywych błędów z `npx eslint .`, zachowując rygor lintingu kodu aplikacji i nie modyfikując zewnętrznych ani generowanych plików.

## Zakres wykonanych prac

- Zebrano baseline ESLint według katalogów, rozszerzeń, reguł i plików.
- Potwierdzono, że 8 653 błędy pochodziły z `vendor/**`, a 3 156 z `public/js/filament/**`.
- Potwierdzono 0 błędów w 152 plikach JS/JSX pod `resources/js`.
- Dodano dwa precyzyjne globalne ignore w flat config ESLint.
- Ograniczono konfiguracje React do `resources/js/**/*.{js,jsx}`, dzięki czemu pliki Node nie dziedziczą reguł React.
- Ujednolicono `lint` i `lint:fix` w `package.json` z zakresem repozytoryjnego flat config.
- Nie zmieniono kodu aplikacji.
- Nie uruchamiano autofixu.

## Użyte agenty i skille

- @WebsiteExpert
- @FrontendEngineer
- Skill: delta-analysis
- Skill: sprint-planning (analiza wykazała, że pierwotny duży zakres można bezpiecznie zredukować do małej korekty konfiguracji)
- Skill: task-completion-report

## Zmodyfikowane / utworzone pliki

- `eslint.config.js`
- `package.json`
- `.github/live-docs/current-task.md`
- `.github/live-docs/status-dashboard.md`
- `.github/completed-tasks/2026-06-29 - Korekta zakresu ESLint.md`

## Walidacja końcowa

- ✅ `npx eslint .` — exit 0, spadek z 11 809 do 0 błędów
- ✅ `npm run lint` — exit 0
- ✅ `npm run build` — exit 0
- ✅ `git diff --check`
- ✅ Brak zmian w `vendor/**` i `public/js/filament/**`
- ✅ Tłumaczenia i multi-tenancy: brak wpływu
- ⚠️ `npm test`: 12 testów przechodzi, 4 istniejące testy `CostCalculatorV2` zawodzą z `usePage must be used within the Inertia component`; problem był obecny przed tą zmianą i nie dotyczy ESLint.

## Uwagi i rekomendacje

- `.eslintrc.json` jest konfiguracją legacy i nie steruje ESLint 9, gdy istnieje `eslint.config.js`; można ją usunąć w osobnym zadaniu porządkowym.
- Jedyny plik TSX, `resources/js/Pages/Services/ServicePage.tsx`, nie jest obecnie lintowany. Dodanie parsera TypeScript powinno być osobnym, świadomym rozszerzeniem zakresu.
- Reguły React są ograniczone do frontendu; wcześniejsze ostrzeżenie o niewykrytej wersji React poza tym zakresem zostało usunięte.
- Build przechodzi z istniejącymi ostrzeżeniami deprecacyjnymi Vite.

## Next steps (opcjonalnie)

- Dodać obsługę TypeScript/TSX do flat config i skryptu lint.
- Naprawić provider/mocking Inertia w testach `CostCalculatorV2`.
- Uporządkować legacy `.eslintrc.json`.

**Raport wygenerowany automatycznie przez WebsiteExpert**
