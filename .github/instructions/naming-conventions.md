# Naming Conventions

**Zasada nadrzędna:** Nazwy muszą być czytelne bez komentarza — nazwa opisuje CO i DLA KOGO.

---

## PHP / Laravel

### Pliki i klasy

| Typ | Format | Przykład |
|-----|--------|---------|
| Model | `PascalCase` | `LeadActivity`, `DomainOrder` |
| Action | `VerbNounAction` | `CreateLeadAction`, `SendInvoiceAction` |
| Controller | `NounController` | `LeadController`, `DomainController` |
| Form Request | `VerbNounRequest` | `StoreLeadRequest`, `UpdateClientRequest` |
| Policy | `NounPolicy` | `LeadPolicy`, `InvoicePolicy` |
| Event | `NounPastTense` | `LeadCreated`, `InvoicePaid` |
| Listener | `VerbNounOnEvent` | `SendWelcomeEmailOnLeadCreated` |
| Job | `VerbNounJob` | `SendInvoiceEmailJob`, `ProcessDomainRenewalJob` |
| Service | `NounService` | `StripeService`, `GoogleCalendarService` |
| DTO | `NounData` | `LeadData`, `DomainOrderData` |
| Resource | `NounResource` | `LeadResource`, `ClientResource` |
| Middleware | `DescriptionMiddleware` | `EnsureBusinessOwner` |
| Seeder | `NounSeeder` | `LeadSeeder`, `DomainPriceListSeeder` |
| Factory | `NounFactory` | `LeadFactory` |

### Metody PHP

| Kontekst | Format | Przykład |
|----------|--------|---------|
| Action główna | `execute()` | `execute(LeadData $data): Lead` |
| Relacje | `camelCase singularOrPlural` | `leads()`, `business()` |
| Scope | `scope` + PascalCase | `scopeForBusiness()`, `scopeActive()` |
| Pomocnicze | `camelCase` czasownik | `formatAmount()`, `isExpired()` |
| Gettery | `getXxx()` | `getStatusLabel()` |
| Boole | `is`/`has`/`can` | `isActive()`, `hasPayment()`, `canEdit()` |

### Migracje

Format: `YYYY_MM_DD_HHMMSS_verb_noun_table.php`

```
2026_06_01_120000_create_something_items_table.php
2026_06_01_120001_add_status_to_leads_table.php
2026_06_01_120002_rename_old_field_in_clients_table.php
```

### Trasy (routes)

```php
// RESTful — zawsze snake_case
Route::resource('lead-activities', LeadActivityController::class);

// Niestandardowe — czasownik jako prefiks lub sufiksy
Route::post('leads/{lead}/activate', [LeadController::class, 'activate'])->name('leads.activate');
Route::get('reports/monthly', [ReportController::class, 'monthly'])->name('reports.monthly');
```

---

## TypeScript / React

### Komponenty i pliki

| Typ | Format | Przykład |
|-----|--------|---------|
| Komponent | `PascalCase.tsx` | `LeadCard.tsx`, `InvoiceForm.tsx` |
| Inertia Page | `PascalCase.tsx` | `Index.tsx`, `Show.tsx`, `Create.tsx` |
| Custom Hook | `camelCase.ts` | `useLead.ts`, `useInvoiceForm.ts` |
| TypeScript typy | `types.ts` | `resources/js/types/leads.ts` |
| Utilities | `camelCase.ts` | `formatCurrency.ts`, `dateHelpers.ts` |

### Interface i typy

```tsx
// Interface dla props — zawsze z suffix Props
interface LeadCardProps {
  lead: Lead;
  onStatusChange?: (id: number, status: string) => void;
}

// Interface dla danych modelu — PascalCase bez suffix
interface Lead {
  id: number;
  name: string;
  status: LeadStatus;
}

// Enum jako union type
type LeadStatus = 'new' | 'contacted' | 'qualified' | 'closed';
```

---

## Baza danych

### Tabele

- `snake_case` plural: `leads`, `lead_activities`, `domain_orders`
- Pivot tables: `{table1}_{table2}` alfabetycznie: `project_user`, `lead_tag`

### Kolumny

- `snake_case`: `business_id`, `created_at`, `first_name`
- Foreign keys: `{singular_table}_id`: `lead_id`, `business_id`
- Booleans: `is_` prefix: `is_active`, `is_verified`
- Timestamps: `{action}_at`: `paid_at`, `activated_at`, `synced_at`

---

## Tłumaczenia — klucze

Format: `{domain}.{action}` lub `{domain}.{field}_{context}`

```php
// Ogólne
'common.save'      // Zapisz
'common.cancel'    // Anuluj
'common.delete'    // Usuń

// Domenowe
'leads.create'             // Utwórz lead
'leads.status.new'         // Nowy
'leads.status.contacted'   // Skontaktowano

// Walidacja
'validation.name_required'
'validation.email_invalid'
```
