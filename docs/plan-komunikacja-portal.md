# Plan: Zarządzanie zgodami na komunikację — Portal Klienta

## Dlaczego to potrzebne?

Aktualnie system wysyła e-maile i SMS-y do klientów **bez żadnej kontroli po stronie klienta**:
- `StripeWebhookController` → `PaymentReceivedMail` + SMS po każdej płatności
- `PayuWebhookController` → jw.
- `ProcessAutomationJob` → `send_email`, `send_sms` bez sprawdzenia zgody
- `InvoiceSentMail`, `QuoteSentMail`, `PortalInviteMail` — brak mechanizmu opt-out

Z perspektyw prawnej (RODO / GDPR UK) i UX:
- klient musi mieć możliwość **samodzielnego zarządzania** tym, co od nas dostaje
- różne typy wiadomości wymagają różnych podstaw prawnych (transakcyjne vs. marketingowe)
- `SmsService::send()` i każda klasa `Mail` muszą respektować preferencje

---

## Stan wyjściowy — braki

| Element | Status |
|---|---|
| Kolumny `notify_*` na tabeli `clients` | ❌ brak |
| Portal: strona preferencji | ❌ brak |
| `SmsService` — sprawdzanie zgody | ❌ brak |
| `ProcessAutomationJob` — sprawdzanie zgody | ❌ brak |
| Webhooki Stripe/PayU — sprawdzanie zgody | ❌ częściowe (SMS tylko gdy `twilio_enabled`) |
| Filament: widoczność preferencji klienta | ❌ brak |

---

## Projektowane kategorie zgód

System rozróżnia **4 kategorie** wiadomości:

| Klucz | Opis | Dotyczy | Domyślnie |
|---|---|---|---|
| `notify_email_transactional` | Faktury, potwierdzenia płatności, dostarczanie umów/ofert | `InvoiceSentMail`, `PaymentReceivedMail`, `QuoteSentMail`, `ContractSentMail` | **ON** |
| `notify_email_projects` | Aktualizacje projektów, wiadomości w projekcie, zmiany statusu | `ProjectStatusMail`, `ClientEmailMail` | **ON** |
| `notify_email_marketing` | Wiadomości z automatyzacji (trigger: `send_email`) | `ProcessAutomationJob` → `send_email` | **ON** |
| `notify_sms` | Wszystkie SMS-y (potwierdzenia płatności + automatyzacja) | `SmsService::send()` zawsze | **ON** |

Dodatkowe pole: `communication_prefs_updated_at` — timestamp ostatniej zmiany (dla RODO — ślad audytowy).

### Dlaczego tak, a nie inaczej?

- **Transakcyjne oddzielnie** — klient może chcieć wyłączyć marketing, ale nadal dostawać faktury
- **Projekty oddzielnie** — wiadomości w projekcie to core usługi, warto je odróżnić od marketingu
- **SMS jako jeden toggle** — SMSy są bardziej inwazyjne; prostszy UX to jeden przełącznik
- **Marketing jako osobna kategoria** — podstawa prawna jest inna (zgoda vs. uzasadniony interes)

---

## Plan wdrożenia — 4 fazy

### Faza 1 — Migracja kolumn `~30 min`

Nowa migracja `add_communication_prefs_to_clients_table`:

```php
$table->boolean('notify_email_transactional')->default(true);
$table->boolean('notify_email_projects')->default(true);
$table->boolean('notify_email_marketing')->default(true);
$table->boolean('notify_sms')->default(true);
$table->timestamp('communication_prefs_updated_at')->nullable();
```

Aktualizacja `Client::$fillable` i `$casts`.

---

### Faza 2 — Portal: strona preferencji `~2h`

**Nowy route:**
```
GET  /portal/settings/notifications         → portal.settings.notifications
POST /portal/settings/notifications         → portal.settings.notifications.update
```

**Kontroler** `PortalController::notificationSettings()` + `updateNotificationSettings()`:
- pobiera `$client` z `clientForUser()`
- waliduje `boolean` dla każdego pola
- zapisuje + ustawia `communication_prefs_updated_at = now()`
- redirect z `?saved=1`

**React page** `Portal/NotificationSettings.jsx`:
- tytuł "Communication Preferences"
- 4 sekcje z opisem + toggle (Switch) dla każdej kategorii
- ostrzeżenie przy wyłączaniu transakcyjnych: _"You may not receive invoices or payment receipts"_
- przycisk "Save preferences"
- baner sukcesu po zapisie
- `← Back to Dashboard`

**Nawigacja** — nowy link w `PortalLayout.jsx`:
```
⚙️ Notifications   → portal.settings.notifications
```
(obok istniejącego "Account Settings" w stopce)

---

### Faza 3 — Bramki zgody w backendzie `~2h`

Każde miejsce wysyłające komunikację musi sprawdzić preferencje **przed** wysłaniem.

#### 3a. Helper `ClientNotificationGate`

Nowa klasa `app/Services/ClientNotificationGate.php`:

```php
class ClientNotificationGate
{
    public static function canSendEmail(Client $client, string $type): bool
    // $type = 'transactional' | 'projects' | 'marketing'

    public static function canSendSms(Client $client): bool
}
```

#### 3b. Miejsca do zaktualizowania

| Plik | Zmiana |
|---|---|
| `StripeWebhookController::sendPaymentNotifications()` | Wrap `Mail::to()` i `SmsService::send()` w `canSendEmail('transactional')` / `canSendSms()` |
| `PayuWebhookController::notify()` | jw. |
| `ProcessAutomationJob` action `send_email` | Sprawdź `canSendEmail('marketing')` |
| `ProcessAutomationJob` action `send_sms` | Sprawdź `canSendSms()` |
| `ViewInvoice.php` → action `send_invoice` | `canSendEmail('transactional')` (lub wysyłaj zawsze — to nie marketing) |
| `SmsService::send()` | Opcjonalnie: obsługa globalnego flaga, ale **nie** tu sprawdzamy per-klient (brak kontekstu) |

> **Ważne:** `InvoiceSentMail`, `QuoteSentMail`, `PortalInviteMail` są wysyłane z akcji admina, nie automatycznie — decyzja o wysłaniu jest świadoma, więc tu bramka jest opcjonalna (informacyjna). Skupiamy się na **automatycznych** wysyłkach.

---

### Faza 4 — Widoczność w Filament `~1h`

W widoku `ClientResource > View` (lub `Edit`):

- Nowa sekcja "Communication Preferences" pokazująca 4 toggle'e (read-only w widoku, edit w formularzu)
- `communication_prefs_updated_at` — data ostatniej zmiany, jeśli admin chce wiedzieć

Opcjonalnie: filtr w liście klientów "SMS opted out" / "Email marketing opted out" — przydatne dla bulk campaigns.

---

## Struktura plików

```
app/
  Services/
    ClientNotificationGate.php          ← NOWY
  Http/Controllers/
    PortalController.php                ← +2 metody
  Models/
    Client.php                          ← +5 pól w fillable/casts

database/migrations/
  YYYY_MM_DD_add_communication_prefs_to_clients_table.php   ← NOWY

resources/js/Pages/Portal/
  NotificationSettings.jsx              ← NOWY
resources/js/Layouts/
  PortalLayout.jsx                      ← +link w nav

routes/web.php                          ← +2 routes
```

---

## Kolejność wdrożenia

```
[1] Faza 1 — Migracja          │ blokuje wszystko
[2] Faza 2 — Portal UI         │ daje klientowi kontrolę
[3] Faza 3 — Bramki zgody      │ egzekwuje preferencje
[4] Faza 4 — Filament          │ widoczność dla admina
```

---

## Pytania do podjęcia decyzji przed implementacją

1. **Czy wysyłka faktury przez admina** (akcja "Send Invoice") powinna respektować `notify_email_transactional`?  
   → Proponuję: **NIE** — to świadoma decyzja admina; zamiast blokowania, pokaż ostrzeżenie.

2. **Czy nowe konta portalu** (PortalInvite) mają dostawać e-mail zawsze?  
   → Proponuję: **TAK** — zaproszenie do portalu to nie marketing.

3. **Czy `notify_email_transactional = false`** powinno blokować `PaymentReceivedMail`?  
   → Proponuję: **TAK** — klient świadomie wybrał, szanujemy (z ostrzeżeniem w UI).

4. **Czy chcesz log/audit trail** — np. tabela `communication_consent_logs(client_id, field, old_value, new_value, changed_by, ip, created_at)`?  
   → Dla RODO UK może być wymagany; proponuję dodać jako opcję w Fazie 1.

---

Od której fazy zaczynamy?
