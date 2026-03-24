# Plan poprawek systemu — Analiza spójności lejka sprzedażowego z treścią kontraktów

**Data analizy:** 2026-03-24  
**Zakres:** Moduł Leads → Client → Project → Contract + ContractTemplates

---

## Podsumowanie problemów

| # | Obszar | Priorytet | Status |
|---|--------|-----------|--------|
| 1 | Placeholdery `{{legal.*}}` nigdy nie są zastępowane | 🔴 Krytyczny | Do zrobienia |
| 2 | Brak placeholderów `{{client.*}}` i `{{project.*}}` w szablonach | 🔴 Krytyczny | Do zrobienia |
| 3 | Brak interpolacji przy tworzeniu kontraktu z szablonu | 🔴 Krytyczny | Do zrobienia |
| 4 | Lead "Won" nie tworzy automatycznie kontraktu | 🟠 Wysoki | Do zrobienia |
| 5 | Brak triggerów automatyzacji dla zdarzeń kontraktu | 🟠 Wysoki | Do zrobienia |
| 6 | AutomationEventListener monitoruje `status` na Lead (pole nie istnieje) | 🟠 Wysoki | Bug |
| 7 | Brak `contract_template_id` na modelu Contract | 🟡 Średni | Do zrobienia |
| 8 | Quote → Contract: brak automatycznego przepływu po akceptacji | 🟡 Średni | Do zrobienia |
| 9 | Wartość kontraktu nie jest prefillowana z Lead/Project | 🟡 Średni | Do zrobienia |
| 10 | Brak sekcji podpisów cyfrowych / potwierdzenia przez klienta | 🟡 Średni | Do zrobienia |

---

## 1. Placeholdery `{{legal.*}}` nigdy nie są zastępowane

### Problem
Szablony kontraktów zawierają tagi np. `{{legal.company_name}}`, `{{legal.deposit_percent}}` itd. Model `Setting` przechowuje te wartości poprawnie. Jednak w `ContractResource` przy wyborze szablonu — treść jest kopiowana do pola `terms` bezpośrednio jako surowy HTML, bez żadnej interpolacji.

Efekt: klient dostaje umowę z dosłownymi `{{legal.company_name}}` w treści.

### Placeholdery w użyciu (z seedera)
```
{{legal.company_name}}
{{legal.company_number}}
{{legal.vat_number}}
{{legal.company_address}}
{{legal.company_email}}
{{legal.company_phone}}
{{legal.deposit_percent}}
{{legal.payment_terms_days}}
```

### Rozwiązanie
Dodać metodę pomocniczą w `ContractResource` (lub serwisie) która przed wklejeniem treści do pola `terms` wykonuje `str_replace` wszystkich `{{legal.*}}` wartościami z `Setting::get(...)`.

```php
// app/Services/ContractInterpolationService.php
public function interpolate(string $content, ?Client $client = null, ?Project $project = null): string
{
    $legalKeys = ['company_name','company_number','vat_number','company_address',
                  'company_email','company_phone','deposit_percent','payment_terms_days'];
    foreach ($legalKeys as $key) {
        $content = str_replace("{{legal.$key}}", Setting::get("legal.$key", ''), $content);
    }
    if ($client) {
        $content = str_replace('{{client.company_name}}', $client->company_name ?? '', $content);
        $content = str_replace('{{client.vat_number}}', $client->vat_number ?? '', $content);
        $content = str_replace('{{client.address}}', $client->full_address ?? '', $content);
        $content = str_replace('{{client.primary_contact_name}}', $client->primary_contact_name ?? '', $content);
        $content = str_replace('{{client.primary_contact_email}}', $client->primary_contact_email ?? '', $content);
    }
    if ($project) {
        $content = str_replace('{{project.title}}', $project->title ?? '', $content);
        $content = str_replace('{{project.budget}}', $project->budget ?? '', $content);
        $content = str_replace('{{project.deadline}}', $project->deadline?->format('Y-m-d') ?? '', $content);
    }
    return $content;
}
```

---

## 2. Brak placeholderów `{{client.*}}` i `{{project.*}}` w szablonach

### Problem
Dane klienta w szablonach są wpisane jako statyczne bloki do ręcznego uzupełnienia:
```
[NAZWA KLIENTA / FIRMY KLIENTA]
[ADRES REJESTROWY KLIENTA]
[NUMER REJESTRACYJNY FIRMY, JEŚLI DOTYCZY]
```

Powoduje to, że każda umowa wymaga ręcznej edycji po wygenerowaniu — zamiast być gotową do wysłania.

### Rozwiązanie
Zaktualizować seeder `ContractTemplateSeeder` — zamienić statyczne bloki na dynamiczne placeholdery:

| Przed | Po |
|-------|----|
| `[NAZWA KLIENTA / FIRMY KLIENTA]` | `{{client.company_name}}` |
| `[ADRES REJESTROWY KLIENTA]` | `{{client.address}}` |
| `[NUMER REJESTRACYJNY FIRMY, JEŚLI DOTYCZY]` | `{{client.companies_house_number}}` |
| `[OSOBA KONTAKTOWA KLIENTA]` | `{{client.primary_contact_name}}` |
| `[DATA ZAWARCIA]` | `{{contract.date}}` |
| `[WARTOŚĆ PROJEKTU]` | `{{project.budget}}` |
| `[TERMIN REALIZACJI]` | `{{project.deadline}}` |
| `[NUMER UMOWY]` | `{{contract.number}}` |

Wymagane nowe placeholdery do dodania do `ContractInterpolationService`:
```
{{client.company_name}}
{{client.companies_house_number}}
{{client.vat_number}}
{{client.address}}
{{client.primary_contact_name}}
{{client.primary_contact_email}}
{{project.title}}
{{project.budget}}
{{project.deadline}}
{{contract.number}}
{{contract.date}}
```

---

## 3. Brak interpolacji przy tworzeniu kontraktu z szablonu

### Problem
`ContractResource` prawdopodobnie zawiera pole `Select` do wyboru szablonu, ale po wyborze — treść jest kopiowana bez przetworzenia.

### Rozwiązanie
W `ContractResource::form()` dodać `afterStateUpdated` na polu wyboru szablonu:

```php
Forms\Components\Select::make('contract_template_id')
    ->label('Use Template')
    ->options(ContractTemplate::where('is_active', true)->pluck('name', 'id'))
    ->reactive()
    ->afterStateUpdated(function ($state, Set $set, Get $get) {
        if (!$state) return;
        $template = ContractTemplate::find($state);
        if (!$template) return;
        $client = Client::find($get('client_id'));
        $project = Project::find($get('project_id'));
        $content = app(ContractInterpolationService::class)
            ->interpolate($template->content, $client, $project);
        $set('terms', $content);
        if (!$get('title')) $set('title', $template->name);
    }),
```

---

## 4. Lead "Won" nie tworzy automatycznie kontraktu

### Problem
Gdy lead przechodzi do etapu `Won` (`pipeline_stage.is_won = true`) — system:
- Tworzy projekt (jeśli jest reguła automatyzacji) ✅  
- Nie tworzy kontraktu ❌
- Nie sugeruje wyboru szablonu ❌

Efekt: handlowiec musi ręcznie pamiętać o wystawieniu umowy.

### Rozwiązanie (opcje do wyboru)

**Opcja A — Automatyzacja (prostsza):**  
Dodać trigger `lead.stage_changed` + warunek `is_won = true` + akcja `create_contract`.  
Wymaga rozszerzenia `ProcessAutomationJob` o akcję `create_contract` z domyślnym szablonem.

**Opcja B — UI prompt (lepsza UX):**  
Na stronie `ViewLead` dodać baner/akcję "This lead is won — create contract" widoczny gdy `$lead->stage->is_won && !$lead->contracts()->exists()`.

**Opcja C — Obie połączone:**  
Automatycznie tworzyć draft kontraktu przy przejściu do Won, powiadamiać admina (`notify_admin`).

---

## 5. Brak triggerów automatyzacji dla zdarzeń kontraktu

### Problem
`AutomationEventListener` obsługuje: Lead, Project, Invoice, Quote — ale **brak Contract**.

Konsekwencja: nie można zautomatyzować:
- Powiadomienia gdy kontrakt zostaje wysłany
- Przypomnienia gdy kontrakt wygasa (`expires_at`)
- Akcji po podpisaniu kontraktu (np. tworzenie faktury za zaliczkę)

### Rozwiązanie
Rozszerzyć `AutomationEventListener` o:

```php
Contract::class => [
    'contract.created'  => ['status'],
    'contract.sent'     => ['sent_at'],
    'contract.signed'   => ['signed_at'],
    'contract.expired'  => ['status' => 'expired'],
],
```

Dodać triggery do `AutomationRuleResource::TRIGGERS`:
```php
'contract.sent'   => 'Contract Sent',
'contract.signed' => 'Contract Signed',
'contract.expired'=> 'Contract Expired',
```

---

## 6. Bug: AutomationEventListener monitoruje `status` na Lead (pole nie istnieje)

### Problem
W `AutomationEventListener` jest logika sprawdzająca `$model->status` na modelu `Lead`. Model `Lead` **nie ma pola `status`** — jego etap to `pipeline_stage_id`.

Efekt: trigger `lead.stage_changed` może nie odpowiadać poprawnie, warunki bazujące na `status` nigdy nie są spełnione.

### Rozwiązanie
W `AutomationEventListener`, przy obsłudze Lead, zamienić referencje `status` na `pipeline_stage_id` i pobierać nazwę etapu przez `$lead->stage->name` lub `$lead->stage->slug`.

---

## 7. Brak `contract_template_id` na modelu Contract

### Problem
Model `Contract` nie przechowuje informacji o tym, z jakiego szablonu powstał. Uniemożliwia to:
- Śledzenie popularności szablonów
- Regenerację kontraktu z nową treścią szablonu
- Filtrowanie kontraktów po typie umowy

### Rozwiązanie
Nowa migracja:
```php
$table->foreignId('contract_template_id')->nullable()->constrained()->nullOnDelete();
```

Dodać relację `belongsTo ContractTemplate` na modelu `Contract`.

---

## 8. Quote → Contract: brak przepływu po akceptacji

### Problem
Gdy oferta (`Quote`) zostaje zaakceptowana (`status = accepted`), trigger `quote.accepted` jest obsługiwany durch `AutomationEventListener`. Jednak brak akcji `create_contract` — handlowiec musi ręcznie tworzyć kontrakt.

### Powiązanie z kontraktem
Model `Contract` ma pole `quote_id` — relacja jest przewidziana ale nie ma automatycznego przepływu.

Contract powinien dziedziczyć z Quote:
- `client_id`
- `value` (z `Quote.total`)
- `currency`
- Wybór szablonu na bazie `Quote.service_type` (jeśli dostępne)

### Rozwiązanie
Dodać akcję `create_contract` do `ProcessAutomationJob` + przycisk "Create Contract from Quote" w `ViewQuote`.

---

## 9. Wartość kontraktu nie jest prefillowana z Lead/Project

### Problem
Gdy tworzony jest kontrakt powiązany z leadem (`lead.value`) lub projektem (`project.budget`) — pole `Contract.value` jest puste i wymaga ręcznego uzupełnienia.

### Rozwiązanie
W `ContractResource::form()` dodać `afterStateUpdated` na polach `client_id` / `project_id`:

```php
Forms\Components\Select::make('project_id')
    ->reactive()
    ->afterStateUpdated(function ($state, Set $set) {
        $project = Project::find($state);
        if ($project) {
            $set('value', $project->budget);
            $set('currency', $project->currency);
        }
    }),
```

---

## 10. Brak sekcji podpisów / potwierdzenia przez klienta

### Problem
Model `Contract` ma pola `signed_at` i `status = signed`, ale:
- Brak mechanizmu podpisu przez klienta (e-mail z linkiem, portal klienta)
- Brak miejsca na podpis w szablonach HTML
- `portal_token` na `Project` jest generowany, ale nie ma analogicznego tokenu na `Contract`

### Rozwiązanie (etapowe)

**Etap 1 — Minimalne MVP:**
- Dodać `client_token` (UUID) na `Contract`
- Publiczny endpoint `GET /contracts/{token}` wyświetla treść + przycisk "Potwierdzam i podpisuję"
- Po kliknięciu: `signed_at = now()`, `status = signed`, trigger `contract.signed`

**Etap 2 — Podpis w szablonie:**
Dodać na końcu każdego szablonu sekcję:
```html
<h2>PODPISY</h2>
<table>
  <tr>
    <td>Wykonawca: {{legal.company_name}}<br>Data: ___________<br>Podpis: ___________</td>
    <td>Zamawiający: {{client.company_name}}<br>Data: ___________<br>Podpis: ___________</td>
  </tr>
</table>
```

---

## Plan implementacji (kolejność)

```
Sprint 1 — Podstawowe sprzęgnięcie danych z kontraktem
  [1] ContractInterpolationService + interpolacja {{legal.*}}
  [2] Dodanie {{client.*}} i {{project.*}} do seedera szablonów
  [3] afterStateUpdated w ContractResource — wybór szablonu → interpolacja
  [4] Prefill wartości kontraktu z Project/Lead

Sprint 2 — Przepływ sprzedażowy
  [5] Przycisk "Utwórz kontrakt" na ViewLead gdy is_won=true
  [6] Przycisk "Utwórz kontrakt z oferty" na ViewQuote
  [7] contract_template_id na modelu Contract

Sprint 3 — Automatyzacje
  [8] Fix buga: AutomationEventListener Lead.status → pipeline_stage_id
  [9] Dodanie triggerów contract.sent / contract.signed / contract.expired
  [10] Akcja create_contract w ProcessAutomationJob

Sprint 4 — Podpisy
  [11] client_token na Contract + publiczny endpoint podpisu
  [12] Sekcja podpisów w szablonach HTML
```

---

## Techniczne zależności

```
ContractInterpolationService
    ↓ używa
Setting::get('legal.*')
Client->company_name, vat_number, full_address, primary_contact_*
Project->title, budget, deadline, currency

ContractResource::form()
    ↓ wywołuje
ContractInterpolationService->interpolate()
    ↓ przy wyborze
contract_template_id (nowe pole)

AutomationEventListener
    ↓ rozszerzony o
Contract events (created, updated)
    ↓ wyzwala
ProcessAutomationJob z nowymi akcjami
```
