# Aplikacje internetowe — Brief kwalifikacyjny
> Service: web-applications
> Market: PL
> Brief Type: Qualification
> Status: Wersja robocza
> Last Updated: 2026-04-17
> Source Anchors: ServiceItemSeeder.php, skrypt-sprzedazowy.md

---

## Cel
Określić, czy lead ma realny, zdefiniowany problem, wystarczający budżet i dojrzałość operacyjną do podjęcia projektu dedykowanej aplikacji webowej — zanim zainwestujemy w płatną fazę discovery.

---

## Kontekst klienta
| Pole | Odpowiedź |
|---|---|
| Nazwa firmy | `[client_name]` |
| Źródło leada | `[lead_source]` |
| Typ aplikacji | `[SaaS / portal / rezerwacje / narzędzie wewnętrzne / inne]` |
| Imię, nazwisko i stanowisko | `[contact_name]`, `[role]` |
| Decydent | Tak / Nie / Wspólnie |
| Potwierdzony budżet | `[budget]` |
| Pilność / deadline | `[deadline]` |
| Istniejący kod | Tak / Nie / Częściowo |

---

## Dopasowanie do oferty
| Kryterium | Zakwalifikowany | Niezakwalifikowany |
|---|---|---|
| Budżet | ≥ £5 999 (lub £599 na discovery) zaakceptowany | Poniżej £3 000 na złożoną aplikację |
| Definicja problemu | Jasny problem ze znanymi użytkownikami i wynikami | „Mam pomysł, nie wiem jeszcze jaki" |
| Decydent | Dostępny i technicznie zorientowany | Nieobecny / zlecony na zewnątrz |
| Harmonogram | Minimum 8 tygodni dostępnych | „Potrzebuję tego za 2 tygodnie" |
| Dopasowanie gotowego oprogramowania | Brak odpowiedniego SaaS na rynku | Wtyczka WordPress lub Zapier mogą rozwiązać problem |
| Rozumienie techniczne | Klient rozumie, że custom = wyższy koszt | Oczekuje aplikacji custom za cenę strony wizytówkowej |

---

## Kryteria kwalifikacji

### Wymagania obligatoryjne
- [ ] Budżet ≥ £5 999 zaakceptowany (lub £599 na płatną fazę discovery)
- [ ] Problem jest konkretny: można opisać workflow do zastąpienia lub zbudowania
- [ ] Zidentyfikowany i zaangażowany decydent
- [ ] Zaakceptowany harmonogram minimum 8–12 tygodni
- [ ] Brak oczekiwania gwarantowanych wyników lub gwarancji przychodów

### Czynniki podnoszące jakość dealu
- [ ] Wcześniejsza nieudana próba z gotowym oprogramowaniem (tworzy pilność)
- [ ] Dostępny wewnętrzny zasób techniczny do przejęcia
- [ ] Jasny model monetyzacji (szczególnie dla SaaS)
- [ ] Istniejące wireframes, user stories lub dokument wymagań
- [ ] Budżet na retainer utrzymania po launchu

### Czerwone flagi
- 🚨 „Chcę klon LinkedIna / Airbnb za £10k"
- 🚨 „To jest proste — nie powinno zająć więcej niż tydzień"
- 🚨 Brak zdefiniowanych użytkowników i wyników
- 🚨 „Funkcje dodam po zbudowaniu"
- 🚨 Klient chce własności całego IP, ale też oczekuje bezpłatnego wsparcia ongoing
- 🚨 Oczekuje stałej ceny bez żadnej fazy discovery

---

## Zakres i granice
**Punkt wejścia:** Płatna faza discovery od £599 — nie podlega negocjacji dla żadnej dedykowanej aplikacji.

**Deliverables discovery:** specyfikacja techniczna, diagramy przepływu użytkownika, przegląd architektury i stałocenowa oferta na build.

**Zakres wymagający specjalistycznej oceny:**
- Wymagania dotyczące natywnej aplikacji mobilnej (iOS/Android) → poza obecną usługą
- Funkcje AI/ML → wymaga specjalistycznego podwykonawcy
- Real-time wideo / audio (WebRTC) → wymaga osobnego scopowania
- Rezydencja danych lub zgodność danych zdrowotnych → trigger dla Legal Compliance Agent

---

## Pricing anchors
| Scenariusz | Cena |
|---|---|
| Płatna faza discovery | **£599** |
| Mała dobrze zdefiniowana aplikacja | **od £5 999** |
| Średnia złożoność (role, integracje, przepływy) | **od £15 000** |
| Platforma SaaS (multi-tenancy, billing, admin) | **od £25 000** |
| Audyt kodu przed przejęciem | **£299 (`audits`)** |
| Retainer utrzymania | **od £149/mc** |

---

## Ryzyka i zależności
- Niezdefiniowany zakres → stałocenowa wycena niemożliwa bez płatnej discovery
- Wielu decydentów z conflictującymi wizjami → faza discovery musi obejmować wszystkich kluczowych interesariuszy
- Integracje API zewnętrznych → muszą być weryfikowane jako technicznie możliwe przed wyceną
- Istniejący kod → może wymagać pełnego przepisania zależnie od jakości; audyt kodu (£299) zdecydowanie rekomendowany

---

## Założenia
- Płatna faza discovery (£599) jest sprzedawana jako pierwszy krok dla każdego złożonego lub niejasnego zakresu
- Klient jest świadomy, że cena buildu jest osobna i następuje po fazie discovery
- Utrzymanie po launchu jest scopowane osobno

---

## Otwarte pytania
- [ ] Czy klient porównuje nas do offshore developerów?
- [ ] Kto jest właścicielem finalnego kodu — klient czy współwłasność?
- [ ] Czy klient współpracował wcześniej z agencją developerską?
- [ ] Czy jest budżet na ciągłe sprinty po launchu?
- [ ] Czy są konkretne wymogi compliance (RODO, PCI-DSS, ISO 27001)?

---

## Rekomendowany następny krok
- **Zakwalifikowany** → Zaproponuj płatną fazę discovery (£599); zaplanuj 60-minutowy warsztat discovery
- **Warunkowo zakwalifikowany** → Zdefiniuj problem i zakres budżetu; wróć z doprecyzowanym briefem
- **Niezakwalifikowany** → Odmów uprzejmie; odnotuj czy prostsze rozwiązanie (`brochure-websites`, `ecommerce`) mogłoby pasować
