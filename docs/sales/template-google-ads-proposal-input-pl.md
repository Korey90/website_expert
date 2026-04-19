# Google Ads (PPC) — Brief do oferty
> Service: google-ads
> Market: PL
> Brief Type: Proposal Input
> Status: Wersja robocza
> Last Updated: 2026-04-17
> Source Anchors: ServiceItemSeeder.php, skrypt-sprzedazowy.md

---

## Cel
Zebrać wszystkie szczegóły potrzebne do napisania propozycji zarządzania Google Ads: strukturę kampanii, konfigurację trackingu, podział budżetu, KPI, kamienie milowe i możliwości upsell.

---

## Kontekst klienta
| Pole | Odpowiedź |
|---|---|
| Nazwa klienta | `[client_name]` |
| Adres strony | `[website_url]` |
| Branża | `[industry]` |
| Główny kontakt | `[contact_name]`, `[email]`, `[phone]` |
| Decydent potwierdzony | `[name]` |
| Uzgodniony miesięczny budżet reklamowy | `[£XXX/mc]` |
| Poziom opłaty za zarządzanie | Starter (£399/mc) / Growth (£599/mc) / Indywidualny |
| Planowany start umowy | `[data]` |
| Istniejące konto Google Ads | Tak (dostęp MCC udzielony) / Nie (nowe konto) |

---

## Dopasowanie do oferty
- Usługa: **Google Ads (PPC)** (slug: `google-ads`)
- Typy kampanii: `[Search / Shopping / Performance Max / Display / Remarketing]`
- Dopasowanie potwierdzone: Tak / Warunkowo / Do weryfikacji
- Kluczowa wartość dla klienta: `[np. natychmiastowe leady, niższe CPA, premiera produktu]`

---

## Zakres i granice

### Struktura kampanii
| Kampania | Typ | Miesięczny budżet | KPI |
|---|---|---|---|
| `[Kampania 1]` | `[Search / Shopping / PMax]` | `[£XXX/mc]` | `[leady / ROAS / CPA]` |
| `[Kampania 2]` | `[Remarketing]` | `[£XXX/mc]` | `[CTR / konwersje]` |

### Konfiguracja śledzenia konwersji
| Akcja konwersji | Metoda śledzenia | Status |
|---|---|---|
| Wypełnienie formularza | Cel GA4 + GTM | `[skonfigurowane / do konfiguracji]` |
| Połączenie telefoniczne (kliknięcia) | Listener kliknięć GTM | `[skonfigurowane / do konfiguracji]` |
| Zakup | Zdarzenie e-commerce GA4 | `[skonfigurowane / do konfiguracji]` |

### Targetowanie
| Parametr | Szczegół |
|---|---|
| Geografia | `[ogólnopolski / miasto / radius wokół adresu]` |
| Język | `[polski / angielski]` |
| Urządzenie | Wszystkie / Priorytet desktop / Priorytet mobile |
| Listy odbiorców | Remarketing / Customer match / Podobni odbiorcy |

### Treść reklamowa i kreacja
- Wytyczne brandingowe: `[dostarczone / do ustalenia]`
- Kluczowe komunikaty / USP: `[lista]`
- Rozszerzenia reklam: Linki do podstron, Teksty dodatkowe, Telefon, Lokalizacja — `[potwierdzone / do ustalenia]`
- Kreacja display / wideo: `[dostarcza klient / produkuje WE / nie dotyczy]`

### Landing pages
| Kampania | URL landing page | Współczynnik konwersji (jeśli znany) |
|---|---|---|
| `[Kampania 1]` | `[URL]` | `[n%]` |
| `[Kampania 2]` | `[URL]` | `[n%]` |

### Poza zakresem
- Kampanie Meta / Facebook / Instagram (→ `meta-ads`)
- Działania SEO lub organiczne (→ `seo`)
- Produkcja wideo / YouTube (chyba że określono inaczej)
- Przeprojektowanie strony (→ `brochure-websites`)

---

## Pricing anchors
| Pozycja | Koszt miesięczny |
|---|---|
| Opłata za zarządzanie (`[Starter / Growth]`) | `[£399 / £599]` |
| Miesięczny budżet reklamowy (płatny bezpośrednio do Google) | `[£XXX/mc]` |
| Jednorazowa konfiguracja GTM / śledzenia (miesiąc 1) | `[£XXX lub w cenie]` |
| **Łączne miesięczne zaangażowanie** | `[£XXX/mc]` |

**Warunki płatności:** Opłata za zarządzanie — miesięcznie z góry. Budżet reklamowy — płatny bezpośrednio na konto Google Ads.

---

## Kamienie milowe
| Faza | Termin | Kluczowe działania |
|---|---|---|
| Tydzień 1 | Przed startem | Audyt / tworzenie konta, konfiguracja śledzenia, badanie słów |
| Tydzień 2 | Przed startem | Struktura kampanii, pisanie treści reklam, przegląd landing page |
| Tydzień 3 | Start | Kampanie uruchomione, śledzenie konwersji zweryfikowane |
| Przegląd miesiąca 1 | ~tydzień 4 | Sprawdzenie wyników, pierwsza runda optymalizacji |
| Miesiące 2–3 | Optymalizacja | Rozbudowa wykluczeń, korekty stawek, testy A/B |
| Miesiąc 3 | Przegląd | Pełny raport wyników, rozmowa o przedłużeniu retainera |

---

## Upsells i cross-sells
| Możliwość | Wartość | Status |
|---|---|---|
| Retainer SEO (`seo`) | od £499/mc | `[zainteresowany / odrzucony / do ustalenia]` |
| Meta Ads (`meta-ads`) | od £349/mc | `[zainteresowany / odrzucony / do ustalenia]` |
| Budowa landing page (`brochure-websites`) | od £799 | `[zainteresowany / odrzucony / do ustalenia]` |
| Plan maintenance (`maintenance`) | od £149/mc | `[zainteresowany / odrzucony / do ustalenia]` |
| Tworzenie treści (`content`) | od £199/mc | `[zainteresowany / odrzucony / do ustalenia]` |

---

## Ryzyka i zależności
- **Śledzenie konwersji** — bez zweryfikowanego trackingu kampanie nie mogą być optymalizowane; konfiguracja musi być gotowa przed startem
- **Jakość landing page** — jeśli współczynnik konwersji wynosi poniżej 1%, budżet reklamowy jest marnowany; zalecany przegląd CRO
- **Historia konta reklamowego** — jeśli konto ma niskie Quality Scores lub zawieszone polityki, odzyskanie trwa 2–4 tygodnie
- **Zmienność budżetu** — jeśli klient musi wstrzymać wydatki, kampanie tracą dane uczenia się; restart zajmuje 2–4 tygodnie
- **Ograniczenia polityk** — niektóre branże (ochrona zdrowia, finanse, prawo) mają dodatkowe ograniczenia Google Ads; zweryfikuj przed startem

---

## Założenia
- Konto Google Ads założone lub dostęp MCC udzielony przed rozpoczęciem konfiguracji kampanii
- Wszystkie zdarzenia śledzenia konwersji zweryfikowane na żywo przed aktywacją jakichkolwiek wydatków
- Klient zatwierdza treść reklam przed uruchomieniem kampanii
- Budżet reklamowy jest oddzielny od opłaty za zarządzanie i do niej dodatkowy
- KPI uzgodnione na piśmie przed miesiącem 1, aby uniknąć sporów o zakres

---

## Otwarte pytania
- [ ] Czy Google Tag Manager jest zainstalowany na stronie?
- [ ] Czy są jakieś ograniczenia słów kluczowych marki lub polityki dotyczące nazw konkurentów?
- [ ] Czy klient ma istniejącą grupę remarketingową (odwiedzający stronę)?
- [ ] Czy są specjalne okresy promocyjne (np. oferty sezonowe, premiery produktów)?
- [ ] Czy klient będzie zarządzał jakimikolwiek kampaniami samodzielnie równolegle do działań Website Expert?

---

## Rekomendowany następny krok
1. Przeprowadź audyt śledzenia i potwierdź, że wszystkie akcje konwersji działają prawidłowo
2. Przygotuj dokument struktury kampanii do zatwierdzenia przez klienta
3. Wystaw umowę o zarządzanie z celami KPI i harmonogramem raportowania
4. Uruchom kampanie w tygodniu 3; zaplanuj call z przeglądem wyników po 30 dniach
