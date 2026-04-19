# Meta / Pixel Ads — Brief do oferty
> Service: meta-ads
> Market: PL
> Brief Type: Proposal Input
> Status: Wersja robocza
> Last Updated: 2026-04-17
> Source Anchors: ServiceItemSeeder.php, skrypt-sprzedazowy.md

---

## Cel
Zebrać wszystkie szczegóły potrzebne do napisania propozycji zarządzania Meta Ads: strukturę kampanii, brief kreacyjny, konfigurację pixela, strategię grup odbiorców, podział budżetu, KPI, kamienie milowe i możliwości upsell.

---

## Kontekst klienta
| Pole | Odpowiedź |
|---|---|
| Nazwa klienta | `[client_name]` |
| Adres strony | `[website_url]` |
| Branża | `[industry]` |
| Główny kontakt | `[contact_name]`, `[email]`, `[phone]` |
| Decydent potwierdzony | `[name]` |
| URL Fanpage Facebook | `[facebook_url]` |
| Instagram | `[instagram_handle]` |
| Uzgodniony miesięczny budżet reklamowy | `[£XXX/mc]` |
| Poziom opłaty za zarządzanie | Starter (£349/mc) / Growth (£499/mc) / Indywidualny |
| Planowany start umowy | `[data]` |
| Istniejące konto Meta Ads | Tak (dostęp Business Manager udzielony) / Nie |

---

## Dopasowanie do oferty
- Usługa: **Meta / Pixel Ads** (slug: `meta-ads`)
- Typy kampanii: `[Świadomość / Ruch / Leady / Sprzedaż / Retargeting / Dynamiczne reklamy produktowe]`
- Dopasowanie potwierdzone: Tak / Warunkowo / Do weryfikacji
- Kluczowa wartość dla klienta: `[np. świadomość marki, leady z social, retargeting odwiedzających, premiery produktów]`

---

## Zakres i granice

### Struktura kampanii
| Kampania | Cel | Grupa odbiorców | Miesięczny budżet | KPI |
|---|---|---|---|---|
| `[Kampania 1]` | `[Leady / Sprzedaż]` | `[Zainteresowania / Lookalike]` | `[£XXX/mc]` | `[CPL / ROAS]` |
| `[Kampania 2]` | `[Retargeting]` | `[Odwiedzający stronę]` | `[£XXX/mc]` | `[CTR / CVR]` |

### Konfiguracja Pixel i trackingu
| Element | Status |
|---|---|
| Meta Pixel zainstalowany | `[tak / wymaga instalacji]` |
| Conversions API (CAPI) skonfigurowane | `[tak / do konfiguracji]` |
| Zdarzenie Page View | `[działa / do konfiguracji]` |
| Zdarzenie Lead / Purchase | `[działa / do konfiguracji]` |
| Niestandardowe konwersje | `[zdefiniowane / do ustalenia]` |

### Strategia grup odbiorców
| Grupa | Typ | Szacowana wielkość | Kampania |
|---|---|---|---|
| `[Zimna grupa 1]` | Zainteresowania | `[~XXX 000]` | `[Kampania 1]` |
| `[Lookalike 1%]` | Lookalike (seed lista e-mail) | `[~XXX 000]` | `[Kampania 1]` |
| `[Retargeting]` | Niestandardowa (odwiedzający 30d) | `[~XXX]` | `[Kampania 2]` |

### Brief kreacyjny
| Typ reklamy | Format | Kopia dostarczona przez | Wizual dostarczony przez |
|---|---|---|---|
| Świadomość | Obraz statyczny / Karuzela | `[WE / klient]` | `[WE / klient]` |
| Lead Form | Reklama leadowa | `[WE / klient]` | `[WE / klient]` |
| Retargeting | Karuzela / Wideo | `[WE / klient]` | `[WE / klient]` |

- Ton komunikacji marki: `[formalny / konwersacyjny / odważny]`
- Kluczowe USP do podkreślenia: `[lista]`
- Formaty reklam: Statyczny / Karuzela / Wideo / Reels / Lead Form — `[potwierdzone]`

### Landing pages
| Kampania | URL landing page | Zdarzenie Pixel |
|---|---|---|
| `[Kampania 1]` | `[URL]` | `[Lead / Purchase / ViewContent]` |
| `[Kampania 2]` | `[URL]` | `[Lead / Purchase / ViewContent]` |

### Poza zakresem
- Google Ads lub reklamy w wyszukiwarce (→ `google-ads`)
- Organiczne zarządzanie social media
- Pełna produkcja wideo (drobna edycja w cenie; pełna produkcja to dodatek)
- Budowa strony lub landing page (→ `brochure-websites`)

---

## Pricing anchors
| Pozycja | Koszt miesięczny |
|---|---|
| Opłata za zarządzanie (`[Starter / Growth]`) | `[£349 / £499]` |
| Miesięczny budżet reklamowy (płatny bezpośrednio do Meta) | `[£XXX/mc]` |
| Jednorazowa konfiguracja Pixel / CAPI (miesiąc 1) | `[£XXX lub w cenie]` |
| **Łączne miesięczne zaangażowanie** | `[£XXX/mc]` |

**Warunki płatności:** Opłata za zarządzanie — miesięcznie z góry. Budżet reklamowy — płatny do Meta Business Manager.

---

## Kamienie milowe
| Faza | Termin | Kluczowe działania |
|---|---|---|
| Tydzień 1 | Przed startem | Konfiguracja konta / Business Manager, instalacja Pixel, badanie grup |
| Tydzień 2 | Przed startem | Struktura kampanii, briefy kreacyjne, pisanie kopii, zatwierdzenie klienta |
| Tydzień 3 | Start | Kampanie uruchomione, zdarzenia Pixel zweryfikowane, wydatki aktywowane |
| Przegląd miesiąca 1 | ~tydzień 4 | Sprawdzenie wyników, performance grup, wyniki testów A/B kreacji |
| Miesiące 2–3 | Optymalizacja | Skalowanie wygrywających grup, odświeżenie kreacji, wprowadzenie Reels/wideo |
| Miesiąc 3 | Przegląd | Pełny raport wyników, rozmowa o przedłużeniu retainera |

---

## Upsells i cross-sells
| Możliwość | Wartość | Status |
|---|---|---|
| Google Ads (`google-ads`) | od £399/mc zarządzanie | `[zainteresowany / odrzucony / do ustalenia]` |
| Tworzenie treści (`content`) | od £199/mc | `[zainteresowany / odrzucony / do ustalenia]` |
| Sklep e-commerce (`ecommerce`) | od £2 999 | `[zainteresowany / odrzucony / do ustalenia]` |
| Landing page (`brochure-websites`) | od £799 | `[zainteresowany / odrzucony / do ustalenia]` |
| Plan maintenance (`maintenance`) | od £149/mc | `[zainteresowany / odrzucony / do ustalenia]` |

---

## Ryzyka i zależności
- **Pixel niezainstalowany** — kampanie nie mogą optymalizować konwersji; instalacja Pixel to twarda zależność przed startem
- **Brak zasobów kreacyjnych** — kampanie nie mogą wystartować bez co najmniej jednego zestawu zatwierdzonych wizualiów; zaplanuj harmonogram dostarczenia zasobów
- **Luka atrybucji iOS 14+** — liczby konwersji mogą wyglądać 20–40% niżej w dashboardzie Meta niż rzeczywistość; klient musi być zabryfowany
- **Za mała grupa odbiorców** — jeśli całkowita grupa jest poniżej ~100 tys., kampanie mogą szybko wpaść w zmęczenie grupy
- **Specjalna kategoria reklamy** — jeśli firma jest w nieruchomościach, finansach lub zatrudnieniu, potwierdź ograniczenia targetowania przed startem

---

## Założenia
- Facebook Business Manager i konto reklamowe założone / dostęp udzielony przed rozpoczęciem konfiguracji
- Pixel i CAPI zainstalowane i zweryfikowane przed aktywacją wydatków
- Klient zatwierdza wszystkie treści reklam i kreacje przed uruchomieniem kampanii
- Budżet reklamowy jest finansowo oddzielny od opłaty za zarządzanie i do niej dodatkowy
- Minimalne zaangażowanie 3-miesięczne na uczenie się grup i optymalizację kampanii

---

## Otwarte pytania
- [ ] Czy konto Facebook Business Manager zostało założone? (Potrzebny dostęp Admin lub Advertiser)
- [ ] Czy Meta Pixel jest zainstalowany na stronie? Czy zdarzenia konwersji się uruchamiają?
- [ ] Czy klient dostarczył wytyczne brandingowe, logo i zatwierdzone zdjęcia?
- [ ] Czy istnieje lista e-mail klientów do seeda grupy Lookalike?
- [ ] Czy wymagane są dynamiczne reklamy produktowe (katalog e-commerce)?

---

## Rekomendowany następny krok
1. Potwierdź dostęp do Business Manager i status Pixel
2. Dostarcz klientowi brief kreacyjny z wymaganiami dotyczącymi zasobów i harmonogramem zatwierdzenia
3. Wystaw umowę o zarządzanie z celami KPI i harmonogramem raportowania
4. Uruchom kampanie w tygodniu 3; zaplanuj call z przeglądem wyników po 30 dniach
