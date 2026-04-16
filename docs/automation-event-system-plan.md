# Plan: Visual Automation Event System

**Data:** 2026-04-16  
**Status:** DRAFT — czeka na akceptację przed implementacją

---

## 1. Stan obecny — co już istnieje

### Warstwa backendowa (w pełni działająca)

| Komponent | Plik | Opis |
|---|---|---|
| `AutomationRule` model | `app/Models/AutomationRule.php` | JSON: `conditions[]`, `actions[]`, `trigger_event`, `delay_minutes` |
| `ProcessAutomationJob` | `app/Jobs/ProcessAutomationJob.php` | Pobiera reguły z DB, ewaluuje warunki, wykonuje akcje przez ACTION_MAP |
| `AutomationEventListener` | `app/Listeners/AutomationEventListener.php` | Subskrybuje eventy Eloquent i app-events, dispatch-uje Job |
| `ConditionEvaluator` | `app/Automation/ConditionEvaluator.php` | Operatory: `=`, `!=`, `>`, `<`, `contains` |
| **Akcje** | `app/Automation/Actions/` | `send_email`, `send_sms`, `notify_admin`, `add_tag`, `change_status`, `create_portal_access`, `send_internal_email` |
| `SmsService` | `app/Services/SmsService.php` | Twilio — wysyłka SMS |
| `EmailTemplate` | wielojęzyczny JSON (subject/body_html/body_text per locale) |
| `SmsTemplate` | treść z placeholderami `{{variable}}` |

### Warstwa UI (istniejące Filament Resources)

| Resource | Lokalizacja | Stan |
|---|---|---|
| `AutomationRuleResource` | `app/Filament/Resources/` | ✅ Działa — formularz z Repeater dla akcji |
| `EmailTemplateResource` | `app/Filament/Resources/` | ✅ Działa — zakładki EN/PL/PT, TinyEditor, preview |
| `SmsTemplateResource` | `app/Filament/Resources/` | ✅ Działa — textarea z podglądem zmiennych |

### Zarejestrowane trigger events

```
lead.created            lead.stage_changed      lead.assigned
project.created         project.status_changed  project.completed
invoice.sent            invoice.overdue         invoice.paid
quote.sent              quote.accepted
contract.created        contract.sent           contract.signed  contract.expired
```

### Problemów obecnego UI

1. **Brak listy dostępnych zmiennych per trigger** — użytkownik nie wie co może wstawić do szablonu
2. **Email Template linkowany przez `slug` (string)** — zamiast ID z dropdownem; podatne na literówki
3. **Brak historii wykonań** — nie widać czy automation się odpaliła i z jakim wynikiem
4. **Brak podglądu reguły** — po zapisaniu nie widać co reguła zrobi
5. **Brak `service_cta` w liście triggerów** — nowe źródło leadów nie jest obsługiwane przez reguły
6. **Warunki (Conditions) brak UI** — sekcja `conditions` nie ma formularza w `AutomationRuleResource`
7. **Brak testowania reguły** — nie można wyzwolić reguły testowo z UI bez tworzenia prawdziwego leada

---

## 2. Cel

Uzupełnić istniejący system o:
1. **Wygodny UI** dla tworzenia reguł — z dropdownami zamiast string-ów, pomocnikami zmiennych, podglądem
2. **Sekcję Conditions** w formularzu reguły
3. **Log wykonań** — tabela `automation_logs` + widok w Filament
4. **Test trigger** — akcja "Run Now" z próbnym kontekstem
5. **Nowe trigger events** — `lead.service_cta`, `lead.contact_form` rozróżnione osobno

---

## 3. Architektura zmian

### 3.1 Nowa tabela: `automation_logs`

```sql
id               bigint PK
automation_rule_id  bigint FK → automation_rules.id  (nullable — reguła mogła być usunięta)
trigger_event    varchar(100)
context          json          -- snapshot kontekstu w momencie wywołania
actions_executed json          -- [{type, status: ok|error, message?, duration_ms}]
lead_id          bigint nullable FK
client_id        bigint nullable FK
status           enum('success','partial','failed')
executed_at      timestamp
```

Model: `AutomationLog`, relacja `automationRule()` belongsTo.

### 3.2 Rozszerzenie `AutomationRule`

Dodać kolumnę:
```sql
business_id  bigint nullable FK  -- multi-tenancy: null = global
```

### 3.3 Rejestr zmiennych per trigger (PHP Registry)

Nowa klasa `app/Automation/TriggerRegistry.php`:

```php
class TriggerRegistry
{
    /** Zwraca [event_key => [label, variables[]]] */
    public static function all(): array { ... }

    /** Zwraca zmienne dostępne dla danego triggera */
    public static function variablesFor(string $event): array { ... }

    /** Zwraca human-readable label triggera */
    public static function label(string $event): string { ... }
}
```

Przykład danych:

```php
'lead.created' => [
    'label'     => 'Lead Created',
    'group'     => 'Leads',
    'variables' => [
        'lead_title'    => 'Lead title',
        'client_name'   => 'Contact name',
        'company_name'  => 'Company',
        'stage_name'    => 'Pipeline stage',
        'assigned_name' => 'Assigned to',
        'lead_source'   => 'Lead source (contact_form, service_cta, …)',
    ],
],
'lead.service_cta' => [ ... ],
```

---

## 4. Plan implementacji — priorytety

### FAZA 1 — Quick wins (nie wymagają migracji) ★★★ HIGH

#### 1A. Dodać warunki (Conditions) do formularza AutomationRuleResource

Sekcja `Conditions` z Repeater — pola:
- `field` → Select z listą pól dostępnych dla wybranego triggera (reaktywne)
- `operator` → Select: `=`, `!=`, `>`, `<`, `>=`, `<=`, `contains`
- `value` → TextInput

Teraz sekcja Conditions w Filament istnieje w modelu ale **nie ma formularza UI** — użytkownik musi wpisywać raw JSON.

#### 1B. Poprawić linkowanie EmailTemplate w akcji

Zamiast `template_slug` (string podatny na literówki):
```php
Forms\Components\Select::make('template_id')
    ->label('Email Template')
    ->options(fn () => EmailTemplate::where('is_active', true)->pluck('name', 'id'))
    ->searchable()
    ->visible(fn (Get $get) => $get('type') === 'send_email')
```

I zaktualizować `SendEmailAction` żeby używał `id` zamiast `slug`.

#### 1C. Dodać `service_cta` do listy triggerów

W `AutomationRuleResource::TRIGGERS` i `AutomationEventListener` dodać:
```php
'lead.service_cta' => 'Lead: Service CTA Form',
'lead.contact_form' => 'Lead: Contact Form',
```

I w `ContactController` emitować właściwy event po stworzeniu leada zamiast polegać na Eloquent `created`.

---

### FAZA 2 — Automation Logs ★★ MEDIUM

#### 2A. Migracja `create_automation_logs_table`

#### 2B. Zapis wyniku w `ProcessAutomationJob`

Przed wykonaniem akcji: otwórz log entry. Po każdej akcji: zapisz status. Po wszystkich: zamknij z `status`.

#### 2C. `AutomationLogResource` w Filament

Tabela tylko do odczytu:
- Kolumny: trigger event, rule name, status (badge), executed_at, lead (link), actions count
- Filter: status, trigger_event, date range
- Row action: "View details" → panel z JSON kontekstu + wynikami akcji

---

### FAZA 3 — Test Trigger (Run Now) ★ LOW

#### 3A. Custom Filament Action na stronie widoku reguły

Przycisk "Test Rule" → modal z polem `lead_id` lub `context JSON` → dispatch `ProcessAutomationJob` z `singleRuleId`.

---

### FAZA 4 — TriggerRegistry + dynamiczne zmienne ★ LOW

#### 4A. Klasa `TriggerRegistry`

#### 4B. W formularzu SMS/Email Template — lista zmiennych ładowana per trigger

Gdy użytkownik buduje regułę i wybiera akcję `send_sms`, obok textarea z treścią pojawia się lista `Available variables for this trigger: {{lead_title}}, {{client_name}}, ...`.

---

## 5. Pliki do stworzenia/zmodyfikowania

| Plik | Operacja | Faza |
|---|---|---|
| `app/Automation/TriggerRegistry.php` | CREATE | 4 |
| `database/migrations/..._create_automation_logs_table.php` | CREATE | 2 |
| `app/Models/AutomationLog.php` | CREATE | 2 |
| `app/Filament/Resources/AutomationLogResource.php` | CREATE | 2 |
| `app/Jobs/ProcessAutomationJob.php` | MODIFY — dodać zapis logu | 2 |
| `app/Filament/Resources/AutomationRuleResource.php` | MODIFY — Conditions UI + email template_id + nowe triggery | 1 |
| `app/Automation/Actions/SendEmailAction.php` | MODIFY — obsługa `template_id` | 1B |
| `app/Listeners/AutomationEventListener.php` | MODIFY — nowe eventy service_cta/contact_form | 1C |
| `app/Http/Controllers/ContactController.php` | MODIFY — emit LeadCapturedFromCta event | 1C |

---

## 6. Nowe eventy aplikacji (opcjonalnie do Fazy 1C)

Można rozszerzyć `AutomationEventListener` o nasłuch na:
- `lead.service_cta` — emitowany przez `ContactController::quickStore` po stworzeniu leada
- `lead.contact_form` — emitowany przez `ContactController::store`

Albo (prostsze) — warunek w istniejącym `lead.created` trigger:
```json
conditions: [{"field": "source", "operator": "=", "value": "service_cta"}]
```
To rozwiązanie nie wymaga nowych eventów — **preferowane w Fazie 1**.

---

## 7. Co NIE wymaga zmian

- `ProcessAutomationJob::ACTION_MAP` — w pełni extensible, dodanie nowej akcji = nowa klasa
- `SmsService` — działa, ma normalizePhone, obsługę błędów
- `ConditionEvaluator` — działa; można rozszerzyć o `>=`, `<=` (minor fix)
- `SmsTemplate` + `EmailTemplate` modele — wystarczają; nie trzeba migracji

---

## 8. Kolejność implementacji (zalecana)

```
1. FAZA 1A — Conditions UI w AutomationRuleResource        ~2h
2. FAZA 1B — Email Template Select zamiast slug            ~1h
3. FAZA 1C — Dodać source conditions dla service_cta       ~30min
4. FAZA 2A+2B — Automation Logs migracja + Job             ~3h
5. FAZA 2C — AutomationLogResource Filament                ~2h
6. FAZA 3  — Test Trigger modal                            ~2h
7. FAZA 4  — TriggerRegistry + dynamiczne zmienne          ~3h
```

---

## 9. Pytania otwarte przed implementacją

1. Czy logi mają być trzymane w nieskończoność czy z automatycznym czyszczeniem (np. 90 dni)?
2. Czy `business_id` na `automation_rules` jest potrzebny teraz (multi-tenancy), czy odkładamy?
3. Czy Faza 3 (Test Trigger) ma działać na prawdziwym leadzie z bazy, czy na mock-danych?
