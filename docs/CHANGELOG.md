# Changelog

Wszystkie istotne zmiany w projekcie WebsiteExpert.

---

## [Unreleased] — 2026-06-02

### Naprawiono
- Poprawki flow zamawiania nowej domeny (`097798c`)

---

## 2026-06-01

### Dodano
- **Moduł domen** — ukończenie pełnej funkcji rejestracji domen, wszystkie sprinty zakończone (`210df0a`)

---

## 2026-05-29

### Inne
- Eksperymenty z agentami Copilot (`00359b8`)

---

## 2026-05-17 — 2026-05-18

### Dodano
- **Kalendarz** — wdrożenie modułu kalendarza z synchronizacją historii aktywności Google Calendar (`28fd906`, `862f27c`, `7467f0c`)

### Inne
- Technical Debt Cleanup Sprint T1–T5 (`b055af7`)
- Aktualizacja konfiguracji agentów i skilli w projekcie (`28e0b0b`)

---

## 2026-04-14 — 2026-04-23

### Dodano
- Dedykowane widoki pod reklamy: `/contact/`, `/about-us` (`10379a6`)
- Dedykowany moduł nawigacji na froncie z pełnym panelem zarządzania; navbar usunięty z site-sections (`e14e5eb`)
- Moduł szczegółów oferty (`9a0ac29`)
- Moduł briefings (`ebb2b9f`)
- Moduł odświeżający `sitemap.xml` automatycznie (`3aec334`)
- Widoki front-end dla modułu services + admin UI (`e510843`)
- Moduł portfolio z widokami index (`ed8f58a`)

### Zmieniono
- Poprawki w edycji portfolio items — naprawiono bug z tagami i obrazem; dodano funkcje AI (`0d627df`)
- Poprawki automatyzacji badania jakości obsługi klienta (`15032b8`)
- Porządkowanie linków w menu admina (`f8a2382`)
- Rozbudowa `/services/*` i `/portfolio/*` — więcej szczegółów + tagi SEO (`ec62c74`)
- Poprawki treści CMS (`5cf7d98`)
- Poprawki widoku mobilnego na froncie, poprawki SEO (`3bfccbd`)
- Poprawki w site-sections > contact; zaktualizowano seeder pod nowego klienta (`8b41e98`)

### Naprawiono
- Fix: zapobieganie ponownemu wysyłaniu SMS/email przy retry'ach w kolejce (`1179965`)

### Wydajność
- Osiągnięto wynik 90+ we wszystkich testach Google PageSpeed na desktop/mobile (`6ae74c5`)
- SEO: osiągnięto wynik 100 pkt; dodano opisowe linki (`4c99654`)
- Poprawki dostępności; wynik 95+ pkt (`c7509b3`)

---

## 2026-04-09

### Zmieniono
- Funkcje AI + poprawki widoków mobile/desktop oraz trybów light/dark (`86315e9`)

---

## 2026-03-31 — 2026-04-03

### Dodano
- **SaaS faza 1** — wdrożenie multi-tenancy (`ef495ff`, `43bdc88`)

### Zmieniono
- Refaktoryzacja i poprawki z testami — zakończenie planu poprawek (`21da3c3`, `43cae7b`, `a5a2a66`)

---

## 2026-03-27

### Dodano
- UI + CRUD dla zarządzania uprawnieniami (Permissions) (`3d4eb6c`)
- System powiadomień — widget oraz CRUD (`9ecb5ba`)

---

## 2026-03-24 — 2026-03-26

### Dodano
- **Moduł kontraktów** wraz z szablonami (`05251e0`, `b07acb9`)
- Zarządzanie zgodami na komunikację w Portalu Klienta (`da0b642`)
- Integracja bramek płatności + historia płatności (`054f7df`)

### Zmieniono
- Przebudowa kalkulatora (front + admin); poprawione tłumaczenia i logika (`bb6bd70`)
- Poprawki sekcji frontowych od strony admina (`1ee4353`)
- Poprawki UI (`b0c6e80`, `4d26b84`)

---

## 2026-03-21 — 2026-03-22

### Dodano
- Szablony wiadomości SMS; ukończono integrację Twilio (SMS + email) (`c1f45e0`)
- Szablony projektów + automatyczne zadania na podstawie szablonu; redesign widoku `project.show` (`c0a044e`)
- Zwijanie/rozwijanie panelu bocznego; trigger z przypiętymi notatkami w belce (`c655a70`)
- Widoki index dla wszystkich modułów; rozbudowa `lead.show` (`69f7cd8`, `afc3fe5`)

### Zmieniono
- Poprawki redesign i logiki działu projektów; aktualizacje w Portalu Klienta (`52faaa8`)

---

## 2026-03-19 — 2026-03-20

### Dodano
- Integracja logowania z frontem; poprawki wyświetlania CMS Pages (`25f1072`)

### Naprawiono
- Poprawki flow po wysłaniu kalkulatora i formularza kontaktowego (`f05e1eb`)

---

## 2026-03-19 — Wersja inicjalna

### Dodano
- Wersja core projektu (`a04305c`)
- Połączenie sekcji hero z backendem przez Inertia; fix konfiguracji Tailwind v4 (`58ac577`)
- Pierwszy commit (`adfafae`)
