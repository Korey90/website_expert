# Sklepy e-commerce — Brief kwalifikacyjny
> Service: ecommerce
> Market: PL
> Brief Type: Qualification
> Status: Wersja robocza
> Last Updated: 2026-04-17
> Source Anchors: ServiceItemSeeder.php, skrypt-sprzedazowy.md

---

## Cel
Określić, czy lead ma budżet, gotowość produktową i jasność operacyjną do realizacji projektu sklepu e-commerce — zanim poświęcimy czas na pełną propozycję.

---

## Kontekst klienta
| Pole | Odpowiedź |
|---|---|
| Nazwa firmy | `[client_name]` |
| Źródło leada | `[lead_source]` |
| Produkty / branża | `[products]` |
| Imię, nazwisko i stanowisko | `[contact_name]`, `[role]` |
| Decydent | Tak / Nie / Wspólnie |
| Potwierdzony budżet | `[budget]` |
| Planowany termin launchu | `[launch_date]` |
| Liczba SKU | `[n]` |

---

## Dopasowanie do oferty
| Kryterium | Zakwalifikowany | Niezakwalifikowany |
|---|---|---|
| Budżet | ≥ £2 999 potwierdzony lub domyślny | Poniżej £1 500 |
| Liczba SKU | 1–999 (WooCommerce) lub 1 000+ (headless) | Brak zdefiniowanych produktów lub tylko koncepcja |
| Bramka płatności | Stripe i/lub PayPal akceptowalne | Wymaga bramki bez API |
| Realizacja | Własna, 3PL lub dostawa cyfrowa | Nierozwiązana kwestia „ogarniemy to później" |
| Harmonogram | Minimum 6–8 tygodni dostępnych | Potrzeba w 2 tygodnie |
| Gotowość danych | Dane produktów w arkuszu lub istniejącej platformie | Brak zdjęć, opisów i cen |

---

## Kryteria kwalifikacji

### Wymagania obligatoryjne
- [ ] Budżet ≥ £2 999 zaakceptowany
- [ ] Istnieje przynajmniej szkicowy katalog produktów (nawet w arkuszu)
- [ ] Zidentyfikowany i dostępny decydent
- [ ] Konto Stripe lub PayPal, lub gotowość do jego założenia
- [ ] Minimum 6 tygodni do launchu

### Czynniki podnoszące jakość dealu
- [ ] Istniejący sklep do migracji (zwiększa pilność i dostępność danych)
- [ ] Gotowa fotografia produktów
- [ ] Zainteresowanie Google Shopping lub Meta Ads przy launchu
- [ ] Zainteresowanie retainerem SEO lub planem opieki
- [ ] Jasny cel konwersji (np. 100 zamówień miesięcznie w ciągu 90 dni)

### Czerwone flagi
- 🚨 „Mam 5 000 produktów, budżet to £1 500"
- 🚨 „Produkty dodam po launchu — teraz mamy tylko nazwy"
- 🚨 Brak konta Stripe, brak rejestracji VAT, brak konfiguracji wysyłki
- 🚨 „Potrzebuję Klarna, AfterPay i własnego systemu lojalnościowego za £3k"
- 🚨 Decyzja przez zarząd bez harmonogramu
- 🚨 Oczekiwanie gwarantowanych wyników sprzedażowych po launchu

---

## Zakres i granice
**Minimalny zakres e-commerce (£2 999):** WooCommerce, do 50 produktów, Stripe + PayPal, projekt mobile-first, podstawowe SEO, wsparcie 30 dni.

**Zakres wymagający ponownej kwalifikacji:**
- 200+ produktów na launch → dodaj usługę uploadu produktów
- Wiele walut lub języków → +£500–£1 500 zależnie od zakresu
- Integracja z niestandardowym ERP → wymagane osobne scopowanie techniczne
- Subskrypcja / billing cykliczny → wymagany przegląd platformy i złożoności

---

## Pricing anchors
| Scenariusz | Cena |
|---|---|
| WooCommerce do 50 produktów, klient dostarcza dane | **£2 999** |
| WooCommerce 50–250 produktów, standardowy projekt | **£4 000–£6 000** |
| Headless React storefront, 500+ SKU | **od £8 000** |
| Migracja danych produktów z istniejącej platformy | **od £500** |
| Dodatek: Feed Google Shopping | **od £300** |
| Dodatek: Plan opieki | **+£149/mc** |
| Dodatek: Retainer SEO | **+£499/mc** |

---

## Ryzyka i zależności
- Jakość danych produktów to największe ryzyko dla harmonogramu — musi być potwierdzona z góry
- Zatwierdzenie konta Stripe/PayPal może trwać do 5 dni roboczych
- Strony prawne (polityka zwrotów, polityka wysyłki, polityka prywatności) są odpowiedzialnością klienta
- Rejestracja VAT i prawidłowa konfiguracja podatku muszą być potwierdzone przed launchem

---

## Założenia
- Klient ma lub niezwłocznie otworzy konto Stripe/PayPal
- Zdjęcia produktów istnieją lub przydzielono budżet na fotografię
- Strefy i stawki wysyłki są zdefiniowane przed rozpoczęciem developmentu

---

## Otwarte pytania
- [ ] WooCommerce czy headless — czy rekomendacja została już przedstawiona?
- [ ] Czy klient miał oferty od innych agencji e-commerce?
- [ ] Czy istnieją wymogi compliance specyficzne dla branży (żywność, suplementy, treści dla dorosłych)?
- [ ] Czy planowany jest program afiliacyjny lub referralowy?
- [ ] Kto zarządza magazynem po launchu — klient czy 3PL?

---

## Rekomendowany następny krok
- **Zakwalifikowany** → Przejdź do Proposal Input; poproś o próbkę danych produktów i dostęp do platformy
- **Warunkowo zakwalifikowany** → Potwierdź gotowość produktów i bramkę płatności; oceń ponownie za 2 tygodnie
- **Niezakwalifikowany** → Odmów lub odłóż; odnotuj potencjał na `brochure-websites` lub `web-applications` przy zmianie zakresu
