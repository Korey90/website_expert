# 2026-06-29 - Przywrócenie meta description strony głównej

**Status:** Zakończone sukcesem
**Data realizacji:** 2026-06-29
**Czas trwania:** poniżej 2 godzin

## Cel zadania

Przywrócić wielojęzyczny `meta description` strony głównej w surowym HTML zwracanym przez Laravel, bez duplikowania tagu po uruchomieniu Inertia.

## Zakres wykonanych prac

- Dodano fallback `meta description` wyłącznie dla nazwanej trasy `home`.
- Oznaczono fallback kluczem `inertia="description"`.
- Dodano zgodny `head-key="description"` do istniejącego tagu w komponencie strony głównej.
- Dodano tłumaczenia `pl`, `en` i `pt` w grupie `seo.php`; angielska treść została zachowana.
- `WelcomeController` rozwiązuje właściwe tłumaczenie po ustawieniu locale i przekazuje jeden prop `seo.description` do Blade oraz Reacta.
- Usunięto angielski hardcode z Blade i JSX.
- Dodano parametryzowany test PHP sprawdzający `pl/en/pt`, surowy HTML oraz zgodność propa Inertia.
- Dodano parametryzowany test Vitest/JSDOM sprawdzający deduplikację po aktualizacji Inertia Head dla wszystkich locale.
- Potwierdzono brak fallbacku strony głównej na `/services`.

## Użyte agenty i skille

- @WebsiteExpert
- @FrontendEngineer
- @TestingEngineer
- Skill: delta-analysis
- Skill: task-completion-report

## Zmodyfikowane / utworzone pliki

- `app/Http/Controllers/WelcomeController.php`
- `lang/en/seo.php`
- `lang/pl/seo.php`
- `lang/pt/seo.php`
- `resources/views/app.blade.php`
- `resources/js/Pages/Welcome.jsx`
- `resources/js/tests/homepageSeo.test.js`
- `tests/Feature/ExampleTest.php`
- `.github/live-docs/current-task.md`
- `.github/live-docs/status-dashboard.md`
- `.github/completed-tasks/2026-06-29 - Przywrócenie meta description strony głównej.md`

## Walidacja końcowa

- ✅ PHP Coding Standard: Pint dla kontrolera, tłumaczeń i testu
- ✅ JavaScript lint: `npm run lint`
- ✅ Build: `npm run build`
- ✅ PHPUnit SEO: 3 zestawy locale, 51 asercji
- ✅ Vitest SEO: 3/3 testy deduplikacji
- ✅ Surowy HTML: `pl/en/pt` zwracają po 1 właściwym opisie; `/services` nie dziedziczy fallbacku
- ✅ Inertia Head: JSDOM pozostawia dokładnie 1 właściwy opis dla każdego locale
- ✅ Multi-language check: komplet `pl/en/pt`, jedno źródło w `lang/{locale}/seo.php`
- ✅ Multi-tenancy compliance: zmiana nie dotyka danych ani zapytań tenantowych
- ✅ `git diff --check`
- ⚠️ Pełny Vitest: 12 testów przechodzi, 4 istniejące testy `CostCalculatorV2` zawodzą przez brak kontekstu Inertia wymaganego przez `useCurrency`; nowy test SEO przechodzi i nie jest przyczyną tych awarii.
- ℹ️ Pełny zestaw PHPUnit nie był uruchamiany; zastosowano proporcjonalną walidację backendu.

## Uwagi i rekomendacje

Naprawa obejmuje `meta name="description"` strony głównej `/`. Title, Open Graph i Twitter metadata nadal mają angielskie treści i wymagają osobnego rozszerzenia, jeżeli także mają być wielojęzyczne. Inne publiczne podstrony nadal opierają metadane Inertia na JavaScript. Build przechodzi, ale Vite zgłasza istniejące ostrzeżenia deprecacyjne dotyczące opcji pluginów.

## Next steps (opcjonalnie)

- Osobno przeanalizować server-side metadane pozostałych publicznych tras, jeśli są sprawdzane przez crawlery bez JavaScriptu.
- Naprawić harness testów `CostCalculatorV2`, zapewniając mock lub provider Inertia dla `useCurrency`.

**Raport wygenerowany automatycznie przez WebsiteExpert**
