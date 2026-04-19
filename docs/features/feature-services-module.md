# Feature: Services Module

## Definicja modułu

**Cel**: Zarządzanie kartami usług (Services) wyświetlanymi na stronie głównej — ekstrakcja z `site_sections.extra.services` (JSON) do dedykowanej tabeli `service_items` z pełnym CRUD w panelu Filament.

**Bounded Context**: Marketing / Content Management

**Priorytet MVP**: MUST HAVE — sekcja Services jest widoczna na homepage

**Zależności**: `SiteSection` (key='services') pozostaje dla metadanych sekcji (tytuł, subtitle, badge, CTA)

**Użytkownik**: Admin agencji

---

## Model danych

### TABELA: `service_items`

| Kolumna | Typ | Opis |
|---------|-----|------|
| `id` | bigint PK | |
| `title` | JSON | Translatable: `{en, pl, pt}` |
| `description` | JSON | Translatable: `{en, pl, pt}` |
| `icon` | string(40) | Klucz ikony SVG (monitor, shopping-cart, code, search, bar-chart, settings, shield, pencil, zap, file-text) |
| `price_from` | string(30) nullable | Wyświetlana cena od (£799, £399/mo) |
| `link` | string(255) nullable | URL do strony szczegółów usługi |
| `slug` | string(100) nullable unique | Slug URL |
| `is_featured` | boolean default true | Widoczność na homepage |
| `is_active` | boolean default true | Czy link do szczegółów jest aktywny |
| `sort_order` | unsigned int default 0 | Kolejność wyświetlania |
| `created_at` | timestamp | |
| `updated_at` | timestamp | |
| `deleted_at` | timestamp | soft deletes |

Indeksy: `(is_featured, sort_order)`, `slug` unique

---

## Backend Laravel

### Model: `app/Models/ServiceItem.php`
- Traits: `HasFactory`, `SoftDeletes`, `HasTranslations`
- translatable: `['title', 'description']`
- Scopes: `featured()`, `active()`, `ordered()`

### Serwis: `app/Services/Marketing/ServiceItemService.php`
- `getFeatured(int $limit = 9)` — is_featured=true, ordered
- `getAll()` — all featured, ordered (dla /services page)
- `create(array $data): ServiceItem`
- `update(ServiceItem $item, array $data): ServiceItem`
- `delete(ServiceItem $item): void`

### WelcomeController
- Zamień `$services->extra.services` na dane z `ServiceItemService::getFeatured()`
- Zachowaj metadane sekcji z SiteSection (title, subtitle, button_text, button_url, badge)
- Przekaż w `extra.services` zamiast `extra.services` z JSON

### Filament: `app/Filament/Resources/ServiceItemResource.php`
- Navigation: group "Marketing", label "Services", icon `heroicon-o-wrench-screwdriver`, sort 4
- Table: icon badge, title.en, price_from, is_featured, is_active, sort_order (reorderable)
- Form tabs: Content (title/description per EN/PL/PT), Settings (icon select, price_from, link, slug, is_featured, is_active, sort_order)

---

## Frontend React

### Services.jsx
- Zamiast `extra.services ?? DEFAULTS.services` czyta dane z backend prop
- Zachowuje fallback do DEFAULTS (gdy brak danych)
- Dodaje obsługę `is_active` — link "Learn more" aktywny tylko gdy `true`

### Strony (opcjonalne v2): `/services`, `/services/{slug}`
- Analogiczne do portfolio module

---

## Checklist implementacji

- [x] `docs/feature-services-module.md`
- [ ] Migration `create_service_items_table`
- [ ] Model `ServiceItem`
- [ ] Seeder `ServiceItemSeeder` (9 usług z seedera SiteSection)
- [ ] Serwis `ServiceItemService`
- [ ] Update `WelcomeController`
- [ ] `ServiceItemResource` + Pages (List/Create/Edit)
- [ ] `SiteSectionResource` — hint przy sekcji services
- [ ] Update `Services.jsx`
- [ ] `php artisan migrate && db:seed --class=ServiceItemSeeder`
