# CRM Workflow — WebsiteExpert
## Od leada do odbioru projektu przez klienta
### Wersja 2.0 — Marzec 2026

> Dokument opisuje pełny przepływ pracy w systemie CRM — od momentu pojawienia się leada,
> przez wszystkie etapy lejka sprzedażowego, wyceny, kontrakty, realizację projektu,
> fakturowanie, aż po finalne zamknięcie i odbiór przez klienta.
> Uwzględnia portal klienta, e-podpisy, automatyzacje i powiadomienia SMS/e-mail.

---

## Przegląd lejka sprzedażowego

```
[New Lead] → [Contacted] → [Proposal Sent] → [Negotiation] → [Won] ──→ PROJECT
                                                                ↓
                                                             [Lost]
```

> **Pipeline jest konfigurowalny** — etapy zarządzane są w `Settings → Pipeline Stages`.
> System nie ma stałego enum statusów leada — każdy etap to rekord w tabeli `pipeline_stages`.
> Wynik leada (won/lost) zapisywany jest przez pola `won_at` / `lost_at` + `lost_reason`.

---

## ETAP 1 — New Lead

**Opis:** Lead pojawia się w systemie — formularz ze strony, telefon, polecenie lub
ręczne dodanie przez managera.

### Akcje w CRM

1. **Dodaj leada** (`Leads → New Lead`)
   - Uzupełnij: tytuł leada, klient (istniejący lub nowy), typ usługi, szacowana wartość
   - Przypisz właściciela (`Assigned To`)
   - Ustaw oczekiwaną datę zamknięcia (`Expected Close`)
2. **Uzupełnij profil klienta** (`Clients → Client record`)
   - Kontakty: imię, e-mail (wymagany do emaili), telefon (wymagany do SMS)
   - Nazwa firmy, adres
3. **Ustaw stage:** pierwszy etap w pipeline (domyślnie `New Lead`)

### Checklist
- [ ] Zapisane dane kontaktowe klienta (e-mail + telefon)
- [ ] Potwierdzony szacowany budżet / wartość leada
- [ ] Zidentyfikowany typ projektu
- [ ] Lead przypisany do właściciela

### Automatyzacje — `lead.created`

| Trigger | Akcja | Odbiornik | Przykład |
|---|---|---|---|
| `lead.created` | `send_sms` | `client` | "Dziękujemy za kontakt, odezwiemy się wkrótce" |
| `lead.created` | `notify_admin` | `mail.admin_address` | "Nowy lead: {{lead_title}} od {{client_name}}" |
| `lead.created` | `send_email` | `assigned_user` | "Przypisano Ci nowego leada: {{lead_title}}" |

> **SMS:** wymaga skonfigurowanego Twilio w `Settings → Integrations` (`twilio_enabled = true`).
> Numer klienta musi być w formacie UK (`07xxx`) lub E.164. System automatycznie normalizuje `07` → `+447`.

### Cel etapu
Kwalifikacja leada — upewnij się, że warto poświęcić czas na dalszy kontakt.

---

## ETAP 2 — Contacted

**Opis:** Nawiązano pierwszy kontakt z klientem. Cel: poznać wymagania i umówić rozmowę.

### Akcje w CRM

1. **Zmień stage** na `Contacted` (lub odpowiedni etap w pipeline)
2. **Wyślij e-mail powitalny** (`Send Email` na stronie leada)
   - Szablon z CRM, zmienne: `{{client_name}}`, `{{lead_title}}`, `{{assigned_name}}`
3. **Zanotuj ustalenia** w sekcji Notes / Activity na stronie leada
4. **Zaktualizuj dane leada** — wpisz szczegóły wymagań w opisie

### Checklist
- [ ] Wysłany e-mail powitalny
- [ ] Zaplanowana rozmowa discovery
- [ ] Zakwalifikowane wymagania i cele projektu
- [ ] Potwierdzony kontakt z osobą decyzyjną

### Automatyzacje — `lead.stage_changed` → Contacted

| Trigger | Akcja | Odbiornik | Przykład |
|---|---|---|---|
| `lead.stage_changed` | `send_email` | `client` | "Dzień dobry {{client_name}}, skontaktujemy się w ciągu 24h" |
| `lead.stage_changed` | `send_sms` | `client` | "Cześć {{client_name}}, oddzwonimy dziś!" |

> **Opóźnienie:** ustaw `delay_minutes` w regule automatyzacji na np. `0` (natychmiast)
> lub `60` (po godzinie). Automatyzacje wykonują się przez kolejkę (`ProcessAutomationJob`).

### Cel etapu
Przeprowadzenie rozmowy kwalifikacyjnej i zebranie brief'u do wyceny.

---

## ETAP 3 — Proposal Sent

**Opis:** Przygotowanie i wysłanie wyceny (Quote) do klienta.

### Akcje w CRM

1. **Zmień stage** na `Proposal Sent`
2. **Utwórz wycenę Quote** (`Quotes → New Quote` lub `Create Quote` na stronie leada)
   - Dodaj pozycje (Line Items): opis, szczegóły, ilość, cena jednostkowa
   - System automatycznie liczy `subtotal`, `VAT ({{vat_rate}}%)`, `total`
   - Ustaw walutę, termin ważności (`valid_until`, domyślnie +30 dni)
3. **Wyślij wycenę do klienta** (`Quotes → View → Send Quote`)
   - Przycisk **Send Quote** (widoczny gdy status = `draft`) → status zmienia się na `sent`, ustawia `sent_at`
   - Klient widzi wycenę w **Portalu Klienta** (`/portal/quotes`)
4. **Klient w Portalu** może:
   - Wyświetlić szczegóły wyceny (pozycje, totale, warunki)
   - Kliknąć **Accept Quote** → status = `accepted`, `accepted_at = now()`
   - Kliknąć **Reject Quote** → status = `rejected`, `rejected_at = now()`
5. **Po akceptacji** — w widoku Quote pojawia się przycisk **Create Contract**

### Checklist
- [ ] Przygotowana spersonalizowana wycena z poprawnymi totami
- [ ] Wycena wysłana do klienta (`status = sent`)
- [ ] Klient zaakceptował w portalu (`status = accepted`)
- [ ] Follow-up zaplanowany (+3 dni) jeśli brak odpowiedzi

### Quote — statusy

| Status | Kolor | Opis |
|---|---|---|
| `draft` | szary | Wycena w przygotowaniu, niewidoczna w portalu |
| `sent` | niebieski | Wysłana, klient może zaakceptować/odrzucić |
| `accepted` | zielony | Klient zaakceptował |
| `rejected` | czerwony | Klient odrzucił |
| `expired` | pomarańczowy | Minął termin ważności |

### Automatyzacje — `quote.sent` / `quote.accepted`

| Trigger | Akcja | Odbiornik | Przykład |
|---|---|---|---|
| `quote.sent` | `send_email` | `client` | Automatyczny email z linkiem do portalu |
| `quote.accepted` | `notify_admin` | `mail.admin_address` | "{{client_name}} zaakceptował wycenę {{lead_title}}" |
| `quote.accepted` | `send_email` | `assigned_user` | "Wycena zaakceptowana — czas na kontrakt!" |

> **Mail przy wysyłce:** `QuoteSentMail` — wysyła email z numerem wyceny i wartością.
> Zmienne dostępne w szablonie: `{{client_name}}`, `{{company_name}}`, `{{lead_title}}`.

---

## ETAP 4 — Negotiation

**Opis:** Klient wyraził zainteresowanie. Negocjacje warunków, zakresu i płatności.

### Akcje w CRM

1. **Zmień stage** na `Negotiation`
2. **Zaktualizuj wycenę** jeśli zakres się zmienił — edytuj Quote, system przeliczy totale
3. **Zaktualizuj notes** na podstawie rozmów
4. **Ustaw `Expected Close`** na realną datę podpisania umowy

### Checklist
- [ ] Omówione zmiany i rewizje oferty
- [ ] Potwierdzony harmonogram projektu
- [ ] Uzgodnione warunki płatności (system zakłada `legal.deposit_percent`% zaliczki, domyślnie 50%)
- [ ] Klient gotowy do podpisania kontraktu

---

## ETAP 4.5 — Kontrakt (Contract)

**Opis:** Gdy wycena jest zaakceptowana, przed uruchomieniem projektu należy podpisać kontrakt.
Jest to etap między Negotiation a Won.

### Akcje w CRM — tworzenie kontraktu

1. **W widoku Quote** kliknij **Create Contract** (widoczny gdy `status` = `accepted` lub `sent`)
   - System prefilluje: `client_id`, `quote_id`, `value`, `currency`
2. **W formularzu kontraktu:**
   - Wybierz **szablon kontraktu** (`Contract Templates`) — system wstawi placeholdery `{{client.company_name}}` itp.
   - Dostępne placeholdery:
     - `{{legal.*}}` — dane firmy (15 kluczy z `Settings → Legal`)
     - `{{client.*}}` — dane klienta (company_name, address, primary_contact_name, email, phone, vat_number)
     - `{{project.*}}` — tytuł, budżet, deadline, service_type, currency
     - `{{contract.*}}` — number, title, date, value, currency
   - Uzupełnij: tytuł, daty (`starts_at`, `expires_at`), wartość, `Terms & Conditions` (RichEditor)
   - Opcjonalnie: dołącz PDF (`file_path`)
3. **Wyślij kontrakt do klienta** — `Contracts → View → Send to Portal`
   - Przycisk **Send to Portal** (widoczny gdy status = `draft`) → status = `sent`, `sent_at = now()`
   - Kontrakt pojawia się w Portalu Klienta (`/portal/contracts`)

### Akcje klienta w Portalu — podpisanie kontraktu

Klient wchodzi w `/portal/contracts`, widzi kontrakt ze statusem "Awaiting Signature",
klika "Sign →":

1. **Wpisuje pełne imię i nazwisko** (wymagane)
2. **Wybiera metodę podpisu:**
   - ✏️ **Draw Signature** — rysowanie podpisu na canvas (base64 PNG zapisywany w `signature_data`)
   - ☑️ **Electronic Acceptance** — zaznaczenie checkboxa jako prawnie wiążący podpis elektroniczny
3. **Zaznacza checkbox** akceptacji warunków
4. **Klika "Sign & Accept Contract"** → system zapisuje:
   - `status = signed`, `signed_at = now()`
   - `signer_name` — imię i nazwisko
   - `signer_ip` — adres IP klienta (dowód elektroniczny)
   - `signature_data` — obraz podpisu (jeśli metoda pad)
5. **Widok po podpisaniu** — zielony baner z imieniem sygnatariusza, datą i IP

> **Admin może też podpisać ręcznie** (`Contracts → View → Mark Signed`) np. po podpisaniu
> papierowym i skanie. W takim przypadku pola `signer_*` nie są wypełniane.

### Kontrakt — statusy

| Status | Kolor | Widoczny w portalu | Opis |
|---|---|---|---|
| `draft` | szary | NIE | W przygotowaniu |
| `sent` | niebieski | TAK | Wysłany, oczekuje na podpis |
| `signed` | zielony | TAK | Podpisany przez klienta |
| `expired` | pomarańczowy | TAK | Minął termin ważności |
| `cancelled` | czerwony | TAK | Anulowany |

### Automatyzacje — `contract.sent` / `contract.signed`

| Trigger | Akcja | Odbiornik | Przykład |
|---|---|---|---|
| `contract.sent` | `send_email` | `client` | "Kontrakt {{contract.number}} czeka na Twój podpis w portalu" |
| `contract.sent` | `send_sms` | `client` | "Nowy kontrakt do podpisania w Twoim portalu klienta" |
| `contract.signed` | `notify_admin` | `mail.admin_address` | "{{client_name}} podpisał kontrakt — można uruchomić projekt" |
| `contract.signed` | `send_email` | `assigned_user` | "Kontrakt podpisany — czas na projekt!" |
| `contract.created` | `create_portal_access` | `client` | Automatycznie tworzy konto w portalu jeśli klient go nie ma |

### Checklist
- [ ] Kontrakt utworzony z odpowiedniego szablonu
- [ ] Placeholdery poprawnie interpolowane
- [ ] Kontrakt wysłany do portalu (`status = sent`)
- [ ] Klient podpisał kontrakt (`status = signed`)

---

## ETAP 5A — Won (Wygrany Lead)

**Opis:** Kontrakt podpisany. Czas uruchomić projekt.

### Akcje w CRM

1. **Zmień stage** na `Won` (lub użyj przycisku `Mark as Won` → ustawia `won_at = now()`)
2. **Utwórz projekt** (`Convert to Project` na stronie leada)
   - Wybierz **szablon projektu** (`Project Templates`) — system automatycznie tworzy fazy
   - Dostępne typy projektów: `wizytowka` (Business Card), `landing` (Landing Page), `ecommerce` (E-Commerce), `aplikacja` (Web Application), `seo` (SEO), `other`
   - Ustaw datę startu i deadline
   - `portal_token` generowany automatycznie (64 znaki) — używany do bezpośredniego dostępu do projektu
3. **Skonfiguruj dostęp do portalu** (jeśli nie zrobiono wcześniej)
   - `Clients → [Klient] → Create Portal Access` lub automatyzacja `create_portal_access`
   - System tworzy konto użytkownika, przypisuje rolę `client`, wysyła `PortalInviteMail`
   - **`PortalInviteMail`** zawiera: login email, tymczasowe hasło, link do portalu
   - Szablon emaila pobierany z `EmailTemplates` (slug: `portal_invite`, język `pl`)
4. **Wystaw fakturę zaliczkową** (50% wartości — `legal.deposit_percent`)
   - `Projects → [Projekt] → Invoices → New Invoice` lub `Invoices → New Invoice`
   - Pozycja: "Zaliczka — {{lead_title}}"
5. **Wyślij fakturę** — zmień status na `sent` → system wysyła `InvoiceSentMail`
6. **Przeprowadź Kickoff Call** i zanotuj w notes projektu

### Checklist
- [ ] Lead oznaczony jako Won (`won_at` ustawiony)
- [ ] Projekt utworzony z odpowiedniego szablonu
- [ ] Portal klienta aktywny (konto + zaproszenie wysłane)
- [ ] Faktura zaliczkowa wystawiona i wysłana
- [ ] Kickoff call przeprowadzony i zanotowany

### Automatyzacje — `project.created` / `invoice.sent`

| Trigger | Akcja | Odbiornik | Przykład |
|---|---|---|---|
| `project.created` | `notify_admin` | `mail.admin_address` | "Nowy projekt: {{project_name}} przypisany do {{assigned_name}}" |
| `project.created` | `send_email` | `client` | "Gratulacje! Zaczynamy projekt 🎉" |
| `invoice.sent` | `send_email` | `client` | Automatyczna wiadomość z fakturą (`InvoiceSentMail`) |

---

## ETAP 5B — Lost (Przegrany Lead)

### Akcje w CRM

1. **Zmień status** na `Lost` (`lost_at = now()`, wpisz `lost_reason`)
2. **Zanotuj powód** (cena, czas, konkurencja, brak budżetu)
3. **Wyślij grzeczną wiadomość zamykającą** (`Send Email` z szablonu)
4. **Opcjonalnie:** tag klienta do re-engagement (`add_tag` w automatyzacji)

### Automatyzacje (opcjonalne)

| Trigger | Akcja | Przykład |
|---|---|---|
| `lead.stage_changed` → Lost | `send_email` | "Dziękujemy za zainteresowanie — zapraszamy w przyszłości" |
| `lead.stage_changed` → Lost | `add_tag` | `re-engagement` — do kampanii follow-up |

---

## ETAP 6 — Realizacja Projektu

Po konwersji leada do projektu, praca odbywa się w zakładce `Projects`.
Klient śledzi postęp przez **Portal Klienta** (`/portal/projects`).

### Fazy projektu (ProjectPhase)

Status fazy: `pending` → `in_progress` → `completed` (lub `cancelled`)

Szablony faz (`Project Templates` w Settings) definiują domyślne fazy per typ.

### 6A — Wizytówka / Landing Page

| # | Faza | Akcje CRM |
|---|---|---|
| 1 | Discovery & Brief | Zebranie treści, logotypów, referencji — zanotuj w projekcie |
| 2 | Design Mockups | 2 propozycje layoutu — prześlij klientowi link/plik, poczekaj na feedback przez portal |
| 3 | Development | Kodowanie — zaktualizuj fazę na `in_progress` |
| 4 | Content Integration | Wgranie tekstów, zdjęć, SEO |
| 5 | Testing & QA | Testy mobilne, przeglądarki, performance |
| 6 | Launch & Handover | Deploy, przekazanie dostępów, e-mail z instrukcjami |

### 6B — E-Commerce

| # | Faza | Akcje CRM |
|---|---|---|
| 1 | Discovery & Strategy | Analiza, platforma, UX strategy |
| 2 | UX / Wireframes | Ścieżka zakupowa, layout |
| 3 | Design | Figma, identyfikacja wizualna |
| 4 | Development | CMS / WooCommerce / Laravel |
| 5 | Product Import | Produkty, zdjęcia, kategorie |
| 6 | Payment Integration | Przelewy24, Stripe, PayU |
| 7 | Testing & QA | Testy zakupowe, bezpieczeństwo |
| 8 | SEO & Analytics | GA4, Search Console, sitemap |
| 9 | Launch | Deploy, DNS, brief szkoleniowy |

### 6C — Web Application

| # | Faza | Akcje CRM |
|---|---|---|
| 1 | Discovery & Requirements | Wymagania funkcjonalne i niefunkcjonalne |
| 2 | System Architecture | DB schema, API design |
| 3 | UI/UX Design | Prototypy Figma, design system |
| 4 | Backend Development | Laravel API, modele, logika |
| 5 | Frontend Development | React / Inertia / Blade |
| 6 | Integration & API | Płatności, SMS, email, zewnętrzne API |
| 7 | Testing & QA | Jednostkowe, E2E, penetracyjne |
| 8 | Deployment | CI/CD, SSL, backup |
| 9 | Documentation | Techniczna i użytkownika |

### Komunikacja z klientem przez portal

- Klient widzi listę faz projektu z procentem ukończenia
- Możliwość wysyłania wiadomości (`ProjectMessage`) — zarówno klient jak i admin
- Wiadomości czytane przez admina oznaczane `read_at`
- Automatyzacje `project.status_changed` → wysyłają `ProjectStatusMail` do klienta

### Automatyzacje projektu

| Trigger | Akcja | Odbiornik | Przykład |
|---|---|---|---|
| `project.status_changed` | `send_email` | `client` | `ProjectStatusMail` — "Status projektu zmieniony na: {{status}}" |
| `project.status_changed` | `send_sms` | `client` | "Aktualizacja projektu {{project_name}}: etap zakończony" |
| `project.status_changed` | `notify_admin` | admin | Wewnętrzne powiadomienie o zmianie |

---

## ETAP 7 — Fakturowanie

### Schemat faktur (standard)

```
Faktura 1 — Zaliczka (50%)          →  Po podpisaniu kontraktu / Won
Faktura 2 — Płatność końcowa (50%)  →  Po odbiorze projektu
```

> Procent zaliczki definiowany w `Settings → Legal → Deposit Percent` (domyślnie 50%).
> Termin płatności: `Settings → Legal → Payment Terms Days` (domyślnie 30 dni).

### Statusy faktur

| Status | Opis |
|---|---|
| `draft` | Wersja robocza — niewidoczna dla klienta w portalu |
| `sent` | Wysłana — klient widzi w `/portal/invoices` |
| `partially_paid` | Częściowa płatność odnotowana |
| `paid` | Opłacona w całości |
| `overdue` | Po terminie (sprawdzane przez `isOverdue()`) |
| `cancelled` | Anulowana |

### Akcje w CRM

1. **Utwórz fakturę** → dodaj pozycje (Line Items) → system liczy `subtotal + VAT = total`
2. **Zmień status na `sent`** → system wysyła `InvoiceSentMail` do klienta
3. **Klient widzi fakturę w portalu** (`/portal/invoices`) — numer, status, kwota, termin
4. **Stripe:** jeśli ustawione `stripe_payment_link`, klient może zapłacić online
5. **Odznacz jako `paid`** gdy wpłata przychodzi → system loguje `paid_at`
6. **Faktury po terminie** widoczne w dashboardzie (badge `overdue`)

### Automatyzacje fakturowania

| Trigger | Akcja | Odbiornik | Przykład |
|---|---|---|---|
| `invoice.sent` | `send_email` | `client` | `InvoiceSentMail` — "Przesyłamy fakturę nr {{invoice_number}}" |
| `invoice.overdue` | `send_email` | `client` | "Przypomnienie: faktura {{invoice_number}} jest zaległa" |
| `invoice.overdue` | `send_sms` | `client` | SMS: "Mamy niezapłaconą fakturę — prosimy o kontakt" |
| `invoice.paid` | `notify_admin` | admin | "Faktura {{invoice_number}} opłacona przez {{client_name}}" |
| `invoice.paid` | `send_email` | `client` | Potwierdzenie wpłaty |

> **Uwaga:** `invoice.overdue` wymaga skonfigurowania schedulera / cron — trigger jest
> zadeklarowany w systemie, ale nie jest jeszcze dispatched automatycznie przez listener.
> Należy uruchomić `php artisan schedule:run` i dodać komendę sprawdzającą zaległe faktury.

---

## ETAP 8 — Finalizacja i Odbiór

### Akcje w CRM

1. **Przesuń projekt do ostatniej fazy** (np. `Launch & Handover`)
2. **Zmień status projektu** na `completed`
3. **Wystaw fakturę końcową** (pozostałe 50%)
4. **Wyślij e-mail podsumowujący** z danymi dostępowymi, instrukcją obsługi, informacją o gwarancji
5. **Poproś o referencje** (review Google / LinkedIn)

### Automatyzacje zamknięcia

| Trigger | Akcja | Odbiornik | Przykład |
|---|---|---|---|
| `project.status_changed` → `completed` | `send_email` | `client` | `ProjectStatusMail` — "Projekt ukończony! Oto Twoje dane dostępowe…" |
| `project.status_changed` → `completed` | `notify_admin` | admin | "Projekt {{project_name}} zamknięty — czas na fakturę końcową" |

### Opcjonalne po zamknięciu

- Utwórz nowego leada dla upsell / kolejnego projektu
- Zaplanuj check-in po 30/60 dniach (`delay_minutes`)

---

## Portal Klienta — pełna mapa funkcji

| URL | Strona | Co widzi klient |
|---|---|---|
| `/portal` | Dashboard | Projekty (5 ostatnich), faktury (5), wyceny (5) |
| `/portal/projects` | Lista projektów | Tytuł, status, deadline, typ usługi |
| `/portal/projects/{id}` | Szczegół projektu | Fazy, tasks, wiadomości, możliwość wysłania wiadomości |
| `/portal/invoices` | Faktury | Numer, status, kwota, termin, link Stripe |
| `/portal/quotes` | Wyceny | Numer, status, total, termin ważności; `View →` (ukryty dla draft) |
| `/portal/quotes/{id}` | Szczegół wyceny | Pozycje, totale, notes, terms; przyciski Accept/Reject (gdy sent) |
| `/portal/contracts` | Kontrakty | Numer, tytuł, status, wartość; `Sign →` lub `View →` |
| `/portal/contracts/{id}` | Szczegół kontraktu | Treść terms, summary; formularz podpisu (gdy sent) |

### Dostęp do portalu

- Konto tworzone przez: `create_portal_access` (automatyzacja) lub ręcznie przez admina
- Rola użytkownika: `client`
- Zaproszenie: `PortalInviteMail` z loginem, hasłem i linkiem

---

## Powiadomienia — pełna tabela

### E-maile (klasy Mail)

| Klasa | Kiedy wysyłana | Odbiornik |
|---|---|---|
| `QuoteSentMail` | Wycena oznaczona jako `sent` | Klient |
| `InvoiceSentMail` | Faktura oznaczona jako `sent` | Klient |
| `PortalInviteMail` | Tworzone konto w portalu | Klient |
| `ProjectStatusMail` | Zmiana statusu projektu | Klient |
| `ClientEmailMail` | Ręczny e-mail z szablonu (z leada) | Klient (dowolny adresat) |
| `NewLeadMail` | Nowy lead (automatyzacja) | Admin |

### SMS (Twilio)

- Szablony w `Settings → SMS Templates`
- Zmienne: `{{client_name}}`, `{{company_name}}`, `{{lead_title}}`, `{{project_name}}`, `{{assigned_name}}`, `{{stage_name}}` itp.
- Wymagana konfiguracja: `Settings → Integrations → Twilio (SID, Token, From)`
- Numery UK: `07xxx` → auto-normalizacja do `+447xxx`

### Automatyzacje — dostępne akcje

| Akcja (`type`) | Opis |
|---|---|
| `send_email` | Wysyła email przez skonfigurowany Mail → klient / admin / assigned_user |
| `send_sms` | Wysyła SMS przez Twilio → klient / assigned_user |
| `notify_admin` | Wysyła surowy email na adres admina (`mail.admin_address`) |
| `create_portal_access` | Tworzy konto portalu dla klienta, wysyła `PortalInviteMail` |
| `add_tag` | Dodaje tag do leada (`leads.tags`) |
| `change_status` | Zmienia status encji (Lead / Project / Invoice) |

### Opóźnienia automatyzacji

- Pole `delay_minutes` w regule automatyzacji (0 = natychmiast)
- Zadanie wykonywane przez kolejkę Laravel (`ProcessAutomationJob`)
- Warunki (`conditions`): operatory `=`, `!=`, `>`, `<`, `contains`

---

## Zmienne szablonowe (e-mail / SMS)

| Token | Źródło |
|---|---|
| `{{today}}` | Aktualna data |
| `{{client_name}}` | `clients.primary_contact_name` |
| `{{company_name}}` | `clients.company_name` |
| `{{lead_title}}` | `leads.title` |
| `{{stage_name}}` | `pipeline_stages.name` |
| `{{assigned_name}}` | `users.name` (właściciel leada/projektu) |
| `{{project_name}}` | `projects.title` |
| `{{invoice_number}}` | `invoices.number` |

---

## Podsumowanie — mapa akcji CRM v2

```
LEAD CREATED
  ├─ Auto SMS → klient: "Dziękujemy za kontakt"
  ├─ Auto Email → admin: "Nowy lead: {{lead_title}}"
  └─ Checklist: dane, budżet, typ, owner

CONTACTED
  ├─ Email powitalny → klient (ręcznie lub auto)
  ├─ SMS → klient: "Oddzwonimy dziś!"
  └─ Discovery call + kwalifikacja

PROPOSAL SENT
  ├─ Utwórz Quote → pozycje → totale auto
  ├─ Send Quote (admin) → status: draft → sent
  ├─ QuoteSentMail → klient
  ├─ Klient w portalu: Accept / Reject
  └─ Po akceptacji: Create Contract

KONTRAKT
  ├─ Utwórz z szablonu (placeholdery auto)
  ├─ Send to Portal (admin) → status: draft → sent
  ├─ Auto Email/SMS → klient: "Kontrakt czeka na podpis"
  ├─ Klient w portalu: Draw Signature lub E-Acceptance
  ├─ Po podpisaniu: signer_name + signer_ip + signed_at
  └─ Auto notify → admin: "Kontrakt podpisany"

WON
  ├─ Mark as Won → won_at
  ├─ Convert → Project (szablon → auto-fazy)
  ├─ create_portal_access → PortalInviteMail
  ├─ Faktura zaliczkowa (50%) → sent → InvoiceSentMail
  └─ Kickoff call

PROJECT PHASES
  ├─ pending → in_progress → completed
  ├─ Wiadomości przez portal (ProjectMessage)
  └─ project.status_changed → ProjectStatusMail → klient

INVOICE
  ├─ sent → InvoiceSentMail → klient
  ├─ overdue → reminder (wymaga schedulera)
  └─ paid → confirmation + notify admin

DONE
  ├─ Projekt → status: completed
  ├─ Faktura końcowa (50%)
  ├─ ProjectStatusMail z danymi dostępowymi
  └─ Prośba o referencje
```

---

## Scenariusze do przetestowania — v2

| # | Scenariusz | Co testujemy |
|---|---|---|
| T1 | Dodaj nowego leada ręcznie | Formularz, walidacja, auto SMS/email |
| T2 | Przesuń lead przez wszystkie etapy pipeline | Zmiana stage, trigger `lead.stage_changed`, automatyzacje |
| T3 | Wyślij e-mail z szablonu z leada | Modal email, zmienne, `ClientEmailMail` |
| T4 | Wyślij SMS z leada | Twilio, `SmsTemplate`, char counter |
| T5 | Utwórz i wyślij Quote → klient akceptuje | `quote.sent`, `quote.accepted`, portal Accept/Reject |
| T6 | Utwórz kontrakt z szablonu | Interpolacja placeholderów `{{client.xxx}}`, `{{legal.xxx}}` |
| T7 | Wyślij kontrakt do portalu → klient podpisuje (pad) | `contract.sent`, `contract.signed`, `signature_data`, `signer_ip` |
| T8 | Klient podpisuje kontrakt (checkbox e-acceptance) | Brak `signature_data`, tylko `signer_name` + `signer_ip` |
| T9 | Ustaw leada jako Won → stwórz projekt z szablonu | Konwersja, `ProjectTemplate`, auto-fazy |
| T10 | `create_portal_access` → `PortalInviteMail` | Konto klienta, rola `client`, email z hasłem |
| T11 | Wystaw fakturę zaliczkową → wyślij | `InvoiceSentMail`, portal `/invoices` |
| T12 | Faktura zaległa — test `isOverdue()` | `due_date` w przeszłości, badge `overdue` |
| T13 | Stwórz regułę automation z delay | `ProcessAutomationJob`, kolejka, log |
| T14 | Lead → Lost → tag re-engagement | `lost_reason`, `add_tag`, email zamykający |
| T15 | Przesuń projekt przez wszystkie fazy → ukończ | Fazy, `project.status_changed`, `ProjectStatusMail` |
| T16 | Wiadomości w portalu projektu | `ProjectMessage`, `read_at`, widok admin + klient |

---

*Dokument zaktualizowany: 24 marca 2026 — wersja 2.0*

