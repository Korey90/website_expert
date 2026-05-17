# 📋 Raport Onboarding & Deep Analysis — Digital Growth OS

**Data:** 17 maja 2026 | **Wykonano:** WebsiteExpert (project-onboarding skill)

---

## 1. Zrozumienie projektu

**Digital Growth OS** to platforma B2B SaaS przeznaczona dla agencji webowych i freelancerów. Produkt łączy trzy konteksty:

| Kontekst | Opis | Dostęp |
|---|---|---|
| **Public Site** | Strony publiczne, landing pages, formularz kontaktowy, kalkulator | Niezalogowani |
| **Client Portal** | Projekty, wyceny, faktury, umowy, komunikacja | Klient agencji |
| **Admin Panel (Filament)** | CRM, pipeline leadów, zarządzanie, billing | Użytkownicy agencji |

### Główne domeny biznesowe
- **Lead Management** — pipeline, source attribution, consent GDPR, automatyzacje
- **Projects & Delivery** — projekt, fazy, zadania, pliki, wiadomości
- **Billing** — faktury, wyceny, płatności (Stripe + PayU)
- **Landing Pages** — builder + AI generator (OpenAI gpt-4o-mini) + publiczne LP + lead capture
- **Client Portal** — dostęp klienta do własnych danych delivery
- **Automation** — trigger/rule/action engine (eventy)
- **Business (Tenant)** — profil, plan SaaS, billing, team members

---

## 2. Aktualny stan techniczny

### ✅ Stack — zweryfikowany

| Warstwa | Technologia | Wersja |
|---|---|---|
| Backend | Laravel | `^13.0` |
| PHP | PHP | `^8.3` |
| Admin | Filament | `^5.4` |
| Frontend | React + Inertia.js | `^18.2` + `^2.0` |
| Typowanie | TypeScript | via Vite + tsconfig |
| CSS | Tailwind CSS | `^4.2.2` |
| Realtime | Laravel Reverb | `^1.10` |
| Płatności | Stripe + PayU | `^19.4` SDK |
| SMS | Twilio | `^8.11` |
| Auth | Sanctum + Socialite (Google) | `^4.0` / `*` |
| Uprawnienia | Spatie Permission | `^7.2` |
| Tłumaczenia | Spatie Translatable | `^6.13` |
| PDF | DomPDF | `^3.1` |
| Testy FE | Vitest + Testing Library | `^4.1.2` |

### ✅ Co działa dobrze

- **54 modele** — bogaty model domenowy, SoftDeletes tam gdzie potrzebne, właściwe casts
- **37 Filament Resources** — pełne pokrycie panelu admin (Leads, Projects, Invoices, LPs, Automation, Roles, etc.)
- **Action pattern** — `CreateLeadAction` jest dobrze napisany: DTL-typed phpDoc, multi-source, auto-stage fallback
- **Lead pipeline** — pełna ścieżka: LP form → `PublicLeadCaptureService` → `LeadService` → `CreateLeadAction` → Events → Automations
- **Event-driven** — 5 zdefiniowanych eventów (`LeadCaptured`, `LeadAssigned`, `BusinessCreated`, `BusinessProfileUpdated`, `LandingPagePublished`)
- **Automation engine** — `ProcessAutomationJob`, `ConditionEvaluator`, 9 akcji (`SendEmailAction`, `SendSmsAction`, `AssignTaskAction`, etc.)
- **Multi-language** — pl/en/pt na wszystkich tabelach lang/
- **Testy** — 87% coverage (critical paths), Feature + Unit, w tym `FullLeadWorkflowTest`
- **Security** — recaptcha v3, GDPR consent flow, PII cleanup job (`CleanLeadSourcePiiJob`), open-redirect guard w routes
- **BelongsToTenant trait + BusinessScope** — zaimplementowany GlobalScope z auto-fill i query isolation

---

## 3. Kluczowe ryzyka i dług techniczny

### 🔴 KRYTYCZNE

**1. Niespójność multi-tenancy (`business_id`)**
Trait `BelongsToTenant` (aktywujący `BusinessScope` GlobalScope) jest użyty tylko w **3 modelach**:
- `LandingPage`
- `LandingPageAiGeneration`
- `LandingPageGenerationVariant`

Modele `Lead`, `Client`, `Project`, `Invoice`, `Quote`, `Briefing`, `SalesOffer` i inne mają kolumnę `business_id` w `$fillable`, ale **nie używają traitu** — brak automatycznej izolacji tenanta.

**2. Stara `LeadCaptureService` (app/Services/LandingPage)**
Istnieje legacy klasa `App\Services\LandingPage\LeadCaptureService` — uproszczona, bez deduplicacji, bez consent, bez source attribution. Nowa ścieżka (`PublicLeadCaptureService`) działa poprawnie, ale stary plik pozostaje w codebase i może być przypadkowo wstrzykiwany.

**3. Dualny model `Client`**
Z refactor-plan.md: model `Client` pełni jednocześnie rolę CRM record i konta portalowego. W `CreateLeadAction` `Client` jest tworzony automatycznie na podstawie emaila — co zaciera granicę między prospektem a klientem portalu.

### 🟡 POWAŻNE

**4. Brak `BelongsToTenant` na większości modeli**
Konsekwencja punktu 1 — `Pipeline Stages` nie są scopowane do tenanta (co status-dashboard potwierdza).

**5. 12 brakujących kluczy tłumaczeń**
Status-dashboard: `⚠️ 12 missing keys` — nie wiadomo których (pt najprawdopodobniej).

**6. `BusinessUser` membership model**
Klient agencyjny (portal-only) vs member workspace'u — brak jasnej granicy. `CreatePortalAccessAction` używa nieprawidłowego URL do invite.

**7. Tylko 1 Action klasa**
Konwencja zakłada `app/Actions/{Domain}/` — istnieje tylko `CreateLeadAction`. Reszta logiki (Billing, Portal, LandingPage) siedzi w Services. Niespójność ze zdefiniowaną architekturą.

### 🟢 NISKI PRIORYTET

**8. Sprint i Current Task nieuzupełnione**
current-sprint.md i current-task.md mają puste szablony — brak aktywnie trackowanego zadania.

**9. ESLint skonfigurowany tylko dla `.js/.jsx`**
W package.json lint dotyczy `.js,.jsx` — brak `--ext .ts,.tsx`. TypeScript pliki mogą omijać lint.

---

## 4. Najważniejsze konwencje i twarde reguły

| Reguła | Szczegół |
|---|---|
| **Thin controllers** | Logika biznesowa → Actions lub Services, nigdy w kontrolerze |
| **Delta-first** | Zawsze sprawdź istniejący kod przed każdą zmianą |
| **Tłumaczenia obowiązkowe** | Po każdej zmianie UI: pl + en + pt (`multi-language-check` skill) |
| **TypeScript strict** | Brak `any` — bez wyjątków |
| **Multi-tenancy** | `business_id` wszędzie + `BelongsToTenant` trait na nowych modelach |
| **Inertia useForm** | Wszystkie mutacje przez `useForm`, nie przez `axios` bezpośrednio |
| **Form Requests** | Złożona walidacja → dedykowany FormRequest, nie inline `validate()` |
| **Policies/Gates** | Autoryzacja → Policy lub Gate, nie w kontrolerze |
| **Reuse komponentów** | Nie tworzyć nowych UI primitives jeśli istnieje odpowiednik |

**Języki:**
- Kod + nazwy techniczne → **angielski**
- Teksty UI + dokumentacja → **polski** (z tłumaczeniami en/pt)

---

## 5. Mapa modułów — status szczegółowy

| Moduł | Stan | Uwagi |
|---|---|---|
| Lead Capture (LP → CRM) | ⚠️ Krytyczny | Nowa ścieżka działa, stara legacy klasa wciąż w repo |
| Pipeline CRM | ✅ Stabilny | Brak BelongsToTenant na PipelineStage |
| Landing Pages + AI | ✅ Dobry | BelongsToTenant aktywny |
| Client Portal | 🔄 W toku | Dualny model Client/User do rozwiązania |
| Billing (Stripe) | ✅ Dobry | Plany: free/starter/pro/agency, limity w config |
| Automatyzacje | ✅ Dobry | 9 akcji, trigger/rule/condition engine |
| Umowy | ✅ Stabilny | |
| Briefings | ✅ Stabilny | |
| Sales Offers | ✅ Stabilny | |
| Portfolio | ✅ Stabilny | |
| Kalkulator | ✅ Stabilny | |
| Tłumaczenia (pl/en/pt) | ⚠️ Uwaga | 12 brakujących kluczy |
| Testy | ✅ 87% | Feature + Unit, FullLeadWorkflowTest |
| Multi-tenancy | ⚠️ Częściowy | Tylko 3 modele mają GlobalScope |

---

## 6. Gotowość do pracy

**Ocena:** ✅ Gotowy do realizacji nowych zadań.

Projekt jest dobrze ustrukturyzowany, dokumentacja żywa (live-docs), stack nowoczesny i spójny. Trzy obszary wymagają uwagi **przed** dodaniem nowych funkcji wielodostępnych:

1. Rozszerzenie `BelongsToTenant` na modele z `business_id` (Lead, Client, Project, Invoice)
2. Usunięcie/deprecacja `LeadCaptureService` z LandingPage
3. Uzupełnienie 12 brakujących kluczy tłumaczeń

Wszystkie plany techniczne istnieją w plans — w tym refactor-plan.md, mvp-plan.md i `automation-event-system-plan.md`.

---

Czy chcesz, abym zaczął od któregoś z ww. obszarów długu technicznego, czy masz nowe zadanie do realizacji?

Completed: *Perform deep analysis of the project* (2/3)