# Sklepy e-commerce — Brief do propozycji
> Service: ecommerce
> Market: PL
> Brief Type: Proposal Input
> Status: Wersja robocza
> Last Updated: 2026-04-17
> Source Anchors: ServiceItemSeeder.php, skrypt-sprzedazowy.md

---

## Cel
Zebrać wszystkie szczegóły scopingowe potrzebne do napisania propozycji e-commerce w stałej cenie: platforma, liczba SKU, konfiguracja płatności, migracja, kierunek projektowy, kamienie milowe i możliwości upsellowe.

---

## Kontekst klienta
| Pole | Odpowiedź |
|---|---|
| Nazwa klienta | `[client_name]` |
| Adres obecnego sklepu | `[store_url]` (lub „brak") |
| Branża / typ produktu | `[industry]` |
| Główny kontakt | `[contact_name]`, `[email]`, `[phone]` |
| Potwierdzony decydent | `[name]` |
| Uzgodniony zakres budżetu | `[budget]` |
| Twardy termin launchu | `[launch_date]` |
| Data briefu | `[brief_date]` |

---

## Dopasowanie do oferty
- Usługa: **Sklep e-commerce** (slug: `ecommerce`)
- Decyzja platformowa: WooCommerce / Headless React / Do ustalenia
- Dopasowanie potwierdzone: Tak / Warunkowo / Do potwierdzenia
- Kluczowa wartość dla klienta: `[np. nowy kanał przychodów, migracja z Shopify, skalowanie z Etsy]`

---

## Zakres i granice

### Katalog produktów
| Szczegół | Odpowiedź |
|---|---|
| Łączna liczba SKU przy launchu | `[n]` |
| Warianty produktów (rozmiar, kolor, itp.) | Tak / Nie / TBD |
| Fizyczne / cyfrowe / mieszane | `[typ]` |
| Produkty subskrypcyjne | Tak / Nie |
| Wymagana migracja danych produktów | Tak / Nie — z `[platforma]` |

### Płatności
| Szczegół | Odpowiedź |
|---|---|
| Stripe | Tak / Nie |
| PayPal | Tak / Nie |
| Klarna / BNPL | Tak / Nie |
| Apple Pay / Google Pay | Tak / Nie |
| Konfiguracja VAT | Standardowa / Zwolniona / Mieszana |
| Wiele walut | Tak / Nie — waluty: `[lista]` |

### Wysyłka i realizacja
| Szczegół | Odpowiedź |
|---|---|
| Model wysyłki | Stałe stawki / Wg przewoźnika / Darmowa / Mieszana |
| Strefy wysyłki | Tylko UK / EU / Międzynarodowa |
| Integracja API kuriera | Tak (`[kurier]`) / Nie |
| Odbiór własny | Tak / Nie |
| Integracja 3PL | Tak (`[nazwa 3PL]`) / Nie |

### Kierunek projektowy
- Referencje stylu: `[URL lub opis]`
- Logo i zasoby brandingowe: `[dostarczone / do ustalenia]`
- Fotografia: `[klient dostarcza / stock / sesja wymagana]`
- Kluczowe wymagania UX: `[np. mega menu, zaawansowane filtrowanie, lista życzeń]`

### Wymagania techniczne
- [ ] Google Analytics / GA4 + śledzenie e-commerce rozszerzone
- [ ] Feed Google Shopping
- [ ] Meta Pixel + Conversions API
- [ ] Baner zgody na cookies (RODO)
- [ ] CMS dla bloga / stron contentowych
- [ ] Obszar konta klienta
- [ ] Inne: `[podaj]`

### Poza zakresem
- Funkcjonalność marketplace wielosprzedawcowego
- Niestandardowa integracja ERP / systemu magazynowego (o ile nie określono powyżej)
- Silnik billing subskrypcji (o ile nie potwierdzono powyżej)
- Podstrony powyżej uzgodnionego zakresu → change request
- Treść stron prawnych (zwroty, prywatność) — odpowiedzialność klienta

---

## Pricing anchors
| Pozycja | Cena |
|---|---|
| Budowa platformy (WooCommerce / Headless) | `[£XXX]` |
| Upload / migracja danych produktów | `[+£XXX]` |
| Pozyskanie zdjęć / licencja stock | `[+£XXX]` |
| Konfiguracja feeda Google Shopping | `[+£XXX]` |
| **Suma projektu** | `[£XXX]` |
| Dodatek: Plan opieki | `[+£149/mc]` |
| Dodatek: Retainer SEO | `[+£499/mc]` |
| Dodatek: Zarządzanie Google Ads | `[+£399/mc]` |
| Dodatek: Zarządzanie Meta Ads | `[+£349/mc]` |

**Warunki płatności:** 50% z góry / 50% przy launchu (lub wg umowy).

---

## Kamienie milowe
| Kamień milowy | Szacunkowa data |
|---|---|
| Podpisana umowa + zaliczka | `[data]` |
| Dane produktów dostarczone przez klienta | `[data]` |
| Potwierdzenie aktywnej bramki płatności | `[data]` |
| Dostarczone koncepcje projektu | `[data]` |
| Zatwierdzenie projektu | `[data]` |
| Zakończenie developmentu | `[data]` |
| UAT / przegląd klienta | `[data]` |
| Launch | `[data]` |
| Koniec wsparcia 30-dniowego | `[data]` |

---

## Upselle i cross-selle
| Możliwość | Wartość | Status |
|---|---|---|
| Google Ads (`google-ads`) | od £399/mc | `[zainteresowany / odrzucony / TBD]` |
| Meta Ads (`meta-ads`) | od £349/mc | `[zainteresowany / odrzucony / TBD]` |
| Retainer SEO (`seo`) | od £499/mc | `[zainteresowany / odrzucony / TBD]` |
| Plan opieki (`maintenance`) | od £149/mc | `[zainteresowany / odrzucony / TBD]` |
| Konfiguracja feeda Google Shopping | od £300 | `[zainteresowany / odrzucony / TBD]` |
| Audyt bezpieczeństwa (`audits`) | £299 | `[zainteresowany / odrzucony / TBD]` |

---

## Ryzyka i zależności
- **Deadline danych produktów** — jeśli dane opóźnią się o 5+ dni, termin launchu przesuwa się proporcjonalnie; uwzględnij w umowie
- **Aktywacja konta Stripe/PayPal** — musi być aktywne przynajmniej 3 dni przed UAT
- **Strony prawne** — polityka zwrotów, wysyłki, prywatności to odpowiedzialność klienta; brak = brak launchu
- **Złożoność VAT** — stawki mieszane lub unijny VAT OSS wymagają przeglądu konfiguracji podatkowej przed launchem
- **Jakość danych migracji** — przeprowadź audyt danych przed zatwierdzeniem harmonogramu i ceny migracji

---

## Założenia
- Klient dostarcza czyste dane produktów (CSV/arkusz) do uzgodnionego terminu
- W cenie zawarte maksymalnie 2 rundy poprawek projektu graficznego
- Klient posiada domenę i zapewni dostęp w ciągu 48 godzin od podpisania umowy
- Błędy i żądania zmian po 30 dniach fakturowane osobno według uzgodnionej stawki

---

## Otwarte pytania
- [ ] Czy wymagany jest zarządzany hosting? (dodatek przez `maintenance`)
- [ ] Czy na stronach produktów potrzebne są branżowe certyfikaty lub etykiety zgodności?
- [ ] Czy jest wymaganie dotyczące systemu kodów rabatowych / polecających?
- [ ] Kto zarządza sklepem po launchu — klient czy WE?
- [ ] Czy są integracje zewnętrzne (CRM, ERP, oprogramowanie księgowe)?

---

## Rekomendowany następny krok
1. Potwierdź wybór platformy, liczbę SKU i zakres migracji
2. Poproś o próbkę danych produktów (5–10 produktów) do walidacji jakości danych
3. Przygotuj propozycję z fazowymi kamieniami milowymi jeśli migracja jest złożona
4. Dołącz podsumowanie upsell dla Google Ads + SEO w celu maksymalizacji ROI przy launchu
