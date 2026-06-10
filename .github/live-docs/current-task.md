# Current Task

**Status:** Ukończone ✅ — 2026-06-09

**Task:** ServicePage Block Builder — moduł dedykowanych stron usługowych z panelem admina

**Last Updated:** 2026-06-09

---

## Cel

Nowy moduł CMS do budowania pełnych stron usługowych (`/seo`, `/hosting`, `/web-design` itp.) z poziomu panelu Filament. Każda strona składa się z kolejkowanych bloków wizualnych. Systemowy (bez `business_id`).

---

## Architektura — 2 tabele

### `service_pages`
| Pole | Typ | Opis |
|---|---|---|
| `id` | bigint PK | |
| `slug` | string(100) unique | URL path, np. `seo`, `hosting` |
| `title` | json (translatable) | Tytuł (EN/PL/PT) |
| `meta_title` | json (translatable) | SEO |
| `meta_description` | json (translatable) | SEO |
| `is_published` | boolean | Widoczność publiczna |
| `show_in_nav` | boolean | Czy pokazywać w nawigacji |
| `nav_label` | json (translatable) | Etykieta w nawigacji |
| `sort_order` | smallint | Kolejność w nav |
| `timestamps` | | |

### `service_page_blocks`
| Pole | Typ | Opis |
|---|---|---|
| `id` | bigint PK | |
| `service_page_id` | FK → service_pages | |
| `type` | string(50) | Typ bloku (enum 8 typów) |
| `sort_order` | smallint | Kolejność bloków na stronie |
| `content` | json | Treść locale-keyed (`{en: {...}, pl: {...}, pt: {...}}`) |
| `settings` | json | Opcje layoutu (bg_color, columns, itp.) |
| `is_active` | boolean | Włącz/wyłącz blok |
| `timestamps` | | |

---

## 8 typów bloków

| Typ | Co zawiera |
|---|---|
| `hero` | Nagłówek, opis, badge, przycisk CTA, tło (kolor/obraz) |
| `features_grid` | Tytuł + siatka kart (ikona, tytuł, opis), 2/3/4 kolumny |
| `packages` | Karty pakietów (nazwa, cena, lista features, badge "Popular", CTA) |
| `pricing_table` | Tabela cennikowa statyczna z wierszami |
| `faq` | Tytuł + lista pytanie → odpowiedź (accordion) |
| `cta_banner` | Wezwanie do działania — tekst + przycisk + tło |
| `text_section` | Rich-text (pełna szerokość lub 2-kolumnowy) |
| `comparison_table` | Tabela porównawcza (checkmarki, ceny per kolumna) |

---

## Pliki do stworzenia

### Faza 1 — Backend + Filament

1. `database/migrations/XXXX_create_service_pages_table.php`
2. `database/migrations/XXXX_create_service_page_blocks_table.php`
3. `app/Models/ServicePage.php` — Spatie Translatable, bez BelongsToTenant
4. `app/Models/ServicePageBlock.php` — Spatie Translatable
5. `app/Http/Controllers/ServicePageController.php` — resolve locale, fetch page+blocks, Inertia
6. `app/Filament/Resources/ServicePageResource.php` — group Marketing, Repeater bloków
7. `app/Filament/Resources/ServicePageResource/Pages/*.php`
8. Route: `/services/{slug}` — sprawdza najpierw ServicePage, fallback ServiceItem (stary)

### Faza 2 — Frontend React

9. `resources/js/Pages/Services/ServicePage.tsx` — generic block renderer
10. Komponenty bloków: `HeroBlock`, `FeaturesGridBlock`, `PackagesBlock`, `PricingTableBlock`, `FaqBlock`, `CtaBannerBlock`, `TextSectionBlock`, `ComparisonTableBlock`

---

## Co NIE ulega zmianie

- `/domains` — zahardkodowany (logika funkcjonalna: price checker, zamówienia)
- `ServiceItem` + stara `Services/Show.jsx` — zostaje jako fallback
- `SiteSection` — bez zmian

---

## Decyzje

- **Route:** `/{slug}` — catch-all na samym końcu web.php, najniższy priorytet
- **Fazy:** obie naraz

---

## Status etapów

- [ ] Faza 1: Backend + Filament
- [ ] Faza 2: Frontend React
- [ ] Testy
- [ ] Tłumaczenia

---

**Last completed:** Job Manager — zarządzanie Laravel Jobs (failed, pending, batches) z poziomu panelu admina Filament — 2026-06-09