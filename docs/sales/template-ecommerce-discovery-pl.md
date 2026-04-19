# Sklepy e-commerce — Brief discovery
> Service: ecommerce
> Market: PL
> Brief Type: Discovery
> Status: Wersja robocza
> Last Updated: 2026-04-17
> Source Anchors: ServiceItemSeeder.php, skrypt-sprzedazowy.md, plan-kampanii.md

---

## Cel
Zrozumieć katalog produktów klienta, model sprzedaży, logistykę i obecny stack technologiczny, aby wycenić sklep e-commerce, który konwertuje od pierwszego dnia.

---

## Kontekst klienta
| Pole | Odpowiedź |
|---|---|
| Nazwa firmy | `[client_name]` |
| Branża / typ produktu | `[industry]` |
| Obecny kanał sprzedaży | `[strona / Etsy / Amazon / brak]` |
| Obecna platforma | `[WooCommerce / Shopify / Magento / brak]` |
| Przybliżona liczba SKU | `[n]` |
| Grupa docelowa | `[B2C / B2B / obydwoje]` |
| Główny cel | `[launch / migracja / przebudowa / rozbudowa]` |
| Termin decyzji | `[decision_deadline]` |
| Orientacyjny budżet | `[budget_indication]` |

---

## Dopasowanie do oferty
Sklep e-commerce od Website Expert pasuje firmom, które:
- Sprzedają fizyczne lub cyfrowe produkty online (lub planują zacząć)
- Mają 1–1 000 SKU (WooCommerce) lub 1 000+ SKU (headless React rekomendowany)
- Potrzebują płatności przez Stripe i/lub PayPal
- Chcą kontroli nad swoimi danymi (własna platforma vs. marketplace)

**Brak dopasowania gdy:** klient potrzebuje tylko strony lead-capture bez płatności → przekaż do `brochure-websites`. Złożony billing SaaS lub model marketplace → przekaż do `web-applications`.

---

## Przebieg discovery

### Produkty i katalog
> „Ile produktów sprzedajesz? Czy masz warianty (rozmiar, kolor, materiał)?"
> „Czy produkty są fizyczne, cyfrowe czy mieszane?"
> „Czy potrzebujesz subskrypcji / rozliczeń cyklicznych?"

### Stan obecny
> „Czy aktualnie sprzedajesz gdzieś? Co nie działa?"
> „Czy masz dane produktów w arkuszu / istniejącym systemie, które trzeba migrować?"

### Realizacja i logistyka
> „Jak obsługujesz wysyłkę? Stałe stawki, stawki przewoźnika czy odbiór własny?"
> „Czy wysyłasz za granicę? Czy potrzebna jest obsługa wielu walut?"

### Płatności
> „Które metody płatności potrzebujesz — Stripe, PayPal, Klarna, Apple Pay?"
> „Czy jesteś zarejestrowany do VAT? Jakieś kategorie zwolnione z VAT?"

### Cele biznesowe
> „Jak wygląda sukces w pierwszych 6 miesiącach — cel GMV, liczba zamówień, CAC?"
> „Czy planujesz prowadzić reklamy Google Shopping lub Meta równolegle ze sklepem?"

### Termin i pilność
> „Czy jest launch produktu, szczyt sezonowy lub kampania z twardym terminem?"
> „Kto zatwierdza finalny build — tylko Ty, czy cały zespół?"

### Budżet
> „Projekty e-commerce tego typu mieszczą się zazwyczaj od £2 999 za WooCommerce do £8 000+ za headless. Czy to pasuje do Twojego planowania?"

---

## Zakres i granice
**W zakresie (WooCommerce starter, od £2 999):**
- Konfiguracja WooCommerce na WordPress lub headless storefront
- Do 50 produktów (dodatkowe produkty za dopłatą)
- Integracja Stripe + PayPal
- Katalog produktów + zarządzanie magazynem
- E-maile odzyskiwania porzuconych koszyków
- Projekt mobile-first zoptymalizowany pod konwersję
- Podstawowe SEO
- 30 dni wsparcia po uruchomieniu

**Poza zakresem (wymaga osobnej wyceny / usługi):**
- Headless build 1 000+ SKU → wycena indywidualna
- Integracje z ERP lub systemem magazynowym
- Funkcjonalność marketplace wielosprzedawcowego
- Konfiguracja feedów Google Shopping / Meta Catalogue (do dodania)
- Ciągłe SEO → `seo` od £499/mc
- Zarządzanie reklamami płatnymi → `google-ads` lub `meta-ads`
- Opieka techniczna → `maintenance` od £149/mc

---

## Pricing anchors
| Poziom | Cena | Opis |
|---|---|---|
| WooCommerce Starter | od **£2 999** | Do 50 produktów, Stripe + PayPal, projekt mobile-first |
| WooCommerce Mid | od **£5 000** | Do 250 produktów, zaawansowane filtrowanie, integracje |
| Headless React | od **£8 000** | 500+ SKU, krytyczna wydajność, własny storefront |
| Migracja danych produktów | od **£500** | Z obecnej platformy (Shopify, Magento, WooCommerce) |

- Dodatek: Feed Google Shopping / Meta Catalogue — od £300 jednorazowo
- Dodatek: Retainer SEO — od £499/mc (`seo`)
- Dodatek: Google Ads — od £399/mc (`google-ads`)
- Dodatek: Meta Ads — od £349/mc (`meta-ads`)
- Dodatek: Opieka techniczna — od £149/mc (`maintenance`)

---

## Ryzyka i zależności
- **Jakość danych produktów** — brakujące zdjęcia, opisy lub ceny znacząco opóźniają go-live
- **Zatwierdzenie bramki płatności** — konto Stripe/PayPal musi być aktywne przed launchem; może trwać 1–5 dni
- **Konfiguracja VAT / podatku** — złożone reguły podatkowe (np. VAT od towarów cyfrowych, wiele regionów) wymagają osobnego scope'owania
- **Złożoność migracji** — migracja z Shopify lub Magento często ujawnia problemy z jakością danych
- **Reguły wysyłki** — złożone stawki obliczane przez przewoźnika wymagają dostępu do API kuriera

---

## Założenia
- Klient ma lub otworzy konto Stripe/PayPal przed launchem
- Zdjęcia produktów dostarczone przez klienta (lub stock photos uzgodnione osobno)
- Stawki i strefy wysyłki zdefiniowane przez klienta przed startem developmentu
- Strony prawne (zwroty, polityka wysyłki, polityka prywatności) dostarczone przez klienta lub przygotowane przez klienta (**domyślnie nie wliczone**)

---

## Otwarte pytania
- [ ] WooCommerce czy headless? (Zależy od liczby SKU i wymagań wydajnościowych)
- [ ] Czy wymagana jest migracja danych produktów z istniejącej platformy?
- [ ] Czy są specjalne reguły cenowe (ceny handlowe, rabaty ilościowe, ceny dla członków)?
- [ ] Czy wymagana jest obsługa wielu walut lub języków przy launchu?
- [ ] Jaki model realizacji — własna wysyłka, 3PL, drop-shipping?
- [ ] Czy będą potrzebne feedy Google Shopping lub Meta Catalogue?

---

## Rekomendowany następny krok
1. Potwierdź liczbę SKU i wymaganie migracji
2. Uzgodnij platformę (WooCommerce vs. headless) i bramkę płatności
3. Przejdź do briefu Proposal Input; poproś o próbkę danych produktów i dostęp do obecnej strony
4. Upsell: Google Ads (`google-ads`) + retainer SEO (`seo`) do generowania ruchu od pierwszego dnia
