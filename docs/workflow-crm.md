# CRM Workflow — WebsiteExpert
## Od leada do odbioru projektu przez klienta

> Dokument opisuje pełny przepływ pracy w systemie CRM — od momentu pojawienia się leada,
> przez wszystkie etapy lejka sprzedażowego, realizację projektu, fakturowanie, aż po
> finalne zamknięcie i odbiór przez klienta. Służy jako podstawa do testowania aplikacji.

---

## Przegląd lejka sprzedażowego

```
[New Lead] → [Contacted] → [Proposal Sent] → [Negotiation] → [Won] ──→ PROJECT
                                                                ↓
                                                             [Lost]
```

---

## ETAP 1 — New Lead

**Opis:** Lead pojawia się w systemie — może to być formularz ze strony, telefon,  
polecenie lub ręczne dodanie przez marketera/managera.

### Akcje w CRM

1. **Dodaj leada** (`Leads → New Lead`)
   - Uzupełnij: tytuł leada, klient (istniejący lub nowy), typ usługi, szacowana wartość
   - Przypisz właściciela (assigned to)
   - Ustaw oczekiwaną datę zamknięcia (`Expected Close`)
2. **Uzupełnij profil klienta** (`Clients → Client record`)
   - Imię i nazwisko osoby kontaktowej
   - Telefon (wymagany do SMS), e-mail
   - Nazwa firmy
3. **Ustaw stage:** `New Lead`

### Checklist (system automatycznie sprawdza)
- [ ] Zapisane dane kontaktowe (`has_client`)
- [ ] Potwierdzony budżet / wartość leada (`has_value`)
- [ ] Zidentyfikowany typ projektu (`has_calculator_data`)
- [ ] Lead przypisany do właściciela (`has_assignee`)

### Automatyzacje (do skonfigurowania w Marketing → Automation Rules)
| Trigger | Akcja | Przykład |
|---|---|---|
| `lead.created` | `send_sms` | SMS do klienta: "Dziękujemy za kontakt, odezwiemy się wkrótce" |
| `lead.created` | `notify_admin` | E-mail do managera: "Nowy lead: {{lead_title}}" |

### Cel etapu
Kwalifikacja leada — upewnij się, że warto poświęcić czas na dalszy kontakt.

---

## ETAP 2 — Contacted

**Opis:** Nawiązano pierwszy kontakt z klientem. Cel: poznać wymagania i umówić rozmowę.

### Akcje w CRM

1. **Zmień stage** na `Contacted`
2. **Wyślij e-mail powitalny** (`Send Email` na stronie leada)
   - Przedstaw firmę
   - Zaproponuj termin rozmowy odkrywczej
3. **Zanotuj ustalenia** (Notes / Activity log na stronie leada)
4. **Zaktualizuj dane leada** — wpisz szczegóły wymagań w opisie / notes

### Checklist
- [ ] Wysłany e-mail powitalny (`email_sent`)
- [ ] Umówiona rozmowa discovery
- [ ] Zakwalifikowane wymagania i cele projektu
- [ ] Potwierdzony kontakt z osobą decyzyjną (`has_contact`)

### Automatyzacje
| Trigger | Akcja | Przykład |
|---|---|---|
| `lead.stage_changed` (→ Contacted) | `send_email` | E-mail: "Dzień dobry, skontaktujemy się w ciągu 24h" |
| `lead.stage_changed` (→ Contacted) | `send_sms` | SMS: "Cześć {{client_name}}, oddzwonimy dziś!" |

### Cel etapu
Przeprowadzenie rozmowy kwalifikacyjnej i zebranie brief'u do wyceny.

---

## ETAP 3 — Proposal Sent

**Opis:** Przygotowanie i wysłanie oferty/wyceny do klienta.

### Akcje w CRM

1. **Zmień stage** na `Proposal Sent`
2. **Skorzystaj z Proposal Builder** (przycisk na stronie leada)
   - Wybierz typ projektu, dodaj pozycje wyceny
   - Ustaw wartość leada (`value`) na kwotę z wyceny
3. **Wyślij propozycję e-mailem** (`Send Email` → dołącz PDF lub link)
4. **Ustaw follow-up reminder** w `Expected Close` lub jako notatkę

### Checklist
- [ ] Przygotowana spersonalizowana oferta
- [ ] Oferta zweryfikowana z zespołem
- [ ] Oferta wysłana do klienta (`email_sent`)
- [ ] Follow-up zaplanowany na +3 dni

### Automatyzacje
| Trigger | Akcja | Przykład |
|---|---|---|
| `lead.stage_changed` (→ Proposal Sent) | `send_email` | "Przesyłam Państwu ofertę na {{lead_title}}" |
| `lead.stage_changed` (→ Proposal Sent) | `delay_minutes` + `send_sms` | Po 3 dniach: SMS "Czy dotarła Pani/Pana nasza oferta?" |

### Cel etapu
Klient zapoznaje się z ofertą. Oczekuj odpowiedzi lub inicjuj follow-up.

---

## ETAP 4 — Negotiation

**Opis:** Klient wyraził zainteresowanie. Negocjacje warunków, zakresu i płatności.

### Akcje w CRM

1. **Zmień stage** na `Negotiation`
2. **Zaktualizuj notes** na podstawie rozmów — co klient chce zmienić
3. **Zaktualizuj wartość leada** jeśli zakres się zmienił
4. **Ustaw `Expected Close`** na realną datę podpisania umowy
5. **Odnotuj uzgodnione warunki płatności** (np. 50% zaliczka, 50% po odbiorze)

### Checklist
- [ ] Omówione zmiany i rewizje oferty
- [ ] Potwierdzony harmonogram projektu (`has_expected_close`)
- [ ] Uzgodnione warunki płatności
- [ ] Podpisana umowa lub wpłacona zaliczka

### Cel etapu
Zamknięcie leada jako `Won` po podpisaniu umowy i/lub wpłacie zaliczki.

---

## ETAP 5A — Won (Wygrany Lead)

**Opis:** Klient zaakceptował ofertę. Czas uruchomić projekt.

### Akcje w CRM

1. **Zmień stage** na `Won` (lub użyj przycisku `Mark as Won` → status = `won`)
2. **Utwórz projekt** (`Convert to Project` na stronie leada)
   - Wybierz szablon projektu (Business Card / E-Commerce / Web Application)
   - Ustaw datę startu i deadline
   - Przypisz team (developer, designer)
3. **Wystaw fakturę zaliczkową** (np. 50%)
   - `Projects → Invoices → New Invoice`
   - Pozycja: "Zaliczka — {{lead_title}}"
4. **Wyślij fakturę** (`Send Invoice` — e-mailem)
5. **Przeprowadź Kickoff Call** i zanotuj ustalenia

### Checklist
- [ ] Lead przekształcony w projekt (`has_project`)
- [ ] Umówiony kickoff call
- [ ] Wysłane materiały onboardingowe / dostępy (`email_sent`)

### Automatyzacje
| Trigger | Akcja | Przykład |
|---|---|---|
| `lead.stage_changed` (→ Won) | `send_email` | "Gratulacje! Zaczynamy projekt 🎉 — kickoff w piątek o 10:00" |
| `project.created` | `notify_admin` | "Nowy projekt: {{project_name}} przypisany do {{assigned_name}}" |
| `invoice.sent` | `send_email` | Automatyczne powiadomienie o fakturze do klienta |

---

## ETAP 5B — Lost (Przegrany Lead)

**Opis:** Klient odmówił lub nie odpowiedział po wielu follow-upach.

### Akcje w CRM

1. **Zmień status** na `Lost` (przycisk `Mark as Lost`)
2. **Wpisz powód** w notes (cena, czas, konkurencja, brak budżetu)
3. **Wyślij grzeczną wiadomość zamykającą** (`Send Email`)
4. **Opcjonalnie:** oznacz klienta tagiem "re-engagement" do przyszłych kampanii

### Checklist
- [ ] Odnotowany powód przegranej
- [ ] Wysłany e-mail zamykający (`email_sent`)
- [ ] Klient dodany do listy re-engagement

---

## ETAP 6 — Realizacja Projektu

Po konwersji leada do projektu, praca odbywa się w zakładce `Projects`.

### 6A — Wizytówka (Business Card Website)

| # | Faza | Opis |
|---|---|---|
| 1 | **Discovery & Brief** | Zebranie treści, logotypu, kolorów, referencji od klienta |
| 2 | **Design Mockups** | Projektowanie makiet w Figma — 2 propozycje layoutu |
| 3 | **Development** | Kodowanie strony (HTML/CSS/Laravel/WordPress) |
| 4 | **Content Integration** | Wgranie tekstów, zdjęć, optymalizacja |
| 5 | **Testing & QA** | Testy na urządzeniach mobilnych, przeglądarkach, GTMetrix |
| 6 | **Launch & Handover** | Deploy na produkcję, przekazanie dostępów, szkolenie |

**Akcje CRM per faza:**
- Zmień fazę projektu gdy praca jest ukończona
- Wyślij klientowi aktualizację statusu e-mailem / SMS
- Zbierz feedback po Design Mockups przed przejściem do Development

---

### 6B — E-Commerce Store

| # | Faza | Opis |
|---|---|---|
| 1 | **Discovery & Strategy** | Analiza konkurencji, strategia UX, dobór platformy |
| 2 | **UX / Wireframes** | Szkice ścieżki zakupowej, layout kategorii i produktu |
| 3 | **Design** | Projekt graficzny w Figma, identyfikacja wizualna sklepu |
| 4 | **Development** | Wdrożenie szablonu, konfiguracja CMS / WooCommerce |
| 5 | **Product Import** | Import produktów, zdjęć, opisów, kategorii |
| 6 | **Payment Integration** | Konfiguracja bramek płatności (Przelewy24, Stripe, PayU) |
| 7 | **Testing & QA** | Testy całej ścieżki zakupowej, bezpieczeństwo |
| 8 | **SEO & Analytics Setup** | Google Analytics, Search Console, sitemap, meta tagi |
| 9 | **Launch** | Deploy, DNS, monitoring, brief szkoleniowy |

---

### 6C — Web Application

| # | Faza | Opis |
|---|---|---|
| 1 | **Discovery & Requirements** | Spisanie wymagań funkcjonalnych i niefunkcjonalnych |
| 2 | **System Architecture** | Projekt bazy danych, API design, schemat architektury |
| 3 | **UI/UX Design** | Prototypy Figma, design system |
| 4 | **Backend Development** | Laravel API, modele, logika biznesowa |
| 5 | **Frontend Development** | React / Inertia / Blade, komponenty |
| 6 | **Integration & API** | Integracje zewnętrzne (płatności, SMS, e-mail, itp.) |
| 7 | **Testing & QA** | Testy jednostkowe, E2E, penetracyjne |
| 8 | **Deployment** | CI/CD, serwer produkcyjny, SSL, backup |
| 9 | **Documentation** | Dokumentacja techniczna i użytkownika |

---

## ETAP 7 — Fakturowanie

### Schemat faktur przykładowy (projekt standard)
```
Faktura 1 — Zaliczka (50%)    →  Wystawiana przy starcie projektu (Won)
Faktura 2 — Płatność końcowa (50%)  →  Wystawiana przy odbiorze
```

### Akcje w CRM

1. **Utwórz fakturę:** `Projects → [Projekt] → Invoices → New Invoice`
2. **Dodaj pozycje** (usługa, godziny lub ryczałt)
3. **Wyślij fakturę** klientowi (`Send Invoice`) — system wyśle e-mail z PDF
4. **Odznacz jako "Paid"** gdy wpłata przychodzi
5. **Śledź zaległości** — faktury po terminie widoczne w dashboardzie

### Automatyzacje fakturowania
| Trigger | Akcja |
|---|---|
| `invoice.sent` | E-mail do klienta z informacją o fakturze |
| `invoice.overdue` | SMS/e-mail przypomnienie o zaległej fakturze |
| `invoice.paid` | E-mail potwierdzenie / notify_admin |

---

## ETAP 8 — Finalizacja i Odbiór

**Opis:** Projekt gotowy do prezentacji klientowi.

### Akcje w CRM

1. **Przesuń projekt do ostatniej fazy** (np. `Launch & Handover` / `Deployment`)
2. **Przeprowadź sesję odbioru** z klientem (screen share lub spotkanie)
3. **Zbierz podpis odbioru** (PDF lub e-mail potwierdzający acceptance)
4. **Wystaw fakturę końcową** (pozostałe 50%)
5. **Zmień status projektu** na `Completed`
6. **Wyślij e-mail podsumowujący** z:
   - Danymi dostępowymi (hosting, CMS, etc.)
   - Instrukcją obsługi
   - Informacją o gwarancji / support package
7. **Poproś o referencje** (review Google, LinkedIn recommendation)

### Opcjonalnie — po zamknięciu
- Dodaj klienta do segmentu "Aktywni klienci" (tagi w CRM)
- Utwórz nowego leada dla kolejnego projektu (upsell)
- Zaplanuj check-in po 30/60 dniach

---

## Podsumowanie — mapa akcji CRM

```
LEAD CREATED
  ├─ Auto SMS: "Dziękujemy za kontakt"
  ├─ Auto Email: notify admin
  └─ Checklist: dane, budżet, typ, owner

CONTACTED
  ├─ Email powitalny (ręcznie lub auto)
  └─ Checklist: discovery call, kwalifikacja

PROPOSAL SENT
  ├─ Proposal Builder → PDF
  ├─ Email z ofertą
  └─ Follow-up: SMS po 3 dniach

NEGOTIATION
  ├─ Notes z ustaleniami
  └─ Podpisana umowa + zaliczka

WON
  ├─ Convert → Project (wybierz template)
  ├─ Faktura zaliczkowa 50%
  ├─ Auto Email: kickoff info
  └─ Projekt → fazy → tasks

  PROJECT PHASES
    ├─ Discovery → Design → Development
    ├─ QA → Launch
    └─ Per faza: update status, email do klienta

INVOICE
  ├─ send: auto email
  ├─ overdue: auto SMS/email reminder
  └─ paid: auto notify + confirmation

DONE
  ├─ Faktura końcowa 50%
  ├─ Project status = Completed
  ├─ Email: dostępy + instrukcje
  └─ Prośba o referencje
```

---

## Scenariusze do przetestowania

| # | Scenariusz | Co testujemy |
|---|---|---|
| T1 | Dodaj nowego leada ręcznie | Formularz, walidacja, auto SMS/email |
| T2 | Przesuń lead przez wszystkie etapy | Zmiana stage, checklisty, automaty |
| T3 | Wyślij e-mail z szablonu z leada | Modal email, zmienne, log aktywności |
| T4 | Wyślij SMS z leada | Modal SMS, template, char counter, log |
| T5 | Ustaw leada jako Won → utwórz projekt | Konwersja, template projektu, fazy |
| T6 | Wystaw i wyślij fakturę | PDF, e-mail, status paid/overdue |
| T7 | Stwórz regułę automation | Trigger + akcja, delay, warunek stage |
| T8 | Automation: SMS przy zmianie stage | ProcessAutomationJob, kolejka, log |
| T9 | Lead → Lost → re-engagement | Status, notes, email zamykający |
| T10 | Przejście przez wszystkie fazy projektu | Update fazy, notes, finalna faktura |

---

*Dokument wygenerowany: 22 marca 2026 — wersja 1.0*
