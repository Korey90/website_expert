# Plan integracji narzędzi śledzących

> Wersja: 1.0 | Data: 20.03.2026  
> Stack: Laravel 13 + Inertia.js + React | Plik wejściowy: `resources/views/app.blade.php`

---

## Architektura

```
app.blade.php  ←  centrum ładowania wszystkich skryptów
     │
     ├─ GTM <head> snippet  (podmiana tytułu w <head>)
     ├─ GTM <body> noscript  (za @inertia)
     └─ Inertia shared props  ←  GTM ID, pixel ID itp. z .env
```

Wszystkie klucze/ID trzymamy w `.env` → `config/services.php` → przekazujemy do frontu przez `HandleInertiaRequests.php` jako shared props. Dzięki temu:
- żaden klucz nie jest hardcoded w kodzie
- zmiana środowiska (staging/prod) = zmiana `.env`
- GTM zarządza wszystkim reszta (GA4, Ads, Pixel) — **jeden snippet w kodzie źródłowym**

---

## Krok 0 — Zarządzanie konfiguracją z panelu admina

Wszystkie ID i flagi są edytowalne przez admina w Filament — bez dostępu do serwera.  
Wartości z DB nadpisują `.env` (DB = źródło prawdy w runtime; `.env` = fallback/dev).

### Architektura

```
Filament TrackingSettingsPage
        │  zapis
        ▼
   settings (tabela DB)  ──→  cache('settings.*')  TTL 1 dzień
        │                              │
        └──────────────────────────────┤
                                       ▼
                         HandleInertiaRequests::share()
                                       │
                                       ▼
                              React (window props)
                         GTM snippet / fbq / dataLayer
```

### 0.1 Migracja — tabela `settings`

```php
// database/migrations/xxxx_create_settings_table.php
Schema::create('settings', function (Blueprint $table) {
    $table->string('key')->primary();
    $table->text('value')->nullable();
    $table->string('group')->default('general')->index();
    $table->timestamps();
});
```

### 0.2 Model `Setting`

```php
// app/Models/Setting.php
class Setting extends Model
{
    protected $primaryKey = 'key';
    public $incrementing  = false;
    protected $keyType    = 'string';
    protected $fillable   = ['key', 'value', 'group'];

    /** Pobierz wartość z cache (1 dzień TTL). */
    public static function get(string $key, mixed $default = null): mixed
    {
        return cache()->remember("settings.{$key}", now()->addDay(), function () use ($key, $default) {
            return static::find($key)?->value ?? $default;
        });
    }

    /** Zapisz wartość i wyczyść cache. */
    public static function set(string $key, mixed $value, string $group = 'general'): void
    {
        static::updateOrCreate(['key' => $key], ['value' => $value, 'group' => $group]);
        cache()->forget("settings.{$key}");
    }
}
```

### 0.3 Seeder — domyślne wartości (z `.env` jako fallback)

```php
// database/seeders/SettingSeeder.php
$defaults = [
    // GTM
    ['key' => 'gtm_enabled',   'value' => '0',                         'group' => 'tracking'],
    ['key' => 'gtm_id',        'value' => env('GTM_ID', ''),           'group' => 'tracking'],
    // GA4
    ['key' => 'ga4_enabled',   'value' => '0',                         'group' => 'tracking'],
    ['key' => 'ga4_id',        'value' => env('GA4_ID', ''),           'group' => 'tracking'],
    // Meta Pixel
    ['key' => 'pixel_enabled', 'value' => '0',                         'group' => 'tracking'],
    ['key' => 'pixel_id',      'value' => env('META_PIXEL_ID', ''),    'group' => 'tracking'],
    // Google Ads
    ['key' => 'gads_enabled',  'value' => '0',                         'group' => 'tracking'],
    ['key' => 'gads_id',       'value' => env('GOOGLE_ADS_ID', ''),    'group' => 'tracking'],
    // Cookie Consent
    ['key' => 'cookie_consent_enabled', 'value' => '1',                'group' => 'tracking'],
    ['key' => 'cookiebot_id',           'value' => '',                 'group' => 'tracking'],
];

foreach ($defaults as $row) {
    Setting::firstOrCreate(['key' => $row['key']], $row);
}
```

Uruchomienie:
```bash
php artisan db:seed --class=SettingSeeder
```

### 0.4 Filament — strona ustawień śledzenia

```php
// app/Filament/Pages/TrackingSettingsPage.php
class TrackingSettingsPage extends Page
{
    protected static string $view = 'filament.pages.tracking-settings';
    protected static ?string $navigationGroup = 'Settings';
    protected static ?string $navigationIcon  = 'heroicon-o-chart-bar';
    protected static ?string $navigationLabel = 'Tracking & Analytics';
    protected static ?int    $navigationSort  = 10;

    public string $gtm_id        = '';
    public bool   $gtm_enabled   = false;
    public string $ga4_id        = '';
    public bool   $ga4_enabled   = false;
    public string $pixel_id      = '';
    public bool   $pixel_enabled = false;
    public string $gads_id       = '';
    public bool   $gads_enabled  = false;
    public bool   $cookie_consent_enabled = true;
    public string $cookiebot_id  = '';

    public function mount(): void
    {
        $this->gtm_id        = Setting::get('gtm_id', '');
        $this->gtm_enabled   = (bool) Setting::get('gtm_enabled', false);
        $this->ga4_id        = Setting::get('ga4_id', '');
        $this->ga4_enabled   = (bool) Setting::get('ga4_enabled', false);
        $this->pixel_id      = Setting::get('pixel_id', '');
        $this->pixel_enabled = (bool) Setting::get('pixel_enabled', false);
        $this->gads_id       = Setting::get('gads_id', '');
        $this->gads_enabled  = (bool) Setting::get('gads_enabled', false);
        $this->cookie_consent_enabled = (bool) Setting::get('cookie_consent_enabled', true);
        $this->cookiebot_id  = Setting::get('cookiebot_id', '');
    }

    public function save(): void
    {
        Setting::set('gtm_id',        $this->gtm_id,        'tracking');
        Setting::set('gtm_enabled',   $this->gtm_enabled ? '1' : '0', 'tracking');
        Setting::set('ga4_id',        $this->ga4_id,        'tracking');
        Setting::set('ga4_enabled',   $this->ga4_enabled ? '1' : '0', 'tracking');
        Setting::set('pixel_id',      $this->pixel_id,      'tracking');
        Setting::set('pixel_enabled', $this->pixel_enabled ? '1' : '0', 'tracking');
        Setting::set('gads_id',       $this->gads_id,       'tracking');
        Setting::set('gads_enabled',  $this->gads_enabled ? '1' : '0', 'tracking');
        Setting::set('cookie_consent_enabled', $this->cookie_consent_enabled ? '1' : '0', 'tracking');
        Setting::set('cookiebot_id',  $this->cookiebot_id,  'tracking');

        Notification::make()->title('Settings saved')->success()->send();
    }

    protected function getFormSchema(): array
    {
        return [
            Section::make('Google Tag Manager')
                ->schema([
                    Toggle::make('gtm_enabled')->label('Enable GTM'),
                    TextInput::make('gtm_id')->label('GTM Container ID')
                        ->placeholder('GTM-XXXXXXX')
                        ->helperText('From tagmanager.google.com → Container → ID'),
                ]),
            Section::make('Google Analytics 4')
                ->schema([
                    Toggle::make('ga4_enabled')->label('Enable GA4 direct snippet')
                        ->helperText('Use only if NOT loading GA4 through GTM'),
                    TextInput::make('ga4_id')->label('Measurement ID')
                        ->placeholder('G-XXXXXXXXXX'),
                ]),
            Section::make('Meta Pixel')
                ->schema([
                    Toggle::make('pixel_enabled')->label('Enable Meta Pixel'),
                    TextInput::make('pixel_id')->label('Pixel ID')
                        ->placeholder('XXXXXXXXXXXXXXXXX'),
                ]),
            Section::make('Google Ads')
                ->schema([
                    Toggle::make('gads_enabled')->label('Enable Google Ads tags'),
                    TextInput::make('gads_id')->label('Conversion ID')
                        ->placeholder('AW-XXXXXXXXX'),
                ]),
            Section::make('Cookie Consent')
                ->schema([
                    Toggle::make('cookie_consent_enabled')->label('Show cookie consent banner'),
                    TextInput::make('cookiebot_id')->label('Cookiebot Domain Group ID')
                        ->placeholder('xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx')
                        ->helperText('Leave empty to use custom banner instead'),
                ]),
        ];
    }

    protected function getActions(): array
    {
        return [
            Action::make('save')
                ->label('Save settings')
                ->action('save')
                ->icon('heroicon-o-check'),
        ];
    }
}
```

### 0.5 Blade view (Livewire-based, minimal)

```blade
{{-- resources/views/filament/pages/tracking-settings.blade.php --}}
<x-filament-panels::page>
    <form wire:submit.prevent="save">
        {{ $this->form }}
        <div class="mt-6">
            {{ $this->saveAction }}
        </div>
    </form>
</x-filament-panels::page>
```

### 0.6 HandleInertiaRequests — przekazanie ustawień do frontu

```php
// app/Http/Middleware/HandleInertiaRequests.php
public function share(Request $request): array
{
    return array_merge(parent::share($request), [
        // ... istniejące props ...
        'tracking' => [
            'gtm_enabled'            => (bool) Setting::get('gtm_enabled'),
            'gtm_id'                 => Setting::get('gtm_id', ''),
            'ga4_enabled'            => (bool) Setting::get('ga4_enabled'),
            'ga4_id'                 => Setting::get('ga4_id', ''),
            'pixel_enabled'          => (bool) Setting::get('pixel_enabled'),
            'pixel_id'               => Setting::get('pixel_id', ''),
            'gads_enabled'           => (bool) Setting::get('gads_enabled'),
            'gads_id'                => Setting::get('gads_id', ''),
            'cookie_consent_enabled' => (bool) Setting::get('cookie_consent_enabled', true),
            'cookiebot_id'           => Setting::get('cookiebot_id', ''),
        ],
    ]);
}
```

### 0.7 app.blade.php — warunkowe GTM na podstawie DB

```blade
@php $tracking = app(\App\Services\TrackingService::class); @endphp

@if($tracking->gtmEnabled())
<script>/* GTM snippet z $tracking->gtmId() */</script>
@endif
```

Lub prościej — GTM snippet czyta `tracking.gtm_id` z Inertia shared props w React i sam zarządza ładowaniem przez `dataLayer`. Patrz krok 1.3.

---

## Krok 1 — Google Tag Manager (GTM)

### 1.1 Zmienne środowiskowe (fallback dla seedera)

```dotenv
# .env — używane tylko jako wartość domyślna przy seederze
GTM_ID=GTM-XXXXXXX
```

Docelowa wartość runtime pochodzi z tabeli `settings` (krok 0).

### 1.2 config/services.php

```php
'gtm' => [
    'id' => env('GTM_ID'),   // fallback gdy brak DB
],
```

### 1.3 Blade — app.blade.php

GTM ładuje się gdy `gtm_enabled = true` i `gtm_id` jest ustawione. Wartość czytana jest z `Setting::get()` (cache):

```html
@php
    $gtmId = \App\Models\Setting::get('gtm_enabled') && \App\Models\Setting::get('gtm_id')
        ? \App\Models\Setting::get('gtm_id')
        : null;
@endphp

@if($gtmId)
{{-- Google Tag Manager --}}
<script>(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':
new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],
j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src=
'https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);
})(window,document,'script','dataLayer','{{ $gtmId }}');</script>
{{-- End Google Tag Manager --}}
@endif
```

Dodać na początku `<body>` przed `@inertia`:

```html
@if($gtmId)
<noscript><iframe src="https://www.googletagmanager.com/ns.html?id={{ $gtmId }}"
height="0" width="0" style="display:none;visibility:hidden"></iframe></noscript>
@endif
```

---

## Krok 2 — Google Analytics 4 (GA4) przez GTM

GA4 jest konfigurowane **wyłącznie w panelu GTM** — bez dodatkowego kodu w aplikacji.

### Kroki w GTM:

1. **Nowy tag** → typ: *Google Analytics: GA4 Configuration*
2. **Measurement ID**: `G-XXXXXXXXXX` (z Google Analytics → Data Streams)
3. **Trigger**: All Pages
4. **Publish** kontenera

### Opcjonalnie — Enhanced Measurement:
- W GA4: Admin → Data Streams → Enhanced Measurement → włączyć scroll, outbound clicks, file downloads

### Weryfikacja:
- GA4 DebugView (Admin → DebugView)
- Chrome extension: Google Analytics Debugger

---

## Krok 3 — Google Ads Remarketing + Conversion Tracking przez GTM

### Kroki w GTM:

#### 3.1 Remarketing tag
1. **Nowy tag** → typ: *Google Ads Remarketing*
2. **Conversion ID**: `AW-XXXXXXXXX`
3. **Trigger**: All Pages

#### 3.2 Conversion tracking (np. formularz kontaktowy)
1. **Nowy tag** → typ: *Google Ads Conversion Tracking*
2. **Conversion ID**: `AW-XXXXXXXXX`
3. **Conversion Label**: z Google Ads → Cele → szczegóły konwersji
4. **Trigger**: Custom Event — `contact_form_submitted`

#### 3.3 Wywołanie eventu z React

W `ContactController.php` (lub React `handleSubmit`) po sukcesie:

```js
// resources/js/Components/Marketing/Contact.jsx — po sukcesie formularza
window.dataLayer = window.dataLayer || [];
window.dataLayer.push({ event: 'contact_form_submitted' });
```

Podobnie dla kalkulatora:
```js
window.dataLayer.push({ event: 'calculator_lead_submitted' });
```

---

## Krok 4 — Meta Pixel (Facebook/Instagram Ads + Analytics)

### 4.1 Zmienne środowiskowe (fallback dla seedera)

```dotenv
# .env — używane tylko jako wartość domyślna przy seederze
META_PIXEL_ID=XXXXXXXXXXXXXXXXX
```

### 4.2 config/services.php

```php
'meta' => [
    'pixel_id' => env('META_PIXEL_ID'),
],
```

### 4.3 Shared props — HandleInertiaRequests.php

Już obsłużone w kroku 0.6 — `tracking.pixel_enabled` i `tracking.pixel_id` dostępne globalnie.

### 4.4 React — inicjalizacja Pixela

```js
// resources/js/Hooks/useMetaPixel.js
import { useEffect } from 'react';
import { usePage } from '@inertiajs/react';

export default function useMetaPixel() {
    const { tracking } = usePage().props;

    useEffect(() => {
        if (!tracking?.pixel_enabled || !tracking?.pixel_id || window.fbq) return;

        !function(f,b,e,v,n,t,s){
            if(f.fbq)return;n=f.fbq=function(){n.callMethod?
            n.callMethod.apply(n,arguments):n.queue.push(arguments)};
            if(!f._fbq)f._fbq=n;n.push=n;n.loaded=!0;n.version='2.0';
            n.queue=[];t=b.createElement(e);t.async=!0;
            t.src=v;s=b.getElementsByTagName(e)[0];
            s.parentNode.insertBefore(t,s)
        }(window,document,'script','https://connect.facebook.net/en_US/fbevents.js');

        window.fbq('init', tracking.pixel_id);
        window.fbq('track', 'PageView');
    }, [tracking?.pixel_id, tracking?.pixel_enabled]);
}
```

### 4.5 Użycie w Welcome.jsx i innych stronach

```jsx
import useMetaPixel from '@/Hooks/useMetaPixel';

export default function Welcome(props) {
    useMetaPixel();
    // ...
}
```

### 4.6 Śledzenie konwersji (formularze)

```js
// Po sukcesie formularza kontaktowego
window.fbq?.('track', 'Lead');

// Po wysłaniu kalkulatora
window.fbq?.('track', 'Lead', { content_name: 'Calculator' });

// Po wysłaniu zapytania o wycenę
window.fbq?.('track', 'SubmitApplication');
```

---

## Krok 5 — Cookie Consent (GDPR) — własny komponent

Przed załadowaniem GTM i Pixela wymagana jest zgoda użytkownika (RODO / UK GDPR).
Implementacja: **własny komponent React** bez zewnętrznych SaaS.

### Wymagania prawne (RODO)

- Granularny wybór kategorii — użytkownik musi móc zaakceptować tylko wybrane
- Odmowa musi być równie łatwa jak akceptacja (żadnych preklikowanych checkboxów)
- Możliwość wycofania zgody w dowolnym momencie (link w stopce → ponowne otwarcie banera)
- Zapis daty i wersji polityki przy zgodzie (do localStorage)
- Brak śledzenia analitycznego/marketingowego przed udzieleniem zgody

### Kategorie cookies

| Klucz | Nazwa | Domyślnie | Opis |
|-------|-------|-----------|------|
| `necessary` | Niezbędne | zawsze `true` (zablokowane) | Sesja, CSRF, preferencje językowe |
| `analytics` | Analityczne | `false` | GA4 via GTM |
| `marketing` | Marketingowe | `false` | Meta Pixel, Google Ads |
| `preferences` | Preferencje | `false` | Zapamiętanie ustawień interfejsu |

---

### 5.1 Hook `useConsent`

Stworzyć `resources/js/Hooks/useConsent.js`:

```js
import { useState, useEffect, useCallback } from 'react';

const STORAGE_KEY = 'cookie_consent';
const CONSENT_VERSION = '1.0'; // zmień przy nowej polityce cookies

const defaultConsent = {
    necessary: true,   // zawsze true, nie można wyłączyć
    analytics: false,
    marketing: false,
    preferences: false,
};

function readConsent() {
    try {
        const raw = localStorage.getItem(STORAGE_KEY);
        if (!raw) return null;
        const parsed = JSON.parse(raw);
        // Jeśli zmieniono wersję polityki — traktuj jako brak zgody
        if (parsed.version !== CONSENT_VERSION) return null;
        return parsed;
    } catch {
        return null;
    }
}

function writeConsent(consent) {
    localStorage.setItem(STORAGE_KEY, JSON.stringify({
        ...consent,
        version: CONSENT_VERSION,
        timestamp: new Date().toISOString(),
    }));
}

function pushConsentToGTM(consent) {
    window.dataLayer = window.dataLayer || [];
    window.dataLayer.push({
        event: 'consent_update',
        analytics_storage:   consent.analytics   ? 'granted' : 'denied',
        ad_storage:          consent.marketing   ? 'granted' : 'denied',
        functionality_storage: consent.preferences ? 'granted' : 'denied',
        security_storage:    'granted',
    });
    // GTM Consent Mode v2
    if (typeof window.gtag === 'function') {
        window.gtag('consent', 'update', {
            analytics_storage:    consent.analytics   ? 'granted' : 'denied',
            ad_storage:           consent.marketing   ? 'granted' : 'denied',
            functionality_storage: consent.preferences ? 'granted' : 'denied',
        });
    }
}

export default function useConsent() {
    const [consent, setConsent]     = useState(defaultConsent);
    const [resolved, setResolved]   = useState(false); // czy użytkownik już podjął decyzję
    const [bannerOpen, setBannerOpen] = useState(false);

    useEffect(() => {
        const saved = readConsent();
        if (saved) {
            setConsent(saved);
            setResolved(true);
            pushConsentToGTM(saved);
        } else {
            setBannerOpen(true);
        }
    }, []);

    const acceptAll = useCallback(() => {
        const full = { necessary: true, analytics: true, marketing: true, preferences: true };
        writeConsent(full);
        setConsent(full);
        setResolved(true);
        setBannerOpen(false);
        pushConsentToGTM(full);
    }, []);

    const rejectAll = useCallback(() => {
        const minimal = { ...defaultConsent };
        writeConsent(minimal);
        setConsent(minimal);
        setResolved(true);
        setBannerOpen(false);
        pushConsentToGTM(minimal);
    }, []);

    const saveCustom = useCallback((custom) => {
        const merged = { ...defaultConsent, ...custom, necessary: true };
        writeConsent(merged);
        setConsent(merged);
        setResolved(true);
        setBannerOpen(false);
        pushConsentToGTM(merged);
    }, []);

    const reopenBanner = useCallback(() => setBannerOpen(true), []);

    return { consent, resolved, bannerOpen, acceptAll, rejectAll, saveCustom, reopenBanner };
}
```

---

### 5.2 Komponent `<CookieBanner>`

Stworzyć `resources/js/Components/Marketing/CookieBanner.jsx`:

```jsx
import { useState } from 'react';
import useConsent from '@/Hooks/useConsent';

export default function CookieBanner() {
    const { bannerOpen, acceptAll, rejectAll, saveCustom } = useConsent();
    const [showDetails, setShowDetails] = useState(false);
    const [custom, setCustom] = useState({
        analytics: false,
        marketing: false,
        preferences: false,
    });

    if (!bannerOpen) return null;

    return (
        <div className="fixed bottom-0 left-0 right-0 z-50 bg-white dark:bg-neutral-900 border-t border-neutral-200 dark:border-neutral-700 shadow-2xl p-6">
            <div className="max-w-5xl mx-auto">
                <p className="text-sm text-neutral-700 dark:text-neutral-300 mb-4">
                    Używamy plików cookies, aby poprawić działanie serwisu.
                    Możesz zaakceptować wszystkie lub wybrać kategorie.{' '}
                    <a href="/cookies" className="underline text-red-600">Polityka cookies</a>
                </p>

                {showDetails && (
                    <div className="grid grid-cols-1 sm:grid-cols-3 gap-3 mb-4">
                        {[
                            { key: 'analytics',   label: 'Analityczne',   desc: 'Google Analytics (GA4)' },
                            { key: 'marketing',   label: 'Marketingowe',  desc: 'Meta Pixel, Google Ads' },
                            { key: 'preferences', label: 'Preferencje',   desc: 'Zapamiętanie ustawień' },
                        ].map(({ key, label, desc }) => (
                            <label key={key} className="flex items-start gap-2 cursor-pointer">
                                <input
                                    type="checkbox"
                                    checked={custom[key]}
                                    onChange={e => setCustom(p => ({ ...p, [key]: e.target.checked }))}
                                    className="mt-1 accent-red-600"
                                />
                                <span className="text-sm">
                                    <span className="font-medium text-neutral-800 dark:text-neutral-200">{label}</span>
                                    <br />
                                    <span className="text-neutral-500 dark:text-neutral-400 text-xs">{desc}</span>
                                </span>
                            </label>
                        ))}
                    </div>
                )}

                <div className="flex flex-wrap gap-2">
                    <button onClick={acceptAll}
                        className="px-4 py-2 bg-red-600 hover:bg-red-700 text-white text-sm rounded-md">
                        Akceptuj wszystkie
                    </button>
                    <button onClick={rejectAll}
                        className="px-4 py-2 border border-neutral-300 dark:border-neutral-600 text-sm rounded-md text-neutral-700 dark:text-neutral-300">
                        Tylko niezbędne
                    </button>
                    {showDetails
                        ? <button onClick={() => saveCustom(custom)}
                            className="px-4 py-2 border border-red-600 text-red-600 text-sm rounded-md">
                            Zapisz wybór
                          </button>
                        : <button onClick={() => setShowDetails(true)}
                            className="px-4 py-2 text-sm text-neutral-500 underline">
                            Dostosuj
                          </button>
                    }
                </div>
            </div>
        </div>
    );
}
```

---

### 5.3 Inicjalizacja GTM Consent Mode (domyślne odmowy)

W `app.blade.php`, **przed** skryptem GTM, dodać Consent Initialization:

```html
<!-- Cookie Consent Mode v2 — domyślna odmowa -->
<script>
    window.dataLayer = window.dataLayer || [];
    function gtag(){dataLayer.push(arguments);}

    gtag('consent', 'default', {
        'analytics_storage':    'denied',
        'ad_storage':           'denied',
        'functionality_storage': 'denied',
        'security_storage':     'granted',
        'wait_for_update':      500
    });
</script>
```

GTM i Pixel nie śledzą nic dopóki `useConsent` nie wywoła `gtag('consent', 'update', {...})` po przyjęciu zgody przez użytkownika.

---

### 5.4 Montowanie banera w layoutach

Dodać `<CookieBanner />` do głównego layoutu Inertia — `resources/js/Layouts/MarketingLayout.jsx` (lub `app.jsx`):

```jsx
import CookieBanner from '@/Components/Marketing/CookieBanner';

// W JSX:
<>
    {/* reszta layoutu */}
    <CookieBanner />
</>
```

---

### 5.5 Link "Zarządzaj cookies" w stopce

Stopka powinna zawierać link otwierający baner ponownie. Wymaga udostępnienia `reopenBanner` przez React Context lub prop drilling.

**Rozwiązanie — ConsentContext:**

Stworzyć `resources/js/Contexts/ConsentContext.js`:

```js
import { createContext, useContext } from 'react';

export const ConsentContext = createContext(null);
export const useConsentContext = () => useContext(ConsentContext);
```

W `MarketingLayout.jsx`:

```jsx
import useConsent from '@/Hooks/useConsent';
import { ConsentContext } from '@/Contexts/ConsentContext';
import CookieBanner from '@/Components/Marketing/CookieBanner';

export default function MarketingLayout({ children }) {
    const consent = useConsent();

    return (
        <ConsentContext.Provider value={consent}>
            <Navbar />
            <main>{children}</main>
            <Footer />
            <CookieBanner />
        </ConsentContext.Provider>
    );
}
```

W `Footer.jsx` — przycisk wycofania zgody:

```jsx
import { useConsentContext } from '@/Contexts/ConsentContext';

const { reopenBanner } = useConsentContext();

<button onClick={reopenBanner} className="text-sm text-neutral-500 hover:underline">
    Zarządzaj cookies
</button>
```

---

### 5.6 Warunkowe uruchamianie Meta Pixel i GA4

Hook `useMetaPixel` (Krok 4) musi sprawdzać zgodę przed inicjalizacją:

```js
import { useConsentContext } from '@/Contexts/ConsentContext';

export default function useMetaPixel() {
    const { tracking } = usePage().props;
    const { consent } = useConsentContext();

    useEffect(() => {
        if (!tracking?.pixel_enabled || !tracking?.pixel_id) return;
        if (!consent.marketing) return; // brak zgody marketingowej
        if (window.fbq) return;
        // ... inicjalizacja fbq
    }, [tracking?.pixel_id, consent.marketing]);
}
```

GA4 jest ładowane przez GTM — GTM sam czeka na `gtag('consent', 'update')` z powrotem z `useConsent`.

---

### 5.7 Pliki do stworzenia

| Plik | Opis |
|------|------|
| `resources/js/Hooks/useConsent.js` | Logika zgód, localStorage, GTM push |
| `resources/js/Components/Marketing/CookieBanner.jsx` | UI banera |
| `resources/js/Contexts/ConsentContext.js` | React Context do współdzielenia stanu |

### 5.8 Modyfikacje istniejących plików

| Plik | Zmiana |
|------|--------|
| `resources/views/app.blade.php` | Dodać Consent Default przed GTM snippetem |
| `resources/js/Layouts/MarketingLayout.jsx` | Owrapować w `ConsentContext.Provider`, dodać `<CookieBanner />` |
| `resources/js/Components/Marketing/Footer.jsx` | Dodać przycisk "Zarządzaj cookies" |
| `resources/js/Hooks/useMetaPixel.js` | Sprawdzać `consent.marketing` przed inicjalizacją |

---

## Krok 6 — DataLayer helper (React)

Stworzyć `resources/js/utils/dataLayer.js`:

```js
export function pushEvent(event, params = {}) {
    window.dataLayer = window.dataLayer || [];
    window.dataLayer.push({ event, ...params });
}
```

Użycie w komponentach:
```js
import { pushEvent } from '@/utils/dataLayer';

// Formularz kontaktowy
pushEvent('contact_form_submitted', { form_type: 'contact' });

// Kalkulator
pushEvent('calculator_lead_submitted', { budget: selectedBudget });

// Kliknięcie CTA
pushEvent('cta_click', { location: 'hero', text: ctaLabel });
```

---

## Kolejność implementacji

| # | Zadanie | Priorytet | Czas |
|---|---------|-----------|------|
| 0 | Migracja `settings` + `SettingSeeder` + `TrackingSettingsPage` w Filament | 🔴 Krytyczne | 2 h |
| 1 | Dodać GTM snippet do `app.blade.php` (czyta z DB przez `Setting::get()`) | 🔴 Krytyczne | 30 min |
| 2 | Skonfigurować GA4 w GTM (tag + trigger All Pages) | 🔴 Krytyczne | 20 min |
| 3 | Dodać `useMetaPixel` hook (czyta `tracking.pixel_id` ze shared props) | 🟠 Ważne | 1 h |
| 4 | Dodać `pushEvent` util + wywołania w Contact.jsx i CostCalculator.jsx | 🟠 Ważne | 1 h |
| 5 | Google Ads Remarketing tag w GTM | 🟠 Ważne | 20 min |
| 6 | Google Ads Conversion Tracking dla formularzy | 🟠 Ważne | 30 min |
| 7a | `useConsent` hook + localStorage + GTM Consent Mode v2 default (5.1 + 5.3) | 🟡 Uzupełniające | 2 h |
| 7b | Komponent `<CookieBanner>` (UI, 3 kategorie, Dostosuj) (5.2) | 🟡 Uzupełniające | 1.5 h |
| 7c | `ConsentContext` + integracja w `MarketingLayout` + Footer link (5.4–5.5) | 🟡 Uzupełniające | 1 h |
| 7d | Podpięcie zgody do `useMetaPixel` i GTM (5.6) | 🟡 Uzupełniające | 30 min |
| 8 | Testowanie: GTM Preview, GA4 DebugView, Meta Pixel Helper | 🟡 Uzupełniające | 1 h |

---

## Zmienne .env — podsumowanie

> **Uwaga:** Po wdrożeniu Kroku 0 wartości `.env` służą wyłącznie jako wartości domyślne dla `SettingSeeder`.
> Faktyczna konfiguracja odbywa się z poziomu panelu admina → **Settings → Tracking**.

```dotenv
# ─── Tracking (fallback dla SettingSeeder) ─────────────
GTM_ID=GTM-XXXXXXX
META_PIXEL_ID=XXXXXXXXXXXXXXXXX
GOOGLE_ADS_ID=AW-XXXXXXXXX
COOKIEBOT_ID=XXXXXXXX-XXXX-XXXX-XXXX-XXXXXXXXXXXX
```

---

## Notatki bezpieczeństwa

- Nigdy nie wstrzykiwać ID przez `{!! !!}` bez sanityzacji — używać `{{ }}` (auto-escape) w Blade lub `@js()` w Inertia
- GTM container access: tylko zaufane osoby mają prawo publishować — błędny tag może złamać stronę
- Pixel + GA4 — dane osobowe (IP, email) nie mogą być wysyłane bez zgody użytkownika (UK GDPR)
- Server-side events (Meta CAPI / GA4 Measurement Protocol) jako uzupełnienie — przydatne gdy adblockers blokują klienta
