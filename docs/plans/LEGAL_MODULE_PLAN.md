# Legal Zone Module — Plan Działania

**Data:** 24 marca 2026  
**Zakres:** Strefa prawna — 4 dokumenty w CMS Pages  
**Rynki:** Wielka Brytania / Irlandia Północna + Polska  
**Języki:** EN / PL / PT (pełna treść we wszystkich trzech — PT istotne ze względu na dużą społeczność portugalskojęzyczną w UK)

---

## 1. Diagnoza — stan aktualny

### Co już istnieje (dobrze)
- Model `Page` z translatability (Spatie) — EN / PL / PT ✅
- Seeder z treścią wszystkich 4 dokumentów (szkielety)
- `PageResource` w Filament z TinyEditor
- Routing slug-based (`/privacy-policy`, `/terms-and-conditions`, `/cookies`, `/accessibility`)
- `CmsPage.jsx` — komponent React z Inertia
- Model `Setting` (klucz-wartość z grupami + cache) — używany już przez `TrackingSettingsPage`
- Wzorzec `TrackingSettingsPage` / `IntegrationSettingsPage` w Filament — gotowy do powielenia

### Co wymaga poprawy / uzupełnienia

| Obszar | Problem |
|--------|---------|
| **Treść prawna** | Obecna treść jest szkieletem — brakuje wielu obowiązkowych klauzul |
| **UK/NI compliance** | Brakuje: prawa do reklamacji CRA 2015, UK GDPR art. 13/14 checklisty, PECR 2003, Accessibility Regs 2018 |
| **PL compliance** | Brakuje: RODO art. 13/14, Prawo telekomunikacyjne, Kodeks cywilny, UODO/UOKIK |
| **PT compliance** | Brakuje pełnej treści po portugalsku — dotychczas tylko szkielet |
| **Dane faktyczne** | Brakuje: numeru rejestracyjnego firmy, adresu, VAT, numeru ICO — zaszyfrowane na stałe w seederze |
| **UI dokumentu** | Brak: spisu treści, daty wejścia w życie, wersji, przycisku drukowania |
| **CMS — brak pól** | Brak: `effective_date`, `version` — potrzebne do dokumentów prawnych |
| **Brak panelu danych firmy** | Dane firmowe (adres, VAT, ICO nr) są hardcode'owane zamiast być zarządzane przez panel |
| **Accessibility Statement** | Wymaga statusu zgodności z WCAG, listy znanych problemów, procedury egzekucji |

---

## 2. Zakres prac

### 2.1 Baza danych — nowa migracja

**Plik:** `database/migrations/YYYY_MM_DD_add_legal_fields_to_pages_table.php`

Dodamy 2 pola do tabeli `pages`:

```sql
ALTER TABLE pages
  ADD COLUMN effective_date DATE NULL,      -- Data wejścia w życie dokumentu
  ADD COLUMN version VARCHAR(20) NULL;       -- Wersja dokumentu, np. "1.2", "2025-01"
```

**Dlaczego:**  
Dokumenty prawne muszą mieć jawną datę wejścia w życie (wymagane przez UK GDPR art. 12 — przejrzystość). Wersjonowanie umożliwia śledzenie zmian historycznych bez edytowania bieżącej treści.

---

### 2.2 Model Page.php

Dodamy `effective_date` i `version` do `$fillable` oraz castów.

---

### 2.3 LegalSettingsPage — nowa strona ustawień w Filament (KLUCZOWA ZMIANA)

**Plik:** `app/Filament/Pages/LegalSettingsPage.php`

Nowa strona ustawień wzorowana na istniejącym `TrackingSettingsPage` (ten sam wzorzec: `Setting::get` / `Setting::set`, grupy kluczy, cache). Wszystkie dane firmowe będą przechowywane w tabeli `settings` z grupą `legal`.

#### Pola formularza — sekcje:

**Sekcja: Company Details** (używane we wszystkich dokumentach)
```
legal.company_name        → "WebsiteExpert Ltd"           (TextInput, required)
legal.company_number      → "[Companies House No.]"        (TextInput)
legal.company_address     → "[Registered address]"         (Textarea, 3 lines)
legal.vat_number          → "GB [numer VAT]"               (TextInput)
legal.company_phone       → "+44 ..."                      (TextInput)
legal.company_email       → "hello@websiteexpert.co.uk"    (TextInput)
```

**Sekcja: Data Protection** (Privacy Policy)
```
legal.ico_number          → "ZB [ICO Registration No.]"   (TextInput)
legal.ico_registration_url → "https://ico.org.uk/..."     (TextInput, URL)
legal.privacy_email       → "privacy@websiteexpert.co.uk" (TextInput, email)
legal.dpo_name            → "[DPO name, if applicable]"   (TextInput, nullable)
legal.data_retention_years → "7"                          (TextInput, number)
```

**Sekcja: Customer Service & Complaints** (T&C)
```
legal.complaints_email    → "support@websiteexpert.co.uk" (TextInput, email)
legal.complaints_phone    → "+44 ..."                     (TextInput)
legal.response_days       → "14"                          (TextInput, number)
legal.deposit_percent     → "50"                          (TextInput, number, %)
legal.payment_terms_days  → "30"                          (TextInput, number)
```

**Sekcja: Tracking & Cookies** (Cookie Policy — pobierane z istniejących ustawień!)
```
→ Wyświetla (readonly): GTM ID, GA4 ID, Pixel ID z grupy 'tracking'
   (nie duplikujemy danych — linkujemy do TrackingSettingsPage)
legal.cookie_policy_email → "privacy@websiteexpert.co.uk" (TextInput)
```

**Sekcja: Document Dates** (wszystkie dokumenty)
```
legal.privacy_effective_date   → DatePicker
legal.privacy_version          → "1.0"
legal.terms_effective_date     → DatePicker
legal.terms_version            → "1.0"
legal.cookies_effective_date   → DatePicker
legal.cookies_version          → "1.0"
legal.accessibility_effective_date → DatePicker
legal.accessibility_version        → "1.0"
```

**Dlaczego to podejście:**
- Istniejący `Setting` model (`key`, `value`, `group`, cache TTL 1d) jest idealny dla tych danych
- Jedna strona ustawień = jedno miejsce, które uzupełniasz raz i wszystkie dokumenty się aktualizują
- Nie trzeba edytować 12 wersji językowych dokumentu gdy zmienia się adres firmy
- Ten sam wzorzec co `TrackingSettingsPage` — minimalna ilość nowego kodu

---

### 2.4 System zmiennych w treści dokumentów

Treść dokumentów w seederze (i zatem w TinyEditor) będzie używać **tokenów** zamiast hardcode'owanych danych:

```
{{legal.company_name}}       → WebsiteExpert Ltd
{{legal.company_number}}     → 12345678
{{legal.company_address}}    → 123 High Street, London, W1A 1AA
{{legal.vat_number}}         → GB 123 456 789
{{legal.ico_number}}         → ZB1234567
{{legal.privacy_email}}      → privacy@websiteexpert.co.uk
{{legal.complaints_email}}   → support@websiteexpert.co.uk
{{legal.deposit_percent}}    → 50
{{legal.payment_terms_days}} → 30
{{legal.data_retention_years}} → 7
{{legal.privacy_effective_date}} → 1 March 2026
{{legal.privacy_version}}    → 1.0
... itd.
```

**Gdzie zamiana tokenów nastąpi:**  
W `PageController::show()` — po pobraniu przetłumaczonej treści, przed przekazaniem do Inertia:

```php
$content = $this->replaceLegalVars($page->content);

private function replaceLegalVars(string $content): string
{
    $vars = Setting::whereGroup('legal')->pluck('value', 'key');
    foreach ($vars as $key => $value) {
        $content = str_replace('{{'.$key.'}}', e($value), $content);
    }
    return $content;
}
```

**Zalety:**
- Zmienisz adres firmy raz w panelu → wszystkie 3 języki × 4 dokumenty zaktualizowane automatycznie
- Tokeny są czytelne w TinyEditor — redaktor wie, że `{{legal.company_name}}` zostanie zastąpione
- Bezpieczne — `e()` escapuje wartości przed wstawieniem do HTML

---

### 2.5 PageResource.php — Filament Admin

Dodamy sekcję **"Document Metadata"** w formularzu (widoczna gdy `type` ≠ `page`):
- `effective_date` — DatePicker, label "Effective Date / Data wejścia w życie"
- `version` — TextInput, label "Version", placeholder "e.g. 1.0"

> **Uwaga:** `effective_date` i `version` PER DOKUMENT są zarządzane przez `LegalSettingsPage` centralnie. Pola w `PageResource` służą jako override dla konkretnej strony (advanced use).

W tabeli: dodamy kolumnę `effective_date` widoczną dla typów policy/terms/cookie_policy.

---

### 2.6 PageController.php

Dwie zmiany:
1. Przekazać `effective_date` i `version` z modelu `Page` do frontendu
2. Wywołać `replaceLegalVars()` na treści po tłumaczeniu

---

### 2.7 CmsPage.jsx — redesign UI

Obecny komponent to prosta strona z nagłówkiem i blokiem prozy. Zamienimy go na **profesjonalny layout dokumentu prawnego**.

#### Nowy layout:
```
┌─────────────────────────────────────────────────────────┐
│ Breadcrumb: Home › Legal                                 │
├─────────────────────────────────────────────────────────┤
│ [DOCUMENT HEADER]                                        │
│   H1: Tytuł dokumentu                                    │
│   Effective date: DD MMMM YYYY  │  Version: 1.0          │
│   Last updated: DD MMMM YYYY    │  [Print] [↑ Top]       │
├──────────────────┬──────────────────────────────────────┤
│  [TOC SIDEBAR]   │  [DOCUMENT CONTENT]                   │
│  1. Sekcja 1    │  <h2 id="s1">Sekcja 1</h2>            │
│  2. Sekcja 2    │  <p>...</p>                            │
│  3. ...         │  <h2 id="s2">Sekcja 2</h2>            │
│  (sticky)       │  ...                                   │
│                  │                                        │
└──────────────────┴──────────────────────────────────────┘
```

**Funkcje:**
1. **Auto-TOC** — wyekstrahowany z tagów `<h2>` w treści HTML (DOMParser lub regex)
2. **Document header** — tytuł + metadata (effective_date, version, updated_at)
3. **Print button** — `window.print()` z ukryciem sidebaru w CSS `@media print`
4. **Sticky TOC sidebar** — IntersectionObserver do podświetlania aktywnej sekcji
5. **Anchor links** — każdy `<h2>` dostaje `id` (slugified tytuł) via DOM manipulation po renderze
6. **Responsive** — TOC ukryty na mobile, zastąpiony `<select>` "Jump to section"
7. **Language pills** — EN / PL / PT z linkami przełączającymi język (jeśli route obsługuje lang param)

**Stack:** React + Tailwind CSS (istniejący)

---

### 2.8 Treść dokumentów — PageSeeder.php (pełna przebudowa, 3 języki)

Każdy dokument musi mieć **pełną treść w EN, PL i PT** z tokenami zmiennych.  
Treść musi spełniać wymagania obu jurysdykcji.

**Języki i jurysdykcje:**
- **EN** — UK GDPR, Consumer Rights Act, PECR, Companies Act; styl formalny angielski
- **PL** — RODO, Kodeks cywilny, Prawo telekomunikacyjne, UOKIK; styl formalny polski
- **PT** — UK GDPR (bo klient mieszka w UK); tłumaczenie EN dostosowane do odbiorcy PT-speaking UK resident

---

## 3. Regulacje prawne — matryca zgodności

### 3.1 Polityka Prywatności

| Wymóg | Regulacja UK | Regulacja PL | Status |
|-------|-------------|-------------|--------|
| Tożsamość administratora + dane kontaktowe | UK GDPR Art. 13/14 | RODO Art. 13/14 | ❌ Brak adresu, numeru firmy |
| Cel i podstawa prawna przetwarzania | UK GDPR Art. 13(1)(c-d) | RODO Art. 13(1)(c-d) | ⚠️ Częściowe |
| Odbiorcy danych (procesorzy, subprocesory) | UK GDPR Art. 13(1)(e) | RODO Art. 13(1)(e) | ❌ Brak |
| Przekazywanie danych poza UK | UK GDPR Art. 46 | RODO Art. 46 | ❌ Brak |
| Okres retencji | UK GDPR Art. 13(2)(a) | RODO Art. 13(2)(a) | ⚠️ Ogólnikowe |
| Prawa podmiotów danych | UK GDPR Art. 13(2)(b-d) | RODO Art. 13(2)(b-d) | ⚠️ Niekompletne |
| Prawo wniesienia skargi | UK GDPR → ICO | RODO → UODO | ⚠️ Tylko ICO |
| Profilowanie / decyzje automatyczne | UK GDPR Art. 22 | RODO Art. 22 | ❌ Brak |
| Źródło danych (gdy pozyskane pośrednio) | UK GDPR Art. 14 | RODO Art. 14 | ❌ Brak |
| Świadomość zmiany polityki | ICO guidance | UODO guidance | ❌ Brak |

**Kluczowe uzupełnienia dla PL:**
- Wzmianka o UODO jako organie nadzorczym (nie ICO) dla klientów z Polski
- Informacja o możliwości przekazywania danych do UK (państwo trzecie wobec UE po Brexicie — UK ma adequacy decision do ~2025, wymaga monitorowania)
- Dane w języku polskim muszą spełniać wymogi RODO (nie UK GDPR) dla klientów będących osobami fizycznymi z UE

**Kluczowe uzupełnienia dla UK/NI:**
- Numer rejestracyjny ICO (Information Commissioner's Office) — obowiązek dla firm przetwarzających dane
- Adres biura zarejestrowanego w Anglii/Walii
- NI: NI nie ma oddzielnej regulacji, obowiązuje UK GDPR — jednak procedury sądowe podlegają NI Courts

---

### 3.2 Regulamin (Terms & Conditions)

| Wymóg | Regulacja UK | Regulacja PL | Status |
|-------|-------------|-------------|--------|
| Dane sprzedawcy (adres, reg. nr) | Companies Act 2006; E-Commerce Regs 2002 | UoŚUDE; KSH | ❌ Brak |
| Opis usługi + zakres | Consumer Rights Act 2015 s.49 | KC art. 627+ | ⚠️ Ogólnikowe |
| Ceny, VAT, waluta | Consumer Rights Act 2015; VAT Act 1994 | Ustawa o cenach; Ustawa VAT | ⚠️ Niekompletne |
| Prawo do odstąpienia (14 dni) | Consumer Contracts Regs 2013 reg. 29 | KC art. 27 UPK; Ustawa o prawach konsumenta | ❌ Brak |
| Realizacja: terminy, opóźnienia | Supply of Goods & Services Act 1982 s.14 | KC art. 476-480 | ⚠️ Ogólnikowe |
| Reklamacje / rękojmia | Consumer Rights Act 2015 s.54-58 | KC art. 556-576 (rękojmia) | ❌ Brak |
| Ograniczenie odpowiedzialności | Unfair Contract Terms Act 1977 (UCTA) | KC art. 361-363 | ⚠️ Niezgodne z UCTA |
| Własność intelektualna | CDPA 1988; CRA 2015 | Ustawa o prawie autorskim | ⚠️ Ogólnikowe |
| Rozstrzyganie sporów (ADR) | ADR Regulations 2015; ODR Platform | Ustawa o mediacji; KPC | ❌ Brak |
| Prawo właściwe | English/NI law | Prawo polskie (jeśli klient z PL) | ❌ Brak klauzuli |
| Siła wyższa | Common law | KC art. 471 | ❌ Brak |
| Płatności, depozyty, kary umowne | CRA 2015 | KC art. 483-484 (kary umowne) | ⚠️ Częściowe |

**Kluczowe uzupełnienia dla PL:**
- Klauzula prawa właściwego: dla konsumentów z UE/PL prawo polskie może być bardziej korzystne — klauzula wyboru prawa nie może pozbawiać konsumenta ochrony kraju zamieszkania (Rozporządzenie Rzym I)
- Rękojmia: 2 lata dla konsumentów (nie do wyłączenia)
- Klauzule abuzywne: lista klauzul zabronionych w polskim prawie konsumenckim (art. 385³ KC)
- UOKIK: dozwolone wzorce umowne / niedozwolone klauzule w rejestrze UOKIK

**Kluczowe uzupełnienia dla UK/NI:**
- Prawo do reklamacji w ciągu 30 dni (CRA 2015) dla usług cyfrowych
- NI: prawo konsumenta w NI podlega UK CRA i NI-specific rules — Consumerline NI jako punkt kontaktowy
- E-commerce: obowiązek podania danych firmy wynikający z E-Commerce Regulations 2002
- Finansowanie/depozyty: regulacja przez Consumer Credit Act 1974 jeśli >£30k

---

### 3.3 Polityka Cookies

| Wymóg | Regulacja UK | Regulacja PL | Status |
|-------|-------------|-------------|--------|
| Podstawa prawna | PECR 2003 (si. 6); ICO guidance | Prawo telekomunikacyjne art. 173 | ⚠️ Ogólnikowe |
| Lista plików cookies z nazwami | ICO cookie guidance 2023 | UODO/UOKIK guidelines | ❌ Brak |
| Czas trwania każdego cookie | ICO guidance | — | ❌ Brak |
| Cel każdego cookie | PECR 2003 | Pr. tel. art. 173 | ⚠️ Kategorie, brak szczegółów |
| Zgoda przed niezbędnymi cookies | ICO guidance | UODO | ⚠️ Brak mechanizmu |
| Wycofanie zgody (tak łatwo jak udzielenie) | PECR 2003 s.6(3) | Pr. tel. art. 173 | ❌ Brak |
| Cookies stron trzecich | ICO guidance | UODO | ❌ Brak |
| Instrukcja zarządzania (przeglądarka) | ICO guidance | — | ⚠️ Ogólnikowe |
| Opt-out dla analityki (GA) | ICO; GDPR Art. 7 | RODO Art. 7 | ❌ Brak |

**Kluczowe uzupełnienia:**
- Konkretna tabela: `Nazwa cookie | Dostawca | Cel | Czas trwania | Kategoria`
- Linki do opt-out narzędzi (Google Analytics opt-out, Google Ads settings)
- Instrukcja usuwania cookies w Chrome/Firefox/Safari/Edge
- Referencja do consent banner (jeśli istnieje) — zarządzanie zgodami
- UK: ICO zaktualizowało wytyczne w 2023 — convenience cookies wymagają zgody
- PL: Prezes UROKIK nałożył kary za brak prawidłowego consent managementu (2023-2024)

---

### 3.4 Oświadczenie o Dostępności

| Wymóg | Regulacja UK | Regulacja PL | Status |
|-------|-------------|-------------|--------|
| Status zgodności (pełna/częściowa/nie) | Accessibility Regs 2018 | Ustawa o dostępności cyfrowej 2019 | ❌ Brak statusu |
| Lista znanych niedostępnych elementów | Accessibility Regs 2018 Schedule 1 | Ustawa 2019 art. 10 | ❌ Brak |
| Uzasadnienie wyjątków | Regs 2018 reg. 4(1)(b) (disproportionate burden) | Ustawa 2019 art. 8 | ❌ Brak |
| Mechanizm feedbacku | Regs 2018 reg. 8 | Ustawa 2019 art. 10(2) | ⚠️ Brak formularza/emaila |
| Procedura egzekucji | Regs 2018 reg. 9 | Ustawa 2019 art. 18 | ❌ Brak |
| Data ostatniej oceny | ICO/GDS guidance | Ustawa 2019 | ❌ Brak |
| Zakres stosowania | Regs 2018 reg. 2 | Ustawa 2019 art. 2 | ❌ Brak |

**Uwagi:**
- UK Accessibility Regulations 2018 dotyczą **sektora publicznego** — jeśli WebsiteExpert to prywatna firma, nie mają zastosowania prawnie, ale stanowią dobry standard
- Dla stron e-commerce (private sector) w UK obowiązuje Equality Act 2010 s.29 (reasonable adjustments)
- PL: Ustawa o dostępności cyfrowej z 4 kwietnia 2019 co do zasady dotyczy podmiotów publicznych
- WCAG 2.1 AA: rekomendowany standard zarówno w UK jak i UE (European Accessibility Act 2025!)
- **European Accessibility Act (EAA)** — obowiązuje od 28 czerwca 2025 dla produktów i usług cyfrowych w UE → dotyczy polskich klientów B2C!

---

## 4. Plan realizacji — kolejność kroków

### Faza 1 — Infrastruktura (backend + admin)
**Priorytet: Wysoki | Szacowany nakład: mały**

1. **Migracja** — dodanie `effective_date` + `version` do tabeli `pages`
2. **Model Page.php** — aktualizacja `$fillable` i `$casts`
3. **LegalSettingsPage.php** — nowa strona ustawień w Filament (Settings group)
4. **SettingsSeeder / migracja danych** — wypełnienie tabeli `settings` placeholderami z kluczem `legal.*`
5. **PageController.php** — dodanie `replaceLegalVars()` + przekazanie `effective_date`, `version`
6. **PageResource.php** — dodanie pól `effective_date` + `version` w formularzu

### Faza 2 — UI dokumentu (frontend)
**Priorytet: Wysoki | Szacowany nakład: średni**

7. **CmsPage.jsx** — pełny redesign:
   - DocumentHeader (metadata, print button, language pills)
   - TableOfContents (auto-generated, sticky, IntersectionObserver)
   - Content z anchor ID na h2 (DOM manipulation po renderze)
   - Responsive (TOC jako select na mobile)

### Faza 3 — Treść prawna (seeder, 3 języki)
**Priorytet: Krytyczny | Szacowany nakład: duży**

8. **PageSeeder — Privacy Policy** — pełna wersja EN + PL + PT z tokenami `{{legal.*}}`
9. **PageSeeder — Terms & Conditions** — pełna wersja EN + PL + PT z tokenami
10. **PageSeeder — Cookie Policy** — pełna wersja EN + PL + PT (z tabelą cookies + tokenami GA/GTM)
11. **PageSeeder — Accessibility Statement** — pełna wersja EN + PL + PT

### Faza 4 — Weryfikacja i testy
**Priorytet: Wysoki**

12. Uzupełnienie danych w `LegalSettingsPage` (dane firmowe) → weryfikacja podstawiania tokenów
13. `npm run build` + `php artisan db:seed --class=PageSeeder`
14. Test renderowania TOC, print CSS, responsywności
15. Walidacja linków zewnętrznych (ICO, UODO, UOKIK)

---

## 5. Dane wymagane — zarządzane przez LegalSettingsPage

Poniższe dane będą wypełniane **bezpośrednio w panelu Filament** → Settings → Legal & Company (nowa strona). Zastępują tabelę z sekcji "dane nieznane" — zamiast edytować seeder, uzupełniasz formularz.

| Klucz (`legal.*`) | Label w panelu | Placeholder / Default |
|-------------------|---------------|----------------------|
| `company_name` | Company legal name | WebsiteExpert Ltd |
| `company_number` | Companies House No. | ❌ do uzupełnienia |
| `company_address` | Registered address | ❌ do uzupełnienia |
| `vat_number` | VAT number | GB xxxxxxx |
| `company_email` | Contact email | hello@websiteexpert.co.uk |
| `company_phone` | Contact phone | +44 ... |
| `ico_number` | ICO Registration No. | ZB xxxxxxx |
| `ico_registration_url` | ICO register URL | https://ico.org.uk/... |
| `privacy_email` | Privacy contact email | privacy@websiteexpert.co.uk |
| `dpo_name` | DPO name (optional) | — |
| `data_retention_years` | Record retention (years) | 7 |
| `complaints_email` | Complaints email | support@websiteexpert.co.uk |
| `complaints_phone` | Complaints phone | +44 ... |
| `response_days` | Response time (days) | 14 |
| `deposit_percent` | Deposit required (%) | 50 |
| `payment_terms_days` | Payment terms (days) | 30 |
| `cookie_policy_email` | Cookie queries email | privacy@websiteexpert.co.uk |
| `privacy_effective_date` | Privacy Policy — effective from | ❌ do uzupełnienia |
| `privacy_version` | Privacy Policy — version | 1.0 |
| `terms_effective_date` | T&C — effective from | ❌ do uzupełnienia |
| `terms_version` | T&C — version | 1.0 |
| `cookies_effective_date` | Cookie Policy — effective from | ❌ do uzupełnienia |
| `cookies_version` | Cookie Policy — version | 1.0 |
| `accessibility_effective_date` | Accessibility — effective from | ❌ do uzupełnienia |
| `accessibility_version` | Accessibility — version | 1.0 |

> Pola **❌** muszą być uzupełnione przed wdrożeniem produkcyjnym.  
> Panel pokaże ostrzeżenie walidacyjne gdy wymagane pola są puste.

---

---

## 6. Decyzje techniczne

### Architektura zmiennych — dlaczego tokeny `{{legal.key}}` a nie Blade?

Dokumenty są edytowalne przez TinyEditor w Filament. Blade nie działa po stronie React/Inertia, więc używamy własnego systemu tokenów zastępowanych w kontrolerze. Tokeny są czytelne dla redaktora treści, nie wymagają znajomości PHP, i są bezpieczne (escapowane przez `e()`).

### Dlaczego LegalSettingsPage zamiast pól w CMS Pages?

Dane firmowe (adres, numer VAT) są **globalne** — takie same w wszystkich 4 dokumentach × 3 językach = 12 miejsc. Gdyby były w seederze lub w personal fields każdej strony, zmiana adresu wymagałaby edycji 12 razy. `LegalSettingsPage` + tokeny → zmiana raz, propagacja automatyczna.

### Dlaczego Setting model (klucz-wartość) a nie osobna tabela?

Projekt już używa `Setting` z tym wzorcem w `TrackingSettingsPage`. Spójność architektury + gotowe caching (`now()->addDay()`). Brak potrzeby nowej migracji poza wypełnieniem danych.

### Wielojęzyczność — strategia dla PT

- **EN i PL** — pełna treść z odniesieniami do właściwych jurysdykcji
- **PT** — treść oparta na wersji EN (UK GDPR, CRA), przetłumaczona na język portugalski dla społeczności brazylijskiej/portugalskiej mieszkającej w UK. Zawiera adnotację: *"If you reside in Portugal or the EU, the GDPR applies..."*

### Jak działają language pills w CmsPage.jsx?

Komponent otrzymuje z kontrolera `availableLocales` (np. `['en','pl','pt']`). Przełącznik języka zmienia parametr `?lang=pl` w URL → PageController uwzględnia ten parametr przy wyborze tłumaczenia (uzupełnienie kontrolera).

---

## 7. Kolejność implementacji (dla sesji roboczej)

```
[ ] 1. Migracja: effective_date + version do tabeli pages
[ ] 2. Model Page.php: $fillable + $casts
[ ] 3. LegalSettingsPage.php: nowa strona Filament z formularzem danych
[ ] 4. SettingsSeeder: wypełnienie kluczy legal.* placeholderami
[ ] 5. PageController.php: replaceLegalVars() + przekazanie nowych pól
[ ] 6. PageResource.php: pola effective_date + version
[ ] 7. CmsPage.jsx: redesign (TOC + DocumentHeader + language pills)
[ ] 8. Privacy Policy: pełna treść EN + PL + PT z tokenami
[ ] 9. Terms & Conditions: pełna treść EN + PL + PT z tokenami
[ ] 10. Cookie Policy: pełna treść EN + PL + PT (tabela cookies + tokenami)
[ ] 11. Accessibility Statement: pełna treść EN + PL + PT
[ ] 12. npm run build + php artisan db:seed --class=PageSeeder
[ ] 13. Uzupełnienie danych w LegalSettingsPage → weryfikacja tokenów
[ ] 14. Weryfikacja end-to-end
```

---

## 8. Uwagi końcowe

> **Zastrzeżenie:** Treść dokumentów prawnych oparta jest na ogólnie dostępnych regulacjach UK GDPR, RODO, PECR, Consumer Rights Act i polskich odpowiednikach. **Przed wdrożeniem produkcyjnym zaleca się konsultację z radcą prawnym lub solicytorem specjalizującym się w prawie UK i UE.** Wygenerowana treść stanowi solidną podstawę roboczą, ale nie zastępuje porady prawnej.

> **Kwestia NI (Irlandia Północna):** Po Brexicie NI ma specyficzny status — podlega UK GDPR w zakresie danych, ale niektóre aspekty regulacji konsumenckich mogą być odmienne. Jeśli firma obsługuje klientów z NI specifically, warto to zaznaczyć.

> **PT — jurysdykcja:** Wersja PT kierowana jest do osób mieszkających w UK i mówiących po portugalsku. Podlega UK GDPR (nie RODO), co jest wyraźnie zaznaczone w treści. Klientów mieszkających w Portugalii/UE obowiązuje RODO — stosowna adnotacja zostanie dodana.
