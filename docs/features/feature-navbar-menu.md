# Feature: Navbar Menu Module
> Data: 2026-04-21
> Status: Draft

---

## 1. Definicja

**Cel:** Zastąpić nawigację przechowywaną jako `SiteSection.extra` dedykowanym, relacyjnym modułem z własnym zasobem Filament. Menu musi rozpoznawać, które sekcje strony są aktualnie widoczne, i wyróżniać aktywny link.

**Bounded context:** Marketing → strona główna. Niezależny od multi-tenancy (jedna globalna nawigacja serwisu).

**Rola:** Administrator edytuje pozycje menu w Filamencie. Gość widzi menu dopasowane do swojego języka z podświetleniem aktywnej sekcji.

**Zależności:**
- `SiteSection` — klucze sekcji (`#about`, `#services`, …) służą jako kotwice do wykrywania widoczności
- `HandleInertiaRequests` — global share danych nawigacji
- `Navbar.jsx` — komponent frontendowy (bez zmian wizualnych)

---

## 2. Stan obecny i delta

### Stan obecny
| Element | Gdzie | Problem |
|---|---|---|
| Dane nawigacji | `site_sections.extra` (klucz `navbar`) | Filament nadpisuje cały JSON `extra` tylko widocznymi polami → links znikają |
| Edycja linków | `SiteSectionResource` Repeater | `->defaultItems(5)` inicjalizuje puste rekordy, brak izolacji od innych kluczy w `extra` |
| Aktywna sekcja | brak | Navbar nie rozpoznaje widocznych sekcji |
| Fallback | `LINK_DEFAULTS` w `Navbar.jsx` | Twardy fallback hardcoded w JS |

### Delta (nowa praca)
- Nowa tabela `nav_items` (relacyjna, nie JSON blob)
- Model `NavItem` + resource `NavItemResource` w Filamencie
- Global share `nav_items` w `HandleInertiaRequests` (dostępne na każdej stronie)
- Intersection Observer w `Navbar.jsx` — wykrywa widoczną sekcję i wyróżnia link
- Usunięcie sekcji `navbar` z `SiteSection` (lub zostawienie jako legacy, wyłączenie z nawigacji)
- Usunięcie `LINK_DEFAULTS` z Navbar.jsx

---

## 3. Model danych

### Tabela `nav_items`

```
nav_items
├── id                bigint unsigned PK
├── label             json  — {"pl":"O nas","en":"About Us","pt":"Sobre Nós"}  (HasTranslations)
├── href              varchar(200)    — "#about" | "/portfolio" | "https://..."
├── section_key       varchar(100) nullable — "about" | "services" | null (linki zewnętrzne)
├── sort_order        unsignedSmallInt default 0
├── is_active         boolean default true
├── open_in_new_tab   boolean default false
├── timestamps
```

**NavItem model:**
- `HasTranslations` → pole `label`
- `scopeActive()` → `where('is_active', true)->orderBy('sort_order')`
- Brak `business_id` — globalna nawigacja serwisu

### Brak zmian w istniejących tabelach

`site_sections` pozostaje bez modyfikacji. Rekord `navbar` może zostać w DB jako archiwum danych CTA (brand_name, cta_text, cta_href) — te pola zostają w `SiteSectionResource` i nadal trafiają do `Navbar.jsx` przez `WelcomeController`. Tylko `links` jest przeniesione.

---

## 4. Backend

### Migration
```
php artisan make:migration create_nav_items_table
```

### Model
```
app/Models/NavItem.php
- HasTranslations, translatable = ['label']
- casts: is_active => boolean, open_in_new_tab => boolean
- scopeActive, scopeOrdered
```

### Filament Resource
```
app/Filament/Resources/NavItemResource.php
```
- Group: `Marketing`, icon `heroicon-o-bars-3`, label `Navigation Menu`, sort 0
- **Table**: sort_order (reorderable drag), label (locale aktualnego admina), href, section_key badge, is_active toggle, open_in_new_tab
- **Form** (inline w tabeli lub modal):
  - `sort_order` hidden (zarządza reorder)
  - Tabs językowe → `label.pl` / `label.en` / `label.pt`
  - `href` TextInput
  - `section_key` TextInput (helper: klucz sekcji, np. `about` → kotwica `#about`)
  - `is_active` Toggle
  - `open_in_new_tab` Toggle
- **Reorder**: `->reorderable('sort_order')` na tabeli
- Brak osobnej strony Edit — wystarczy `->actions([EditAction, DeleteAction])` w wierszu

### Global share — HandleInertiaRequests
```php
// share():
'nav_items' => Cache::remember('nav_items', 60, fn () =>
    NavItem::active()->ordered()->get()->map(fn ($item) => [
        'href'            => $item->href,
        'label'           => $item->getTranslations('label'),
        'section_key'     => $item->section_key,
        'open_in_new_tab' => $item->open_in_new_tab,
    ])->values()->all()
),
```
Cache z `Cache::tags(['nav'])` lub zwykły TTL 60 s — czyści się przy `NavItem::saved/deleted` Observerem albo po prostu TTL wystarczy w MVP.

### WelcomeController
`navbar` prop pozostaje (brand_name, cta_text, cta_href). Pole `extra.links` jest ignorowane przez `Navbar.jsx` jeśli `nav_items` jest dostępne.

---

## 5. Frontend

### Navbar.jsx — zmiany

**Źródło linków:**
```js
// BYŁO
const rawLinks = Array.isArray(extra.links) && extra.links.length > 0 ? extra.links : LINK_DEFAULTS;

// BĘDZIE
const { nav_items } = page.props;          // global share
const rawLinks = nav_items ?? [];
```

**Usunąć:** `LINK_DEFAULTS`, oba `console.log`.

**Aktywna sekcja — Intersection Observer:**
```js
const [activeSection, setActiveSection] = useState(null);

useEffect(() => {
    if (!isHome) return;                    // aktywność tylko na stronie głównej
    const sectionKeys = rawLinks
        .map(l => l.section_key)
        .filter(Boolean);

    const observers = [];
    sectionKeys.forEach(key => {
        const el = document.getElementById(key);
        if (!el) return;
        const obs = new IntersectionObserver(
            ([entry]) => { if (entry.isIntersecting) setActiveSection(key); },
            { threshold: 0.4 }
        );
        obs.observe(el);
        observers.push(obs);
    });
    return () => observers.forEach(o => o.disconnect());
}, [isHome, rawLinks]);
```

**Mapowanie linków:**
```js
const navLinks = rawLinks.map(l => ({
    href:        resolveHref(l.href),
    label:       l.label[locale] ?? l.label['en'] ?? l.href,
    sectionKey:  l.section_key ?? null,
    newTab:      l.open_in_new_tab ?? false,
    isActive:    l.section_key === activeSection,
}));
```

**Render linku (desktop i mobile):**
```jsx
<a
    href={l.href}
    target={l.newTab ? '_blank' : undefined}
    rel={l.newTab ? 'noopener noreferrer' : undefined}
    className={`hover:text-brand-500 transition-colors ${
        l.isActive ? 'text-brand-500 font-semibold' : ''
    }`}
>
    {l.label}
</a>
```

**Wizualnie:** zero zmian — te same klasy Tailwind, ta sama struktura HTML.

### Sekcje strony

Każda sekcja `<section>` musi mieć `id` równy `section_key`, np.:
```html
<section id="about" …>
```
Większość sekcji już to ma (kotwice `#about`, `#services` itp.). Do zweryfikowania przy implementacji.

---

## 6. Workflow

### Happy path — admin edytuje menu
1. Admin otwiera `Marketing → Navigation Menu`
2. Widzi listę pozycji z drag & drop po sort_order
3. Klika `Edit` na pozycji → modal z polami `label` (3 języki), `href`, `section_key`, toggle `is_active`
4. Zapisuje → rekord aktualizowany, cache TTL wygasa w ciągu ≤60 s
5. Na froncie gość odświeża stronę → widzi nowe menu

### Happy path — dodanie nowej pozycji
1. Admin klika `Add item`
2. Wpisuje label (PL + EN + PT), href, section_key
3. Sort_order ustawia drag & drop po dodaniu
4. Zapisuje → nowy `NavItem` w DB

### Edge cases
| Sytuacja | Zachowanie |
|---|---|
| `nav_items` puste (brak rekordów w DB) | Navbar.jsx renderuje puste menu — brak fallbacku; admin widzi komunikat w Filamencie "No items yet" |
| `section_key` ustawiony ale sekcja nie istnieje w DOM | Observer nic nie znajdzie, `activeSection` nie zmienia się dla tego klucza — bezpieczne |
| Link zewnętrzny (href: `https://…`) | `section_key = null`, `open_in_new_tab = true`, Intersection Observer go pomija |
| Strona inna niż `/` | `isHome = false` → Observer nie startuje, żaden link nie jest "aktywny" |
| Zmiana języka | `locale` zmienia się, `l.label[locale]` zwraca tłumaczenie bez przeładowania komponentu |

---

## 7. Test plan

### Backend
- [ ] Migration tworzy tabelę z poprawnymi typami
- [ ] `NavItem::active()->ordered()` zwraca tylko `is_active=true` w kolejności sort_order
- [ ] `getTranslation('label', 'pl')` zwraca polskie tłumaczenie
- [ ] Global share `nav_items` zawiera klucz w każdym żądaniu Inertia
- [ ] Zapis w Filamencie nie niszczy innych danych (brak `extra` blob)

### Frontend
- [ ] Linki renderują się z poprawnym `label` dla locale PL / EN / PT
- [ ] Po przescrollowaniu do sekcji `#about` → link "O nas" ma klasę `text-brand-500`
- [ ] Link zewnętrzny otwiera się w nowej karcie
- [ ] Na stronie `/portfolio` żaden link nie jest wyróżniony (isHome = false)
- [ ] Usunięcie pozycji w Filamencie → po ≤60 s znika z menu

---

## 8. Checklist implementacji

### Backend
- [ ] `create_nav_items_table` migration
- [ ] `NavItem` model (HasTranslations, scopes)
- [ ] `NavItemResource` + Pages (List z reorder, modal Edit)
- [ ] Global share `nav_items` w `HandleInertiaRequests`
- [ ] Seeder `NavItemSeeder` z 5 domyślnymi pozycjami (About, Services, Portfolio, Calculator, Contact)
- [ ] Zarejestrować seeder w `DatabaseSeeder`

### Frontend
- [ ] Usunąć `LINK_DEFAULTS` z `Navbar.jsx`
- [ ] Podmienić źródło linków na `page.props.nav_items`
- [ ] Dodać Intersection Observer (tylko `isHome`)
- [ ] Dodać `isActive` klasę do linków desktop i mobile
- [ ] Dodać `target/_blank` + `rel` dla `open_in_new_tab`
- [ ] Usunąć `console.log`

### Weryfikacja
- [ ] `php artisan migrate`
- [ ] `php artisan db:seed --class=NavItemSeeder`
- [ ] Filament: dodaj/usuń pozycję → sprawdź front
- [ ] Sprawdź aktywną sekcję przy scrollu na `/`
- [ ] Sprawdź brak aktywności na `/portfolio`
