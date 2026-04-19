# Meta / Pixel Ads — Brief kwalifikacyjny
> Service: meta-ads
> Market: PL
> Brief Type: Qualification
> Status: Wersja robocza
> Last Updated: 2026-04-17
> Source Anchors: ServiceItemSeeder.php, skrypt-sprzedazowy.md

---

## Cel
Określić, czy lead ma obecność w social mediach, zasoby kreacyjne, budżet i profil grupy docelowej, aby prowadzić efektywne kampanie Meta Ads na Facebooku i Instagramie.

---

## Kontekst klienta
| Pole | Odpowiedź |
|---|---|
| Nazwa firmy | `[client_name]` |
| Adres strony | `[website_url]` |
| Źródło leada | `[lead_source]` |
| Branża | `[industry]` |
| Imię, nazwisko i stanowisko | `[contact_name]`, `[role]` |
| Decydent | Tak / Nie / Wspólnie |
| Dostępny miesięczny budżet reklamowy | `[£XXX/mc]` |
| Opłata za zarządzanie zaakceptowana | Tak / Nie |

---

## Dopasowanie do oferty
| Kryterium | Zakwalifikowany | Niezakwalifikowany |
|---|---|---|
| Budżet reklamowy | ≥ £300/mc potwierdzony | Poniżej £200/mc |
| Opłata za zarządzanie | ≥ £349/mc zaakceptowana | Chce zarządzanie wliczone w budżet reklamowy |
| Typ biznesu | Produkt B2C, usługa lub budowanie świadomości marki | Czyste B2B bez grupy konsumenckiej |
| Zasoby kreacyjne | Ma zdjęcia/wideo lub gotów zlecić | Brak wizualiów i odmawia inwestycji w kreację |
| Strony społecznościowe | Aktywny Fanpage / konto Instagram | Brak obecności w social media i niechęć do jej tworzenia |
| Mechanizm konwersji | Landing page, lead form lub strona produktu | Cel ruchu niejasny lub nieistniejący |
| Meta Pixel | Zainstalowany lub gotów zainstalować | Odmawia konfiguracji trackingu |

---

## Kryteria kwalifikacji

### Wymagania obligatoryjne
- [ ] Budżet reklamowy ≥ £300/mc (płatny do Meta, oddzielnie od opłaty za zarządzanie)
- [ ] Opłata za zarządzanie ≥ £349/mc zaakceptowana
- [ ] Aktywny Fanpage na Facebooku (Instagram opcjonalny, ale rekomendowany)
- [ ] Co najmniej jeden cel konwersji: landing page, strona produktu lub lead form
- [ ] Gotowość do zainstalowania Meta Pixel przed uruchomieniem kampanii
- [ ] Minimalny zasób kreacyjny: co najmniej jeden zestaw zatwierdzonych zdjęć marki

### Czynniki podnoszące jakość dealu
- [ ] Istniejący Meta Pixel z danymi zdarzeń (odwiedzający stronę, dodanie do koszyka itp.)
- [ ] Lista e-mail lub dane klientów do niestandardowych grup / seed podobnych
- [ ] Dostępne treści wideo lub w produkcji
- [ ] Obecność na Instagramie z istniejącym organicznym zaangażowaniem
- [ ] Jasny kalendarz promocji (oferty sezonowe, premiery)
- [ ] Katalog produktów e-commerce (dla dynamicznych reklam produktowych)

### Czerwone flagi
- 🚨 „Mój całkowity budżet to £200 — to obejmuje wszystko"
- 🚨 „Próbowałem Meta Ads i to był kompletny wyrzut pieniędzy" (zbadaj przyczynę przed zobowiązaniem)
- 🚨 „Nie chcę żadnego trackingu na mojej stronie"
- 🚨 Czyste B2B z wartościowymi niszowymi klientami (np. enterprise SaaS) — Meta często zły kanał, zasugeruj `google-ads`
- 🚨 Brak strony lub landing page w momencie startu kampanii
- 🚨 Branża w specjalnej kategorii reklam Meta (nieruchomości, finanse, zatrudnienie) — ograniczone targetowanie

---

## Zakres i granice
**Minimalne zaangażowanie (£349/mc zarządzanie + £300/mc budżet):**
Kampanie na Facebooku i Instagramie, konfiguracja Pixel, targetowanie grup zainteresowaniowych, tworzenie treści reklam, miesięczny raport.

**Zakres wymagający wyższego poziomu:**
- Dynamiczne reklamy produktowe (katalog e-commerce) → poziom Growth (£499/mc)
- Kampanie podobnych grup odbiorców (Lookalike) → poziom Growth
- Reels + reklamy wideo → poziom Growth
- Pełnolejkowa strategia multi-kampanijna → Scale lub indywidualnie

---

## Pricing anchors
| Scenariusz | Opłata za zarządzanie | Budżet reklamowy | Suma miesięczna |
|---|---|---|---|
| Starter | **£349/mc** | **£300/mc** | **£649/mc** |
| Growth (Lookalike, DPA, Reels) | **£499/mc** | **£600/mc** | **£1 099/mc** |
| Scale (pełny lejek) | **indywidualnie** | **£1 500+/mc** | **indywidualnie** |
| Bezpłatny audyt Meta (istniejące konto) | **£0** | — | Warunek wstępny |

---

## Ryzyka i zależności
- Brak Pixel = brak optymalizacji konwersji, tylko cele dotyczące ruchu lub zasięgu
- Słaba kreacja to główna przyczyna słabych wyników kampanii Meta; zabrief klienta nt. oczekiwań kreacyjnych
- Utrata atrybucji iOS 14+ — Meta może zaniżać konwersje o 20–40%; klient musi to rozumieć
- Specjalne kategorie reklam (nieruchomości, zatrudnienie, finanse) znacząco ograniczają targetowanie
- Mały rozmiar całkowitej grupy odbiorców na Meta w niszowych rynkach B2B może szybko prowadzić do zmęczenia grupy

---

## Założenia
- Klient kontroluje lub założy Facebook Business Manager i konto reklamowe
- Meta Pixel zostanie zainstalowany przed aktywacją jakichkolwiek wydatków
- Klient dostarcza lub zatwierdza zasoby kreacyjne przed uruchomieniem kampanii
- Minimalne zaangażowanie 3-miesięczne na fazę uczenia się
- Budżet reklamowy to oddzielne zobowiązanie budżetowe od opłaty za zarządzanie

---

## Otwarte pytania
- [ ] Czy klient uruchamiał wcześniej Meta Ads? Jaki był wynik?
- [ ] Czy dostępny jest katalog produktów (dla DPA / e-commerce)?
- [ ] Czy klient ma listę e-mail do seeda niestandardowej grupy odbiorców?
- [ ] Jaki jest główny cel konwersji — lead, zakup czy połączenie?
- [ ] Czy są nadchodzące premiery produktów lub promocje sezonowe?

---

## Rekomendowany następny krok
- **Zakwalifikowany** → Przeprowadź bezpłatny audyt konta lub szacowanie wielkości grupy; przedstaw 3-miesięczną propozycję Starter
- **Warunkowo zakwalifikowany** → Zaadresuj lukę kreacyjną lub problem landing page; wróć za 2–4 tygodnie
- **Niezakwalifikowany** → Przekieruj: jeśli potrzebny ruch oparty na intencji, zaproponuj `google-ads`; jeśli brakuje treści, najpierw zaproponuj retainer `content`
