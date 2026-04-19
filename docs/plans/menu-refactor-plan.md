# Plan reorganizacji menu panelu administracyjnego

**Data analizy**: 2026-04-17  
**Dotyczy**: `app/Providers/Filament/AdminPanelProvider.php` + wszystkie Resources i Pages

---

## 1. Stan aktualny — mapa menu

### Grupy nawigacyjne (zdefiniowane w `AdminPanelProvider`)

```
Dashboard (brak grupy)
├── CRM
├── Projects
├── Finance
├── Marketing
├── SaaS Billing  [collapsed]
└── Settings      [collapsed]
```

### Szczegółowa zawartość grup

#### 🔵 CRM (4 pozycje)
| sort | Label | Klasa |
|------|-------|-------|
| 1 | Clients | `ClientResource` |
| 2 | Leads | `LeadResource` |
| 3 | Sales Pipeline | `PipelinePage` |
| 4 | Pipeline Stages | `PipelineStageResource` |

#### 🟤 Projects (2 pozycje)
| sort | Label | Klasa |
|------|-------|-------|
| 1 | Projects | `ProjectResource` |
| 5 | Project Templates | `ProjectTemplateResource` |

#### 🟢 Finance (5 pozycji)
| sort | Label | Klasa |
|------|-------|-------|
| 1 | Invoices | `InvoiceResource` |
| 2 | Quotes | `QuoteResource` |
| 3 | Contracts | `ContractResource` |
| 3 | Payments | `PaymentResource` |
| 4 | Contract Templates | `ContractTemplateResource` |

> ⚠️ **Konflikt sort**: `ContractResource` i `PaymentResource` mają ten sam `navigationSort = 3`

#### 🟡 Marketing (7 pozycji)
| sort | Label | Klasa |
|------|-------|-------|
| 1 | Landing Pages | `LandingPageResource` |
| 1 | Email Templates | `EmailTemplateResource` |
| 2 | Front-end Sections | `SiteSectionResource` |
| 3 | Portfolio Projects | `PortfolioProjectResource` |
| 4 | Services | `ServiceItemResource` |
| 6 | SMS Templates | `SmsTemplateResource` |
| — | (brak) | `PageResource` — **błąd**: group=Settings, label=CMS Pages |

> ⚠️ **Konflikt sort**: `LandingPageResource` i `EmailTemplateResource` mają `sort = 1`

#### 🟣 SaaS Billing (3 pozycje, collapsed)
| sort | Label | Klasa |
|------|-------|-------|
| 5 | Plans | `PlanResource` |
| 10 | Businesses | `BusinessResource` |
| 20 | Subscriptions | `SubscriptionResource` |

#### ⚙️ Settings (13 pozycji — zbyt rozbudowana!)
| sort | Label | Klasa |
|------|-------|-------|
| 1 | Users | `UserResource` |
| 2 | Roles & Permissions | `RoleResource` |
| 2 | Calculator | `CalculatorAdminPage` |
| 2 | Active Sessions | `SessionResource` |
| 3 | Calculator Pricing | `CalculatorPricingResource` |
| 3 | Calculator Strings | `CalculatorStringsResource` |
| 3 | Permissions | `PermissionResource` |
| 3 | CMS Pages | `PageResource` |
| 4 | Automation Rules | `AutomationRuleResource` |
| 4 | Calculator Steps | `CalculatorStepsResource` |
| 5 | Automation Logs | `AutomationLogResource` |
| 9 | Legal & Company | `LegalSettingsPage` |
| 10 | Tracking & Analytics | `TrackingSettingsPage` |
| 12 | Payments | `PaymentSettingsPage` |
| 20 | Integrations | `IntegrationSettingsPage` |
| 20 | Notifications | `NotificationResource` |
| 25 | Sitemap | `SitemapPage` |

> ⚠️ Wiele konfliktów sort: 2 (x3), 3 (x4), 4 (x2), 20 (x2)  
> ⚠️ `Automation Triggers` bez zdefiniowanej grupy (fallback do Settings)  
> ⚠️ `AutomationTriggerResource` — `sort=3`, ale brakuje go w powyższej liście bo używa tej samej wartości co inne

#### 📊 Reports (1 pozycja — osobna grupa!)
| sort | Label | Klasa |
|------|-------|-------|
| 10 | Conversion Report | `ConversionReportPage` |

---

## 2. Zidentyfikowane problemy

### P1 — Group "Settings" jest przepełniona (13+ pozycji) ⛔ HIGH
13 różnych pozycji w jednej grupie to chaos nawigacyjny. Miks odpowiedzialności: automation, calculator, uprawnienia, CMS, integracje, prawne.

### P2 — Konflikty `navigationSort` ⛔ HIGH
Wiele pozycji ma ten sam `sort` w tej samej grupie — kolejność staje się niedeterministyczna:
- Settings: sort=2 (x3), sort=3 (x4), sort=4 (x2), sort=20 (x2)
- Finance: sort=3 (x2)
- Marketing: sort=1 (x2)

### P3 — Calculator rozproszony po Settings ⚠️ MEDIUM
`CalculatorAdminPage`, `CalculatorPricingResource`, `CalculatorStepsResource`, `CalculatorStringsResource` — 4 pozycje kalkuatora "ukryte" w Settings, zamiast mieć własne miejsce lub podgrupę.

### P4 — Automation w Settings zamiast we własnej grupie ⚠️ MEDIUM
`AutomationRuleResource`, `AutomationTriggerResource`, `AutomationLogResource` — wszystkie 3 siedzą w Settings. Tymczasem automation to osobny system, który rośnie.

### P5 — Brak grupy "Reports" jako stały element ⚠️ MEDIUM
`ConversionReportPage` tworzy grupę "Reports" jako jedyna pozycja. Nie ma jej w `AdminPanelProvider::navigationGroups()` — co oznacza że sortowanie grupy względem innych jest niecontrolowane.

### P6 — `PageResource` (CMS Pages) przypisany do "Settings" ⚠️ MEDIUM
CMS Pages logicznie należy do Marketing lub własnej grupy Content, nie do Settings.

### P7 — `PaymentSettingsPage` i `PaymentResource` — zduplikowany label ℹ️ LOW
Oba mają label "Payments" — jedno w Finance, drugie w Settings. Użytkownik może się zgubić.

### P8 — `PermissionResource` + `RoleResource` — duplikacja ℹ️ LOW
Dwie pozycje zarządzające uprawnieniami w tej samej grupie. Można je połączyć lub wyraźniej rozróżnić.

### P9 — `NotificationResource` w Settings ℹ️ LOW
Notyfikacje to nie ustawienie systemu — to operacyjne dane (historia wysłanych notyfikacji). Mogłoby należeć do CRM lub Reports.

---

## 3. Proponowana nowa struktura menu

```
Dashboard
├── CRM                     ← bez zmian, +sort fix
│   ├── (1) Clients
│   ├── (2) Leads
│   ├── (3) Sales Pipeline
│   └── (4) Pipeline Stages
│
├── Projects                ← bez zmian, +sort fix
│   ├── (1) Projects
│   └── (2) Project Templates
│
├── Finance                 ← sort fix, Payments przeniesione
│   ├── (1) Invoices
│   ├── (2) Quotes
│   ├── (3) Contracts
│   ├── (4) Contract Templates
│   └── (5) Payments
│
├── Marketing               ← sort fix, +CMS Pages
│   ├── (1) Landing Pages
│   ├── (2) Email Templates
│   ├── (3) SMS Templates
│   ├── (4) Services
│   ├── (5) Portfolio Projects
│   ├── (6) Front-end Sections
│   └── (7) CMS Pages       ← przeniesione z Settings
│
├── Automation  [NOWA]      ← wydzielona z Settings
│   ├── (1) Automation Rules
│   ├── (2) Automation Triggers
│   └── (3) Automation Logs
│
├── Reports  [NOWA - collapsed]
│   ├── (1) Conversion Report
│   └── (2) Notifications   ← historia notyfikacji, przeniesione z Settings
│
├── SaaS Billing [collapsed]← bez zmian, +sort fix
│   ├── (1) Plans
│   ├── (2) Businesses
│   └── (3) Subscriptions
│
└── Settings [collapsed]    ← odchudzona: tylko system/konfiguracja
    ├── (1) Users
    ├── (2) Roles & Permissions
    ├── (3) Permissions
    ├── (4) Active Sessions
    ├── (5) Calculator       ← Calculator Admin Page
    ├── (6) Calculator Steps
    ├── (7) Calculator Pricing
    ├── (8) Calculator Strings
    ├── (9) Legal & Company
    ├── (10) Payment Settings ← zmiana label na "Payment Settings"
    ├── (11) Tracking & Analytics
    ├── (12) Integrations
    └── (13) Sitemap
```

---

## 4. Plan zmian — krok po kroku

### Krok 1 — Dodaj grupy do `AdminPanelProvider` (1 plik)
**Plik**: `app/Providers/Filament/AdminPanelProvider.php`

```php
->navigationGroups([
    NavigationGroup::make('CRM'),
    NavigationGroup::make('Projects'),
    NavigationGroup::make('Finance'),
    NavigationGroup::make('Marketing'),
    NavigationGroup::make('Automation'),
    NavigationGroup::make('Reports')->collapsed(),
    NavigationGroup::make('SaaS Billing')->collapsed(),
    NavigationGroup::make('Settings')->collapsed(),
])
```

---

### Krok 2 — Przenieś CMS Pages do Marketing (1 plik)
**Plik**: `app/Filament/Resources/PageResource.php`
```php
// ZMIANA:
protected static ?string $navigationGroup = 'Marketing';  // było: 'Settings'
protected static ?int $navigationSort = 7;                 // było: 3
```

---

### Krok 3 — Wydziel grupę Automation (3 pliki)
**Plik**: `app/Filament/Resources/AutomationRuleResource.php`
```php
protected static ?string $navigationGroup = 'Automation'; // było: 'Settings'
protected static ?int $navigationSort = 1;                 // było: 4
```

**Plik**: `app/Filament/Resources/AutomationTriggerResource.php`
```php
protected static ?string $navigationGroup = 'Automation'; // było: 'Settings'
protected static ?int $navigationSort = 2;                 // było: 3
```

**Plik**: `app/Filament/Resources/AutomationLogResource.php`
```php
protected static ?string $navigationGroup = 'Automation'; // było: 'Settings'
protected static ?int $navigationSort = 3;                 // było: 5
```

---

### Krok 4 — Napraw sortowanie w Settings (wiele plików)
**Plik**: `app/Filament/Resources/UserResource.php`
```php
protected static ?int $navigationSort = 1; // bez zmian
```

**Plik**: `app/Filament/Resources/RoleResource.php`
```php
protected static ?int $navigationSort = 2; // bez zmian
```

**Plik**: `app/Filament/Resources/PermissionResource.php`
```php
protected static ?int $navigationSort = 3; // było: 3 (pozostaje, ale inne zostaną zmienione)
```

**Plik**: `app/Filament/Resources/SessionResource.php`
```php
protected static ?int $navigationSort = 4; // było: 2 — konflikt!
```

**Plik**: `app/Filament/Pages/CalculatorAdminPage.php`
```php
protected static ?int $navigationSort = 5; // było: 2 — konflikt!
```

**Plik**: `app/Filament/Resources/CalculatorStepsResource.php`
```php
protected static ?int $navigationSort = 6; // było: 4
```

**Plik**: `app/Filament/Resources/CalculatorPricingResource.php`
```php
protected static ?int $navigationSort = 7; // było: 2 — konflikt!
```

**Plik**: `app/Filament/Resources/CalculatorStringsResource.php`
```php
protected static ?int $navigationSort = 8; // było: 3 — konflikt!
```

**Plik**: `app/Filament/Pages/LegalSettingsPage.php`
```php
protected static ?int $navigationSort = 9; // bez zmian
```

**Plik**: `app/Filament/Pages/PaymentSettingsPage.php`
```php
protected static ?string $navigationLabel = 'Payment Settings'; // było: 'Payments' — duplikat!
protected static ?int $navigationSort = 10;                      // było: 12
```

**Plik**: `app/Filament/Pages/TrackingSettingsPage.php`
```php
protected static ?int $navigationSort = 11; // było: 10 — konflikt z LegalSettings
```

**Plik**: `app/Filament/Pages/IntegrationSettingsPage.php`
```php
protected static ?int $navigationSort = 12; // było: 20
```

**Plik**: `app/Filament/Pages/SitemapPage.php`
```php
protected static ?int $navigationSort = 13; // było: 25
```

---

### Krok 5 — Przenieś Notifications do Reports (1 plik)
**Plik**: `app/Filament/Resources/NotificationResource.php`
```php
protected static ?string $navigationGroup = 'Reports'; // było: 'Settings'
protected static ?int $navigationSort = 2;              // było: 20
```

---

### Krok 6 — Dodaj Conversion Report do navigationGroups i napraw sort (1 plik)
**Plik**: `app/Filament/Pages/ConversionReportPage.php`
```php
protected static ?int $navigationSort = 1; // było: 10
```

---

### Krok 7 — Napraw konflikty sort w Finance (2 pliki)
**Plik**: `app/Filament/Resources/ContractResource.php`
```php
protected static ?int $navigationSort = 3; // bez zmian
```

**Plik**: `app/Filament/Resources/PaymentResource.php`
```php
protected static ?int $navigationSort = 5; // było: 3 — KONFLIKT z ContractResource!
```

**Plik**: `app/Filament/Resources/ContractTemplateResource.php`
```php
protected static ?int $navigationSort = 4; // bez zmian
```

---

### Krok 8 — Napraw konflikty sort w Marketing (2 pliki)
**Plik**: `app/Filament/Resources/LandingPageResource.php`
```php
protected static ?int $navigationSort = 1; // bez zmian
```

**Plik**: `app/Filament/Resources/EmailTemplateResource.php`
```php
protected static ?int $navigationSort = 2; // było: 1 — KONFLIKT z LandingPageResource!
```

**Plik**: `app/Filament/Resources/SmsTemplateResource.php`
```php
protected static ?int $navigationSort = 3; // było: 6
```

**Plik**: `app/Filament/Resources/ServiceItemResource.php`
```php
protected static ?int $navigationSort = 4; // bez zmian
```

**Plik**: `app/Filament/Resources/PortfolioProjectResource.php`
```php
protected static ?int $navigationSort = 5; // było: 3 — konflikt z EmailTemplates po zmianie
```

**Plik**: `app/Filament/Resources/SiteSectionResource.php`
```php
protected static ?int $navigationSort = 6; // było: 2
```

---

### Krok 9 — Napraw sort w SaaS Billing (3 pliki)
**Plik**: `app/Filament/Resources/PlanResource.php`
```php
protected static ?int $navigationSort = 1; // było: 5
```

**Plik**: `app/Filament/Resources/BusinessResource.php`
```php
protected static ?int $navigationSort = 2; // było: 10
```

**Plik**: `app/Filament/Resources/SubscriptionResource.php`
```php
protected static ?int $navigationSort = 3; // było: 20
```

---

## 5. Podsumowanie zmian

| # | Zmiana | Typ | Priorytet | Pliki |
|---|--------|-----|-----------|-------|
| 1 | Dodanie grup Automation + Reports do AdminPanelProvider | strukturalna | HIGH | 1 |
| 2 | Przeniesienie CMS Pages z Settings do Marketing | grupowanie | HIGH | 1 |
| 3 | Wydzielenie grupy Automation (3 resources) | grupowanie | HIGH | 3 |
| 4 | Naprawa konfliktów sort w Settings (13 pozycji) | sort | HIGH | 10 |
| 5 | Zmiana label PaymentSettingsPage na "Payment Settings" | label | HIGH | 1 |
| 6 | Przeniesienie Notifications do Reports | grupowanie | MEDIUM | 1 |
| 7 | Naprawa konfliktów sort w Finance | sort | MEDIUM | 2 |
| 8 | Naprawa konfliktów sort w Marketing | sort | MEDIUM | 4 |
| 9 | Naprawa sort w SaaS Billing (ciągłość 1-2-3) | sort | LOW | 3 |
| 10 | Naprawa sort w Projects (Templates: 5→2) | sort | LOW | 1 |

**Łącznie plików do modyfikacji**: ~27  
**Zmiany tylko w `navigationGroup` / `navigationSort` / `navigationLabel`** — brak zmian w logice biznesowej.

---

## 6. Kolejność wdrożenia

```
1. AdminPanelProvider — grupy
2. Automation (3 pliki) — nowa grupa, od razu widoczna
3. Marketing — sort + CMS Pages
4. Finance — sort
5. Settings — sort + label fixes
6. Reports — Notifications przeniesione
7. SaaS Billing — sort cleanup
8. Projects — sort cleanup
```

> **Uwaga**: Wszystkie zmiany są bezpieczne — dotyczą tylko wartości `navigationGroup`, `navigationSort`, `navigationLabel`. Nie wpływają na routing, uprawnienia ani logikę CRUD.
