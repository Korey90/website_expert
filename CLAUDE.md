# WebsiteExpert — Claude Code Instructions

## Projekt
B2B SaaS dla agencji webowych. Zarządzanie leadami, klientami, projektami, fakturami, domenami. Multi-tenancy przez `business_id`.

**Stack:** Laravel 13 (PHP 8.3) · Filament 5.4 · Inertia.js 2 · React 18 · TypeScript · Tailwind CSS 4 · SQLite/MySQL · Reverb · Stripe · Twilio · OpenAI

## Kluczowe zasady — nigdy nie łam

1. **Delta-first** — zanim cokolwiek stworzysz, znajdź istniejący analogiczny kod i zacznij od niego.
2. **Cienkie kontrolery** — logika biznesowa wyłącznie w `app/Actions/` lub `app/Services/`.
3. **TypeScript strict** — zero `any`. Wszystkie propsy, funkcje i zmienne muszą być typowane.
4. **Multi-tenancy** — każdy nowy model związany z biznesem musi mieć `business_id` i trait `BelongsToTenant`.
5. **Tłumaczenia** — każdy nowy tekst UI wymaga kluczy w `lang/pl/`, `lang/en/`, `lang/pt/`.
6. **Form Requests** — walidacja tylko przez Form Request classes.
7. **Reuse components** — sprawdź `resources/js/Components/` przed tworzeniem nowego.
8. **Testy** — każda nowa Action i każdy krytyczny flow musi mieć test PHPUnit.

## Języki
- **Kod** (zmienne, klasy, komentarze, pliki): angielski
- **UI dla użytkownika, dokumentacja, raporty**: polski (z tłumaczeniami EN + PT)
- **Komunikacja ze mną**: polski

## Architektura — wzorce

```
app/Actions/{Domain}/VerbNounAction.php   # logika biznesowa
app/Models/{ModelName}.php                # Eloquent, traity, relacje
app/Http/Controllers/                     # cienkie, tylko HTTP
app/Http/Requests/                        # walidacja
app/Policies/                            # autoryzacja
app/Events/ + app/Listeners/             # efekty uboczne
app/Jobs/                                # async
resources/js/Pages/{Domain}/             # Inertia pages
resources/js/Components/{Domain}/        # React components (TypeScript)
resources/js/Hooks/                      # custom React hooks
```

## Plik .github — struktura agentów

| Folder | Zawartość |
|--------|-----------|
| `.github/agents/` | Specjaliści: @WebsiteExpert (orkiestrator), @BackendEngineer, @FrontendEngineer, @DatabaseEngineer, @TestingEngineer, @SecurityEngineer, @AutomationEngineer, @DocumentationEngineer |
| `.github/skills/` | Szablony zadań: laravel-action, react-inertia-component, database-migration, test-generation, delta-analysis, multi-language-check, sprint-planning, code-review, new-module, quick-fix, debug-session, filament-resource, task-completion-report, lead-capture, real-time-reverb, stripe-integration, project-onboarding |
| `.github/hooks/` | Hooki workflow: pre-commit, post-generation, after-feature-completion, on-file-save, before-new-sprint, before-deploy |
| `.github/instructions/` | Zasady: project-rules.md, solo-workflow.md, naming-conventions.md |
| `.github/live-docs/` | Stan projektu: current-task.md, current-sprint.md, status-dashboard.md, project-analysis.md, architecture-plan.md |
| `.github/completed-tasks/` | Archiwum raportów ukończonych zadań |

## Workflow dla każdego zadania

```
1. Przeczytaj .github/live-docs/ (kontekst, sprint, bieżące zadanie)
2. Delta Analysis — znajdź istniejący kod (@BackendEngineer lub skill:delta-analysis)
3. Plan → zapisz w current-task.md → przedstaw mi po polsku → czekaj na OK
4. Implementacja przez specjalistów (@BackendEngineer, @FrontendEngineer, etc.)
5. Walidacja: php artisan pint && npm run lint && php artisan test
6. Sprawdź tłumaczenia (skill:multi-language-check)
7. Raport końcowy (skill:task-completion-report) → archiwum
```

## Najczęstsze komendy

```bash
php artisan serve                          # serwer lokalny
npm run dev                                # Vite dev server
php artisan pint                           # PHP formatter
npm run lint && npm run format             # JS/TS linting
php artisan test                           # wszystkie testy
php artisan test --filter=FeatureName     # konkretny test
php artisan migrate                        # migracje
php artisan migrate:fresh --seed          # reset DB + seed
php artisan tinker                         # REPL
./.github/scripts/run-full-validation.sh  # pełna walidacja
./.github/scripts/check-translations.sh  # sprawdź tłumaczenia
```

## Model domeny — kluczowe tabele

| Domena | Modele |
|--------|--------|
| CRM | Lead, LeadActivity, LeadNote, LeadSource |
| Klienci | Client, ClientActivity, Contact, ClientPortalAccess |
| Projekty | Project, ProjectPhase, ProjectTask, ProjectFile |
| Faktury | Quote, Invoice, Payment |
| Domeny | Domain, DomainOrder, DomainRenewal |
| Kalendarz | CalendarEvent, GoogleCalendarToken |
| Automatyzacja | AutomationRule, AutomationTrigger |
| Treść | LandingPage, Page, SiteSection |

## Bezpieczeństwo
- Auth: Laravel Sanctum + Spatie Permission (role: manager, admin, agent)
- Webhooks Stripe: zawsze weryfikuj podpis
- Publiczne API: reCAPTCHA v3 + rate limiting
- Dane wrażliwe: tylko przez `.env`, nigdy hardkodowane

## Ogłoszenia — aktywny agent i skill

**Zawsze** informuj użytkownika który agent lub skill jest aktualnie w użyciu.
Pisz na początku każdego etapu pracy w formacie:

```
> [@NazwaAgenta] krótki opis co robię
> [skill: nazwa-skilla] krótki opis
> [hook: nazwa-hooka] krótki opis
```

Przykłady:
- `> [@BackendEngineer] Tworzę CreateLeadAction z DTO...`
- `> [skill: delta-analysis] Szukam anchor files w app/Actions/...`
- `> [@DatabaseEngineer] Piszę migrację dla tabeli proposals...`
- `> [hook: post-generation] Uruchamiam pint + testy...`
- `> [@TestingEngineer] Generuję PHPUnit feature test...`
- `> [skill: task-completion-report] Archiwizuję raport zadania...`

Jeśli w jednej odpowiedzi używasz kilku agentów/skilli — ogłaszaj każde przejście osobno.
