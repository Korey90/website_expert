# Plan poprawy wydajności – PageSpeed Mobile 68 → 90+

**Data analizy**: 14.04.2026  
**URL**: https://website-expert.uk  
**Wynik wyjściowy**: 68 / 100 (mobile)  
**Cel**: ≥ 90 / 100 (mobile), ≥ 95 (desktop)

---

## Diagnoza – znalezione problemy w kodzie

### 🔴 KRYTYCZNE (każdy problem to ~5–12 pkt)

#### 1. Podwójne ładowanie czcionek (render-blocking)
**Plik**: `resources/css/app.css` linia 1 + `resources/views/app.blade.php` linia ~50

```css
/* app.css – blokuje renderowanie */
@import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Syne:wght@600;700;800&display=swap');
```
```html
<!-- app.blade.php – zbędna, nieużywana czcionka Figtree z bunny.net -->
<link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />
```

**Wpływ**: dwa zewnętrzne DNS lookups + dwie blokujące odpowiedzi CSS przed pierwszym renderem.  
**FCP penalty**: ~400–800ms na mobile 4G.

---

#### 2. Brak `loading="lazy"` na obrazach poniżej foldu
**Pliki**: wszystkie komponenty Marketing (`Portfolio.jsx`, `About.jsx` itd.)

Żaden obraz poniżej hero nie ma atrybutu `loading="lazy"`. Przeglądarka pobiera wszystkie obrazy przy starcie strony.

---

#### 3. Brak `fetchpriority="high"` na LCP
**Plik**: `resources/js/Components/Marketing/Hero.jsx`

Element LCP (tytuł / mockup Hero) nie ma wyznaczonego priorytetu. Przeglądarka musi go odgadnąć.

---

#### 4. `transition-all` na animacji reveal – blokuje GPU compositing
**Plik**: `resources/css/app.css` linia ~62

```css
/* PROBLEM */
.reveal { @apply opacity-0 translate-y-6 transition-all duration-700; }
```

`transition-all` nasłuchuje na WSZYSTKIE właściwości CSS — wymusza pełny layout recalculation przy każdej klatce animacji. Na mobile = jank + obniżone FPS = niskie TBT.

---

### 🟠 POWAŻNE (~3–6 pkt każdy)

#### 5. Wszystkie komponenty Welcome.jsx ładowane eagerly – duży bundle
**Plik**: `resources/js/Pages/Welcome.jsx` linie 1–12

```js
import CostCalculatorV2 from '@/Components/Marketing/CostCalculatorV2';
import Faq              from '@/Components/Marketing/Faq';
import Contact          from '@/Components/Marketing/Contact';
// ... 9 statycznych importów
```

Kalkulator, FAQ, kontakt – to komponenty off-screen. Wchodzą do głównego bundle zamiast się wczytywać gdy potrzebne.

---

#### 6. Preconnect bez preload czcionek
**Plik**: `resources/views/app.blade.php`

Brak `<link rel="preload" as="font">` dla Inter i Syne. Przeglądarka zaczyna pobierać czcionki dopiero po sparsowaniu CSS.

---

#### 7. Dekoracyjne `blur-3xl` w Hero – ciężkie dla GPU na mobile
**Plik**: `resources/js/Components/Marketing/Hero.jsx`

```html
<div className="absolute top-1/4 right-0 w-96 h-96 bg-brand-500/10 rounded-full blur-3xl ..."/>
```

`filter: blur(96px)` na mobile to jeden z najdroższych efektów CSS. Powoduje dropped frames przy scrollu.

---

### 🟡 UMIARKOWANE (~1–3 pkt każdy)

#### 8. Brak kompresji Brotli/Gzip na poziomie serwera
Nginx/Apache musi mieć włączoną kompresję dla JS/CSS/HTML. Bez niej bundle ~150–300 KB zamiast ~50 KB.

#### 9. Brak nagłówków cache dla assetów statycznych
Vite generuje hashe w nazwach plików (np. `app-Abc123.js`) ale bez `Cache-Control: max-age=31536000` przeglądarka może je ignorować.

#### 10. Brak WebP / srcset dla obrazów portfolio
Obrazy w `/public/images/portfolio/` to SVG – ok. Ale przyszłe obrazy uploadowane przez Filament nie mają WebP conversion.

---

## Plan poprawek – priorytety

### FAZA 1 – Quick wins (est. +15–20 pkt, czas: 2–3h)

---

#### P1-A: Usuń zbędną czcionkę Figtree + przenieś Google Fonts na self-hosted

**Krok 1**: Usuń z `app.blade.php`:
```html
<!-- USUNĄĆ -->
<link rel="preconnect" href="https://fonts.bunny.net">
<link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />
```

**Krok 2**: Pobierz Inter i Syne jako pliki WOFF2.  
Narzędzia: https://gwfh.mranftl.com/ (Google Webfonts Helper) lub `fontsource`:
```bash
npm install @fontsource-variable/inter @fontsource/syne
```

**Krok 3**: W `app.css` zastąp `@import url(...)`:
```css
/* ZAMIAST */
@import url('https://fonts.googleapis.com/...');

/* DODAJ */
@import '@fontsource-variable/inter/index.css';
@import '@fontsource/syne/600.css';
@import '@fontsource/syne/700.css';
@import '@fontsource/syne/800.css';
```

**Krok 4**: Dodaj preload w `app.blade.php` dla krytycznych wariantów (Inter 400 i 600, Syne 700):
```html
<link rel="preload" as="font" type="font/woff2"
      href="{{ asset('fonts/inter-latin-woff2.woff2') }}" crossorigin>
```

**Szacowany zysk**: +8–12 pkt (eliminacja render-blocking external CSS)

---

#### P1-B: Napraw `transition-all` na `.reveal`

**Plik**: `resources/css/app.css`

```css
/* PRZED */
.reveal { @apply opacity-0 translate-y-6 transition-all duration-700; }

/* PO */
.reveal {
  opacity: 0;
  transform: translateY(1.5rem);
  transition: opacity 700ms ease, transform 700ms ease;
  will-change: opacity, transform;
}
.reveal.visible {
  opacity: 1;
  transform: translateY(0);
}
```

`will-change: opacity, transform` promuje element na osobną warstwę GPU — eliminuje layout recalculation.

**Szacowany zysk**: +3–5 pkt (TBT, CLS reduction)

---

#### P1-C: Dodaj `loading="lazy"` na wszystkich obrazach off-screen

Przeszukaj i dodaj `loading="lazy"` do:
- `Portfolio.jsx` — `<img>` w kartach
- `About.jsx` — zdjęcie zespołu/biura
- `Services/Index.jsx` — jeśli obrazy
- `Portfolio/Index.jsx` — wszystkie karty

```jsx
/* PRZED */
<img src={p.image} alt={itemTitle} className="w-full h-full object-cover" />

/* PO */
<img src={p.image} alt={itemTitle} className="w-full h-full object-cover" loading="lazy" decoding="async" />
```

**Szacowany zysk**: +5–8 pkt (LCP, pobieranie obrazów odroczone)

---

### FAZA 2 – Optymalizacja JS Bundle (est. +5–8 pkt, czas: 1–2h)

---

#### P2-A: Lazy loading komponentów below-fold w Welcome.jsx

```jsx
// PRZED
import CostCalculatorV2 from '@/Components/Marketing/CostCalculatorV2';
import Faq              from '@/Components/Marketing/Faq';
import Contact          from '@/Components/Marketing/Contact';
import Testimonials     from '@/Components/Marketing/Testimonials';

// PO
import { lazy, Suspense } from 'react';
const CostCalculatorV2 = lazy(() => import('@/Components/Marketing/CostCalculatorV2'));
const Faq              = lazy(() => import('@/Components/Marketing/Faq'));
const Contact          = lazy(() => import('@/Components/Marketing/Contact'));
const Testimonials     = lazy(() => import('@/Components/Marketing/Testimonials'));
```

Opakuj sekcje below-fold w `<Suspense fallback={null}>`:
```jsx
<Suspense fallback={null}>
  <CostCalculatorV2 data={cost_calculator_v2} ... />
</Suspense>
```

**Zachowaj jako eager** (above fold): `Hero`, `About`, `TrustStrip`, `Services` (pierwsze 3)

**Szacowany zysk**: +4–6 pkt (TTI, TBT — mniejszy initial JS parse)

---

#### P2-B: Usuń blur-3xl na mobile

**Plik**: `resources/js/Components/Marketing/Hero.jsx`

```jsx
/* PRZED */
<div className="absolute top-1/4 right-0 w-96 h-96 bg-brand-500/10 rounded-full blur-3xl pointer-events-none" />

/* PO — ukryj na mobile, zostaw na md+ */
<div className="hidden md:block absolute top-1/4 right-0 w-96 h-96 bg-brand-500/10 rounded-full blur-3xl pointer-events-none" />
```

**Szacowany zysk**: +2–4 pkt (FPS podczas scrollu na mobile)

---

### FAZA 3 – Infrastruktura (est. +3–6 pkt, czas: 30min w Nginx)

---

#### P3-A: Kompresja Brotli/Gzip w Nginx

```nginx
# /etc/nginx/sites-available/website-expert.uk
gzip on;
gzip_vary on;
gzip_min_length 1024;
gzip_types text/html text/css application/javascript application/json image/svg+xml;

brotli on;
brotli_comp_level 6;
brotli_types text/html text/css application/javascript application/json image/svg+xml;
```

**Szacowany zysk**: +3–5 pkt (wielkość transferu JS/CSS -60–80%)

---

#### P3-B: Cache-Control dla assetów Vite

```nginx
# Assety z hashem w nazwie – cache 1 rok
location ~* \.(js|css|woff2|woff|ttf)$ {
    expires 1y;
    add_header Cache-Control "public, immutable";
}

# Obrazy
location ~* \.(webp|png|jpg|jpeg|svg|ico|gif)$ {
    expires 6M;
    add_header Cache-Control "public";
}
```

---

### FAZA 4 – Dodatkowe (est. +2–4 pkt)

---

#### P4-A: `fetchpriority="high"` na LCP

W `Hero.jsx` – jeśli istnieje obraz hero:
```jsx
<img
  src={heroImage}
  fetchpriority="high"
  loading="eager"
  decoding="sync"
  alt={title}
/>
```

Dla tekstowego LCP (h1 w Hero) dodaj w `app.blade.php`:
```html
<link rel="preload" as="style" href="...app.css">
```
Vite robi to automatycznie z `modulepreload` — upewnij się że `@vite` tag jest przed `</head>`.

---

#### P4-B: Defer GTM do po LCP

```html
<!-- PRZED – GTM ładuje się natychmiast -->
<script>(function(w,d,s,l,i){...j.async=true;...})()</script>

<!-- PO – GTM ładuje się po `load` event -->
<script>
window.addEventListener('load', function() {
    (function(w,d,s,l,i){w[l]=w[l]||[];...j.async=true;...})(window,document,'script','dataLayer','{{ $gtmId }}');
});
</script>
```

**Szacowany zysk**: +1–2 pkt (blokuję TBT przez third-party scripts)

---

#### P4-C: WebP dla uploadowanych obrazów portfolio

W `PortfolioProjectService` lub przez Intervention Image:
```bash
composer require intervention/image
```

Konwertuj podczas upload w Filament:
```php
// W PortfolioProjectResource FileUpload
->saveUploadedFileUsing(function ($file) {
    $img = Image::read($file)->toWebp(85);
    $path = 'portfolio/' . Str::uuid() . '.webp';
    Storage::disk('public')->put($path, $img->toString());
    return $path;
})
```

---

## Prognozowany efekt

| Faza | Działanie | Est. zysk |
|------|-----------|-----------|
| P1-A | Self-hosted fonts | +8–12 pkt |
| P1-B | Naprawa transition-all | +3–5 pkt |
| P1-C | loading="lazy" na obrazach | +5–8 pkt |
| P2-A | React.lazy dla below-fold | +4–6 pkt |
| P2-B | Brak blur-3xl na mobile | +2–4 pkt |
| P3-A/B | Nginx gzip/brotli + cache | +3–5 pkt |
| P4    | fetchpriority + GTM defer + WebP | +2–4 pkt |
| **Suma** | | **+27–44 pkt** |

**Prognoza**: 68 + 27 = **~95 pkt (pesymistycznie 85, optymistycznie 97)**

---

## Kolejność implementacji (rekomendowana)

```
1. P1-A (fonty self-hosted)       ← największy zysk, 1h
2. P1-B (transition-all fix)      ← 10 min
3. P1-C (lazy images)             ← 30 min
4. P3-A/B (nginx)                 ← 30 min na serwerze
5. P2-A (React.lazy)              ← 1h
6. P2-B (blur-3xl mobile)         ← 5 min
7. P4   (GTM defer + fetchpriority) ← 30 min
```

Po każdej fazie: odśwież PageSpeed https://pagespeed.web.dev i porównaj wynik.

---

## Narzędzia do weryfikacji

- **PageSpeed**: https://pagespeed.web.dev
- **WebPageTest**: https://webpagetest.org (waterfall chart)
- **Bundle analyzer**: `npx vite-bundle-visualizer` w projekcie
- **Font audit**: Chrome DevTools → Network → filter: Font
- **LCP debug**: Chrome DevTools → Performance → Core Web Vitals
