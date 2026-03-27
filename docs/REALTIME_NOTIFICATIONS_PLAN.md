# Plan implementacji: Real-Time System Powiadomień (Admin Panel)

**Stack:** Laravel 13 · Filament v5 · Laravel Reverb (WebSocket) · Laravel Echo · Filament Notifications  
**Data powstania planu:** 2026-03-26

---

## 1. Cel i zakres

System powiadomień w czasie rzeczywistym dla użytkowników panelu admina (`/admin`).  
Powiadomienie pojawia się w dzwoneczku Filament bez przeładowania strony, gdy w systemie zajdzie ważne zdarzenie.

### Co będzie powiadamiać:

| Zdarzenie | Komunikat |
|-----------|-----------|
| Nowy lead z formularza kontaktowego | „Nowy lead: Jan Kowalski (jan@firma.pl)" |
| Nowy lead z kalkulatora wyceny | „Nowy lead z kalkulatora: budżet £1200" |
| Fake Nowa wiadomość w projekcie (od klienta) | „Nowa wiadomość: Projekt XYZ → client@email.com" |
| Projekt zmienił fazę | „Projekt XYZ przeszedł do fazy: Testing" |
| Faktura opłacona | „Faktura #INV-042 opłacona przez Firma Sp. z o.o. (£999)" |
| Faktura przeterminowana | „Faktura #INV-038 jest przeterminowana — 5 dni po terminie" |
| Oferta zaakceptowana przez klienta | „Oferta #QUO-011 zaakceptowana przez Jan Kowalski" |
| Wyzwolona reguła automatyzacji | „Automatyzacja: wysłano email powitalny dla Projekt XYZ" |

---

## 2. Kontrola z panelu admina — UI zarządzania powiadomieniami

### Krótka odpowiedź: TAK — i już masz do tego fundament

Projekt posiada gotowy system `AutomationRules` z modelem, jobem i UI w Filament.  
`ProcessAutomationJob` **już ma zdefiniowany typ akcji `notify_admin`** — aktualnie wysyła tylko email.  
Wystarczy go rozszerzyć, żeby zamiast (lub obok) emaila wysyłał powiadomienie real-time do panelu.

Dzięki temu **nie tworzysz osobnego systemu** — zarządzasz powiadomieniami dokładnie tak samo jak innymi regułami automatyzacji.

---

### 2.1 Co będziesz mógł zrobić z UI

#### Tworzenie reguły (Admin → Automations → New Rule):

```
Trigger event:  [lead.created ▼]
Conditions:     [source] [=] [calculator]      ← opcjonalne filtry
Delay (min):    0
Actions:
  + [notify_admin ▼]
      Title:   "Nowy lead z kalkulatora: {lead.name}"
      Body:    "Email: {lead.email} | Budżet: {context.budget}"
      Icon:    [heroicon-o-user-plus ▼]
      Color:   [success ▼]
      URL:     "/admin/leads/{lead.id}"
      Target:  [admin, manager ☑]  [developer ☐]
```

#### Efekt:
Gdy klient wypełni kalkulator → powiadomienie w panelu pojawi się natychmiast,  
z customowym tytułem, treścią i linkiem do rekordu.

---

### 2.2 Dostępne zmienne (placeholdery) w szablonach tytułu/treści

System zastępuje `{klucz}` wartością z kontekstu zdarzenia.

| Placeholder | Opis | Dostępny dla |
|-------------|------|-------------|
| `{lead.name}` | Imię i nazwisko leada | lead.* |
| `{lead.email}` | Email leada | lead.* |
| `{lead.source}` | Źródło (formularz/kalkulator) | lead.* |
| `{project.name}` | Nazwa projektu | project.* |
| `{project.status}` | Status projektu | project.* |
| `{invoice.number}` | Numer faktury | invoice.* |
| `{invoice.amount}` | Kwota faktury | invoice.* |
| `{invoice.client}` | Nazwa klienta | invoice.* |
| `{quote.number}` | Numer oferty | quote.* |
| `{quote.amount}` | Kwota oferty | quote.* |
| `{client.name}` | Nazwa firmy klienta | client.* |
| `{client.email}` | Email klienta | client.* |
| `{automation.rule_name}` | Nazwa reguły | wszystkie |
| `{trigger_event}` | Nazwa zdarzenia | wszystkie |

Placeholder `{lead.name}` mapuje się na `$context['lead_name']` w `buildTemplateVars()` — metoda już istnieje w JobProcessAutomationJob.

---

### 2.3 Dostępne zdarzenia (trigger events) — lista gotowa do wyboru w UI

Już obsługiwane przez `AutomationEventListener`:

| Klucz zdarzenia | Kiedy |
|----------------|-------|
| `lead.created` | Nowy lead z formularza |
| `lead.created_from_calculator` | Nowy lead z kalkulatora |
| `project.created` | Projekt utworzony |
| `project.phase_changed` | Projekt zmienił fazę |
| `project.message_received` | Klient wysłał wiadomość |
| `project.completed` | Projekt ukończony |
| `invoice.sent` | Faktura wysłana |
| `invoice.paid` | Faktura opłacona |
| `invoice.overdue` | Faktura przeterminowana |
| `quote.sent` | Oferta wysłana |
| `quote.accepted` | Oferta zaakceptowana |
| `quote.rejected` | Oferta odrzucona |
| `client.portal_accessed` | Klient zalogował się do portalu |

Nowe zdarzenia można dodać przez dispatch `event(new XxxEvent(...))` w odpowiednim miejscu kodu.

---

### 2.4 Przykładowe reguły gotowe do wgrania (seeder)

```php
// Reguła 1 — Nowy lead z formularza → alert dla admina
[
  'name'          => 'Alert: nowy lead',
  'trigger_event' => 'lead.created',
  'conditions'    => [],
  'actions'       => [[
    'type'   => 'notify_admin',
    'title'  => 'Nowy lead: {lead.name}',
    'body'   => '{lead.email} — {lead.source}',
    'icon'   => 'heroicon-o-user-plus',
    'color'  => 'success',
    'url'    => '/admin/leads/{lead.id}',
    'roles'  => ['admin', 'manager'],
  ]],
  'is_active' => true,
]

// Reguła 2 — Faktura opłacona (tylko jeśli > £500)
[
  'name'          => 'Alert: faktura opłacona',
  'trigger_event' => 'invoice.paid',
  'conditions'    => [['field' => 'amount', 'operator' => '>', 'value' => 500]],
  'actions'       => [[
    'type'   => 'notify_admin',
    'title'  => '💰 Faktura opłacona: {invoice.number}',
    'body'   => '{invoice.client} — £{invoice.amount}',
    'icon'   => 'heroicon-o-banknotes',
    'color'  => 'success',
    'url'    => '/admin/invoices/{invoice.id}',
    'roles'  => ['admin'],
  ]],
  'is_active' => true,
]
```

---

### 2.5 Co trzeba zmienić w istniejącym kodzie

**`ProcessAutomationJob::notifyAdmin()`** — aktualnie wysyła tylko email.  
Zmiana: zamiast emaila → `User::notify(new AdminRealtimeNotification(...))` z broadcastem.

```php
// PRZED (obecny kod):
private function notifyAdmin(array $action): void
{
    Mail::raw($body, fn($msg) => $msg->to($to)->subject($subject));
}

// PO (po implementacji):
private function notifyAdmin(array $action): void
{
    $title  = $this->interpolate($action['title'] ?? 'Powiadomienie', $this->buildTemplateVars());
    $body   = $this->interpolate($action['body']  ?? $this->triggerEvent, $this->buildTemplateVars());
    $roles  = $action['roles'] ?? ['admin', 'manager'];

    User::whereHas('roles', fn($q) => $q->whereIn('name', $roles))
        ->get()
        ->each->notify(new AdminRealtimeNotification(
            title: $title,
            body:  $body,
            url:   $this->interpolate($action['url'] ?? '', $this->buildTemplateVars()),
            icon:  $action['icon']  ?? 'heroicon-o-bell',
            color: $action['color'] ?? 'primary',
        ));
}

// helper do interpolacji placeholderów {klucz}:
private function interpolate(string $template, array $vars): string
{
    foreach ($vars as $key => $value) {
        $template = str_replace('{' . $key . '}', $value ?? '', $template);
    }
    return $template;
}
```

**`AutomationRuleResource` form** — dodanie pól dla `notify_admin` action:  
Repeater z `type` selector i polami warunkowymi (pokazują się gdy type = notify_admin).

---

### 2.6 Opcja: ręczne powiadomienie z rekordu

W `LeadResource`, `InvoiceResource`, `ProjectResource` — akcja "Wyślij powiadomienie do admina":

```php
Tables\Actions\Action::make('notify')
    ->icon('heroicon-o-bell')
    ->form([
        TextInput::make('title')->required(),
        Textarea::make('body'),
        Select::make('color')->options(['success','warning','danger','info']),
    ])
    ->action(function (array $data, Lead $record) {
        User::role(['admin','manager'])->get()->each->notify(
            new AdminRealtimeNotification(...$data, url: '/admin/leads/' . $record->id)
        );
    });
```

---

## 3. Wybór technologii: Laravel Reverb (self-hosted WebSocket)

### Dlaczego Reverb, nie Pusher?

| Kryterium | Laravel Reverb | Pusher |
|-----------|---------------|--------|
| Koszt | Darmowy (self-hosted) | Płatny po 200 połączeń |
| Kontrola | Pełna, własny serwer | Zewnętrzny SaaS |
| Opóźnienia | ~0ms (lokalnie) | ~50–100ms (external) |
| Konfiguracja | `composer require laravel/reverb` | Klucze API, rejestracja |
| Protokół | Kompatybilny Pusher (drop-in) | Pusher |
| Laravel 13 | Oficjalny pakiet 1st-party | Obsługiwany |

**Reverb** to oficjalny WebSocket server Laravel, kompatybilny z protokołem Pusher — `laravel-echo` działa bez zmian.

### Alternatywy (odrzucone):

- **Polling** — prosto w implementacji, ale obciąża serwer (~co 5s zapytania od każdego admina)  
- **SSE (Server-Sent Events)** — jednostronne, brak wsparcia Filament  
- **Soketi** — dobry, ale Reverb ma lepsze wsparcie ekosystemu Laravel  

---

## 3. Architektura systemu

```
[Zdarzenie w systemie]
        │
        ▼
[Event dispatched]  ──────────────────────────────────────────────┐
(NewLeadReceived, InvoicePaid, ProjectMessageSent, itd.)          │
        │                                                          │
        ▼                                                          ▼
[Listener: SendAdminNotification]               [DB: notifications table]
        │                                       (Laravel built-in, 
        ▼                                        morph do User)
[Laravel Reverb WS Server :8080]
        │
        ▼ WebSocket broadcast
[Laravel Echo (przeglądarka)]
        │
        ▼
[Filament Notifications.send()]  ───→ 🔔 Toast + Dzwoneczek w /admin
```

### Kanały:

- **Private channel:** `admins` — tylko zalogowani User z rolą admin/manager/developer  
- **Autoryzacja:** `routes/channels.php` + `BroadcastServiceProvider`

---

## 4. Struktura plików do stworzenia

```
app/
├── Events/
│   ├── NewLeadReceived.php              ← nowy lead (formularz/kalkulator)
│   ├── ProjectMessageSent.php           ← nowa wiadomość klienta
│   ├── ProjectPhasChanged.php           ← zmiana fazy projektu
│   ├── InvoicePaid.php                  ← faktura opłacona
│   ├── InvoiceOverdue.php               ← faktura przeterminowana
│   ├── QuoteAccepted.php                ← oferta zaakceptowana
│   └── AutomationTriggered.php         ← reguła automatyzacji wykonana
│
├── Listeners/
│   └── SendAdminNotification.php        ← jeden listener obsługuje wszystkie
│
├── Notifications/
│   └── AdminRealtimeNotification.php    ← Laravel Notification (database + broadcast)
│
database/
└── migrations/
    └── 2026_xx_xx_create_notifications_table.php  ← standard Laravel
```

---

## 5. Plan krok po kroku

### Krok 1 — Instalacja Laravel Reverb

```bash
composer require laravel/reverb
php artisan reverb:install
```

Generuje:
- `config/reverb.php`
- Aktualizuje `.env` z kluczami Reverb
- Rejestruje `BroadcastServiceProvider`

```env
BROADCAST_CONNECTION=reverb
REVERB_APP_ID=your-app-id
REVERB_APP_KEY=your-app-key
REVERB_APP_SECRET=your-app-secret
REVERB_HOST=localhost
REVERB_PORT=8080
REVERB_SCHEME=http

VITE_REVERB_APP_KEY="${REVERB_APP_KEY}"
VITE_REVERB_HOST="${REVERB_HOST}"
VITE_REVERB_PORT="${REVERB_PORT}"
VITE_REVERB_SCHEME="${REVERB_SCHEME}"
```

---

### Krok 2 — Instalacja Laravel Echo (frontend)

```bash
npm install laravel-echo pusher-js
```

Konfiguracja w `resources/js/bootstrap.js` (lub dedykowany plik ładowany tylko w panelu admin):

```js
import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

window.Pusher = Pusher;
window.Echo = new Echo({
    broadcaster: 'reverb',
    key: import.meta.env.VITE_REVERB_APP_KEY,
    wsHost: import.meta.env.VITE_REVERB_HOST,
    wsPort: import.meta.env.VITE_REVERB_PORT,
    wssPort: import.meta.env.VITE_REVERB_PORT,
    forceTLS: (import.meta.env.VITE_REVERB_SCHEME ?? 'https') === 'https',
    enabledTransports: ['ws', 'wss'],
});
```

> **Uwaga:** Filament ma już bundlowany `echo.js` z Pusher v7.6. Możemy to wykorzystać zamiast nowego install — wystarczy skonfigurować broadcasting w Filament Panel Provider.

---

### Krok 3 — Migracja tabeli notifications

Laravel ma wbudowany mechanizm:

```bash
php artisan notifications:table
php artisan migrate
```

Schemat tabeli `notifications`:
```
id (uuid)
type (string)          ← klasa notification
notifiable_type        ← 'App\Models\User'
notifiable_id          ← user.id
data (json)            ← tytuł, treść, URL, ikona, kolor
read_at (timestamp)    ← null = nieprzeczytane
created_at
```

---

### Krok 4 — Model User: HasDatabaseNotifications

```php
// app/Models/User.php
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use Notifiable; // już powinno być w standardowym Laravel
}
```

---

### Krok 5 — Eventy

Przykład `NewLeadReceived`:

```php
// app/Events/NewLeadReceived.php
class NewLeadReceived implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public Lead $lead) {}

    public function broadcastOn(): array
    {
        return [new PrivateChannel('admins')];
    }

    public function broadcastAs(): string
    {
        return 'new-lead';
    }

    public function broadcastWith(): array
    {
        return [
            'title'   => 'Nowy lead: ' . $this->lead->name,
            'body'    => $this->lead->email . ' — ' . ($this->lead->source ?? 'formularz'),
            'url'     => '/admin/leads/' . $this->lead->id,
            'icon'    => 'heroicon-o-user-plus',
            'color'   => 'success',
        ];
    }
}
```

---

### Krok 6 — Listener: zapis do DB + wysyłka do zalogowanych adminów

```php
// app/Listeners/SendAdminNotification.php
class SendAdminNotification
{
    public function handle(object $event): void
    {
        $admins = User::whereHas('roles', fn($q) => $q->whereIn('name', ['admin', 'manager', 'developer']))->get();
        
        $admins->each->notify(new AdminRealtimeNotification(
            title:  $event->broadcastWith()['title'],
            body:   $event->broadcastWith()['body'],
            url:    $event->broadcastWith()['url'],
            icon:   $event->broadcastWith()['icon'] ?? 'heroicon-o-bell',
            color:  $event->broadcastWith()['color'] ?? 'primary',
        ));
    }
}
```

---

### Krok 7 — Laravel Notification (DB + Broadcast)

```php
// app/Notifications/AdminRealtimeNotification.php
class AdminRealtimeNotification extends Notification implements ShouldBroadcast
{
    public function __construct(
        public string $title,
        public string $body,
        public string $url,
        public string $icon = 'heroicon-o-bell',
        public string $color = 'primary',
    ) {}

    public function via(object $notifiable): array
    {
        return ['database', 'broadcast'];
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'title' => $this->title,
            'body'  => $this->body,
            'url'   => $this->url,
            'icon'  => $this->icon,
            'color' => $this->color,
        ];
    }

    public function toBroadcast(object $notifiable): BroadcastMessage
    {
        return new BroadcastMessage($this->toDatabase($notifiable));
    }

    public function broadcastOn(): PrivateChannel
    {
        return new PrivateChannel('App.Models.User.' . $this->notifiable->id);
    }
}
```

---

### Krok 8 — Autoryzacja kanału

```php
// routes/channels.php
Broadcast::channel('App.Models.User.{id}', function (User $user, int $id) {
    return (int) $user->id === $id;
});

Broadcast::channel('admins', function (User $user) {
    return $user->hasAnyRole(['admin', 'manager', 'developer']);
});
```

---

### Krok 9 — Rejestracja w EventServiceProvider

```php
// app/Providers/EventServiceProvider.php
protected $listen = [
    NewLeadReceived::class     => [SendAdminNotification::class],
    ProjectMessageSent::class  => [SendAdminNotification::class],
    ProjectPhaseChanged::class => [SendAdminNotification::class],
    InvoicePaid::class         => [SendAdminNotification::class],
    InvoiceOverdue::class      => [SendAdminNotification::class],
    QuoteAccepted::class       => [SendAdminNotification::class],
    AutomationTriggered::class => [SendAdminNotification::class],
];
```

---

### Krok 10 — Dispatch eventów w istniejącym kodzie

Dodajemy `event(new XxxEvent(...))` w miejscach gdzie już dzieje się logika:

| Gdzie | Event |
|-------|-------|
| `ContactController@store` | `NewLeadReceived` |
| `CalculatorLeadController@store` | `NewLeadReceived` |
| `ProjectMessage::created` (observer) | `ProjectMessageSent` |
| `Project::updated` (faza zmieniona) | `ProjectPhaseChanged` |
| Webhook płatności IPN / PayU | `InvoicePaid` |
| Scheduled job sprawdzający faktury | `InvoiceOverdue` |
| `Quote::updated` (status → accepted) | `QuoteAccepted` |
| `ProcessAutomationJob::handle()` | `AutomationTriggered` |

---

### Krok 11 — Filament Panel: DatabaseNotifications widget

Filament v5 ma wbudowane wsparcie dla powiadomień bazodanowych.  
W `app/Providers/Filament/AdminPanelProvider.php`:

```php
use Filament\Notifications\Livewire\DatabaseNotifications;

->plugins([...])
->databaseNotifications()           // włącza dzwoneczek w topbarze
->databaseNotificationsPolling('30s') // fallback polling co 30s
```

Filament automatycznie:
- Wyświetla dzwoneczek 🔔 z licznikiem nieprzeczytanych
- Otwiera panel z historią powiadomień  
- Oznacza jako przeczytane po kliknięciu
- Linkuje do URL z powiadomienia

---

### Krok 12 — Real-time push z Reverb do Filament

Filament v5 obsługuje broadcasting przez Livewire. Dodajemy do Panel Provider:

```php
->broadcastDriver('reverb')
```

Lub konfigurujemy Echo bezpośrednio w Filament — nasłuchuje na kanale `App.Models.User.{id}` i wywołuje `FilamentNotification.send()` po stronie klienta gdy przyjdzie event.

---

### Krok 13 — Uruchomienie serwera Reverb

**Lokalnie (development):**
```bash
php artisan reverb:start
```

**Produkcja (supervisor lub systemd):**
```ini
[program:reverb]
command=php /var/www/app/artisan reverb:start --host=0.0.0.0 --port=8080
autostart=true
autorestart=true
user=www-data
redirect_stderr=true
```

---

## 6. Przepływ danych — przykład "Nowy lead"

```
1. Wizytant wypełnia formularz kontaktowy
2. ContactController::store() → Lead::create() → event(new NewLeadReceived($lead))
3. Laravel Queue Worker odbiera event
4. SendAdminNotification::handle() → User::notify(new AdminRealtimeNotification(...))
5. AdminRealtimeNotification::via() → ['database', 'broadcast']
6. → zapis do tabeli notifications
7. → BroadcastMessage wysyłana przez Reverb WebSocket
8. Przeglądarka admina odbiera wiadomość przez Echo
9. Filament (Livewire) odbiera broadcast → wyświetla toast + +1 na dzwoneczku
```

Czas od wysłania formularza do pojawienia się powiadomienia: **< 500ms**

---

## 7. Opcje konfiguracyjne (ustawienia admina)

Opcjonalnie — strona `NotificationSettingsPage` w Filament:

| Ustawienie | Domyślnie |
|-----------|-----------|
| Powiadamiaj o nowych leadach | ✅ |
| Powiadamiaj o wiadomościach w projektach | ✅ |
| Powiadamiaj o opłaconych fakturach | ✅ |
| Powiadamiaj o przeterminowanych fakturach | ✅ |
| Powiadamiaj o zaakceptowanych ofertach | ✅ |
| Powiadamiaj o automatyzacjach | ❌ (zbyt częste) |
| Dźwięk powiadomienia | ❌ |

Preferencje per-user, przechowywane w tabeli `user_notification_preferences`.

---

## 8. Kolejka (Queue)

Notification powinna być dispatchowana przez queue (nie synchronicznie), żeby nie blokować response:

```php
class AdminRealtimeNotification extends Notification implements ShouldQueue, ShouldBroadcast
```

Wymagane uruchomienie queue workera:
```bash
php artisan queue:work --queue=notifications,default
```

Na produkcji: supervisor process dla queue worker.

---

## 9. Bezpieczeństwo

- Kanały prywatne — tylko autoryzowani użytkownicy  
- Autoryzacja przez `routes/channels.php` (sprawdza rolę)  
- Reverb uruchamiany na osobnym porcie (8080), za nginx reverse proxy  
- Brak wrażliwych danych w payload broadcastu (tylko ID + opis)  
- CSRF-free WebSocket — połączenie autoryzowane przez Laravel session  

---

## 10. Checklist implementacji

```
[ ] 1. composer require laravel/reverb
[ ] 2. php artisan reverb:install
[ ] 3. npm install laravel-echo pusher-js
[ ] 4. php artisan notifications:table && php artisan migrate
[ ] 5. User::Notifiable — sprawdzić czy już jest
[ ] 6. Stworzyć app/Events/*.php (7 eventów)
[ ] 7. Stworzyć app/Notifications/AdminRealtimeNotification.php
[ ] 8. Stworzyć app/Listeners/SendAdminNotification.php
[ ] 9. Zaktualizować EventServiceProvider
[ ] 10. Dodać event() w ContactController, CalculatorLeadController
[ ] 11. Dodać Observer na ProjectMessage (wiadomości)
[ ] 12. Dodać Observer na Project (zmiana fazy)
[ ] 13. Dodać event() w webhook IPN (InvoicePaid)
[ ] 14. Dodać event() w ProcessAutomationJob
[ ] 15. routes/channels.php — autoryzacja kanałów
[ ] 16. AdminPanelProvider — ->databaseNotifications()
[ ] 17. Skonfigurować Echo w Filament (Reverb)
[ ] 18. Uruchomić reverb:start + queue:work
[ ] 19. Test end-to-end: formularz → powiadomienie w panelu
[ ] 20. Supervisor config na produkcji
```

---

## 11. Szacowany czas implementacji

| Etap | Czas |
|------|------|
| Instalacja + konfiguracja (Reverb, Echo, migrations) | ~1h |
| Eventy + Listener + Notification | ~2h |
| Dispatch w istniejącym kodzie (8 miejsc) | ~1.5h |
| Filament panel + DatabaseNotifications | ~0.5h |
| Testy + poprawki | ~1h |
| **Łącznie** | **~6h** |
