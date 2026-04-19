# Strony wizytówkowe — Brief do propozycji
> Service: brochure-websites
> Market: PL
> Brief Type: Proposal Input
> Status: Wersja robocza
> Last Updated: 2026-04-17
> Source Anchors: ServiceItemSeeder.php, skrypt-sprzedazowy.md

---

## Cel
Zebrać wszystkie szczegóły potrzebne do napisania propozycji w stałej cenie: potwierdzony zakres, właścicielstwo treści, kierunek projektowy, harmonogram, możliwości upsellowe i otwarte ryzyka.

---

## Kontekst klienta
| Pole | Odpowiedź |
|---|---|
| Nazwa klienta | `[client_name]` |
| Adres obecnej strony | `[website_url]` |
| Branża / grupa docelowa | `[industry]` |
| Główny kontakt | `[contact_name]`, `[email]`, `[phone]` |
| Potwierdzony decydent | `[name]` |
| Uzgodniony zakres budżetu | `[budget]` |
| Twardy termin launchu | `[launch_date]` |
| Data briefu | `[brief_date]` |

---

## Dopasowanie do oferty
- Usługa: **Strona wizytówkowa** (slug: `brochure-websites`)
- Dopasowanie potwierdzone: Tak / Warunkowo / Do potwierdzenia
- Kluczowa wartość dla klienta: `[np. więcej zapytań, rebranding, pierwsza strona]`

---

## Zakres i granice

### Potwierdzone podstrony
| Podstrona | Właściciel treści | Status |
|---|---|---|
| Strona główna | `[klient / WE]` | `[gotowe / szkic / potrzebne]` |
| O nas | `[klient / WE]` | `[gotowe / szkic / potrzebne]` |
| Usługi | `[klient / WE]` | `[gotowe / szkic / potrzebne]` |
| Kontakt | `[klient / WE]` | `[gotowe / szkic / potrzebne]` |
| `[Podstrona 5]` | `[klient / WE]` | `[gotowe / szkic / potrzebne]` |

**Łączna liczba uzgodnionych podstron:** `[n]`

### Kierunek projektowy
- Referencje stylu / mood board: `[URL lub opis]`
- Kolory: `[dostarczone / do ustalenia]`
- Logo: `[dostarczone / do ustalenia / potrzebne nowe logo]`
- Fotografia: `[klient dostarcza / stock / sesja zdjęciowa]`

### Wymagania techniczne
- [ ] CMS (wliczony)
- [ ] Formularz kontaktowy z powiadomieniami e-mail
- [ ] SSL (wliczony)
- [ ] Konfiguracja Google Analytics / GA4
- [ ] Baner zgody na cookies
- [ ] Konfiguracja domeny
- [ ] Inne: `[podaj]`

### Poza zakresem
- Brak e-commerce ani bramki płatności
- Brak kont użytkowników ani systemu logowania
- Brak silnika rezerwacji
- Podstrony powyżej uzgodnionej liczby → proces change-request

---

## Pricing anchors
| Pozycja | Cena |
|---|---|
| Budowa strony (do `[n]` podstron) | `[£XXX]` |
| Copywriting dla `[n]` podstron (jeśli dotyczy) | `[+£XXX]` |
| Pozyskanie zdjęć / licencja stock | `[+£XXX]` |
| **Suma projektu** | `[£XXX]` |
| Dodatek: Plan opieki | `[+£149/mc]` |
| Dodatek: Retainer SEO | `[+£499/mc]` |

**Warunki płatności:** 50% z góry / 50% przy launchu (lub wg umowy).

---

## Kamienie milowe
| Kamień milowy | Szacunkowa data |
|---|---|
| Podpisana umowa + zaliczka | `[data]` |
| Deadline treści (klient) | `[data]` |
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
| Plan opieki (`maintenance`) | £149/mc | `[zainteresowany / odrzucony / TBD]` |
| Retainer SEO (`seo`) | od £499/mc | `[zainteresowany / odrzucony / TBD]` |
| Copywriting (`content`) | od £199/mc | `[zainteresowany / odrzucony / TBD]` |
| Audyt bezpieczeństwa (`audits`) | £299 jednorazowo | `[zainteresowany / odrzucony / TBD]` |
| Google Ads (`google-ads`) | od £399/mc | `[zainteresowany / odrzucony / TBD]` |

---

## Ryzyka i zależności
- **Deadline treści** — jeśli klient przekroczy termin dostarczenia treści o więcej niż 7 dni, termin launchu przesuwa się proporcjonalnie
- **Zasoby brandingowe** — brak logo/kolorów przed startem projektowania opóźnia fazę designu
- **Zatwierdzenia zewnętrzne** — dostęp do rejestratora domeny i konta hostingowego musi być dostarczony przed migracją
- **Czas reakcji na feedback** — zatwierdzenie projektu oczekiwane w ciągu 5 dni roboczych; więcej rund = dodatkowy koszt

---

## Założenia
- Wszystkie treści (teksty, zdjęcia, zasoby brandingowe) dostarczone przed uzgodnionym terminem
- W cenie zawarte maksymalnie 2 rundy poprawek projektu graficznego
- Hosting nie jest wliczony, chyba że określono inaczej (patrz `maintenance` — zarządzany hosting od £29/mc)
- Błędy lub żądania zmian po 30 dniach są fakturowane osobno

---

## Otwarte pytania
- [ ] Czy wymagany jest zarządzany hosting? (Patrz plan `maintenance`)
- [ ] Czy potwierdzono wymóg zgody na cookies / RODO?
- [ ] Czy są integracje zewnętrzne (CRM, rezerwacje, live chat)?
- [ ] Czy klient wymaga szkolenia z CMS?
- [ ] Czy potrzebne są strony wymagane prawnie (polityka prywatności, regulamin)?

---

## Rekomendowany następny krok
1. Sfinalizuj listę podstron i potwierdź właścicielstwo treści
2. Przygotuj dokument propozycji ze stałą ceną i harmonogramem kamieni milowych
3. Wyślij do zatwierdzenia klientowi z załączoną fakturą zaliczkową (50%)
4. Zaplanuj kick-off call w ciągu 48 godzin od otrzymania zaliczki
