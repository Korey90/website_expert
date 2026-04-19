# Maintenance strony — Brief kwalifikacyjny
> Service: maintenance
> Market: PL
> Brief Type: Qualification
> Status: Wersja robocza
> Last Updated: 2026-04-17
> Source Anchors: ServiceItemSeeder.php, skrypt-sprzedazowy.md

---

## Cel
Określić, czy lead ma aktywną, biznesowo krytyczną stronę z możliwą do zidentyfikowania luką zarządzania, którą retainer serwisowy może bezpośrednio zaadresować, oraz czy klient rozumie ciągły charakter zaangażowania.

---

## Kontekst klienta
| Pole | Odpowiedź |
|---|---|
| Nazwa firmy | `[client_name]` |
| Adres strony | `[website_url]` |
| Źródło leada | `[lead_source]` |
| CMS / platforma | `[WordPress / Shopify / własna]` |
| Imię, nazwisko i stanowisko | `[contact_name]`, `[role]` |
| Decydent | Tak / Nie / Wspólnie |
| Budżet miesięczny | £149/mc zaakceptowane / Pyta / Za wysoko |
| Obecne zarządzanie | `[agencja / in-house / nikt]` |

---

## Dopasowanie do oferty
| Kryterium | Zakwalifikowany | Niezakwalifikowany |
|---|---|---|
| Aktywna strona | Aktywna, publicznie dostępna strona | Brak strony lub strona w pełnej przebudowie |
| Budżet | £149/mc zaakceptowane | Chce tylko jednorazowej naprawy, bez retainera |
| Platforma | WordPress, Shopify lub obsługiwany CMS | W pełni własna platforma z in-house DevOps |
| Luka zarządzania | Nikt aktywnie nie zarządza / aktualizuje / tworzy kopii | Duży team in-house już to pokrywa |
| Krytyczność biznesowa | Przychody, leady lub rezerwacje zależą od strony | „To tylko placeholder, brak ruchu" |
| Dostęp | Klient może zapewnić lub uzyskać dostęp CMS + hosting | Brak dostępu i niechęć do rozwiązania |

---

## Kryteria kwalifikacji

### Wymagania obligatoryjne
- [ ] Aktywna strona istnieje i jest biznesowo krytycznym zasobem klienta
- [ ] Budżet miesięczny £149/mc zaakceptowany
- [ ] Dostęp admin do CMS lub hostingu może być zapewniony (lub uzyskany w onboardingu)
- [ ] Możliwa do zidentyfikowania luka w obecnym zarządzaniu stroną (brak aktualizacji, monitorowania, kopii)
- [ ] Klient rozumie, że to rolling monthly retainer, nie jednorazowa naprawa

### Czynniki podnoszące jakość dealu
- [ ] Brak aktywnej umowy serwisowej z inną agencją
- [ ] Ostatnie lub nadchodzące obawy dotyczące bezpieczeństwa tworzące pilność (włamanie, malware, ostrzeżenie)
- [ ] Zainteresowanie `audits` jako pierwszym krokiem do ustalenia baseline (zwiększa jakość dealu)
- [ ] Prowadzone płatne reklamy (Google / Meta) czyniące przestój finansowo kosztownym
- [ ] Strona WordPress z wieloma aktywnymi wtyczkami (wyższe ryzyko aktualizacji = wyższa wartość)

### Czerwone flagi
- 🚨 „Potrzebuję tylko żebyście naprawili jedną rzecz i potem sam sobie poradzę" — zakwalifikuj jako jednorazowe zadanie developerskie, nie maintenance
- 🚨 „Mamy własny dział IT, który to wszystko robi" — to nieodpowiedni nabywca; najpierw potwierdź, że jest luka
- 🚨 W pełni własna aplikacja (bez off-the-shelf CMS) — zakres maintenance może być zbyt złożony; eskaluj do zespołu `web-applications`
- 🚨 „Mój obecny host robi wszystkie aktualizacje automatycznie" — wyjaśnij; większość hostów robi tylko aktualizacje poziomu serwera, nie wtyczek/szablonów CMS
- 🚨 Klient odmawia zapewnienia jakiegokolwiek dostępu do strony — bez dostępu, bez monitorowania; maintenance nie może być realizowane

---

## Zakres i granice
**Plan standardowy (£149/mc):**
Aktualizacje, monitoring dostępności, kopie zapasowe offsite, miesięczny raport, 2-godzinny SLA odpowiedzi na krytyczne problemy, SSL + DNS.

**Dodatek zarządzanego hostingu (£29/mc):**
Środowisko hostingowe zarządzane przez Website Expert; wymagana migracja od obecnego hosta.

**Nie wliczone:**
- Nowy development funkcji (→ osobna wycena developerska)
- Zmiany designu (→ osobna wycena)
- Praca SEO (→ retainer `seo`)
- Wstępny audyt / skan bezpieczeństwa (→ `audits` £299 jednorazowo — rekomenduj jako punkt wejścia)

---

## Pricing anchors
| Plan | Opłata miesięczna | Kluczowe elementy |
|---|---|---|
| Standardowy Maintenance | **£149/mc** | Aktualizacje, monitoring, kopie, 2h SLA, SSL/DNS |
| + Zarządzany Hosting | **+ £29/mc** | Środowisko hostingowe zarządzane przez Website Expert |

- Punkt wejścia: `audits` £299 jednorazowo (idealny baseline przed maintenance)
- Cross-sell: `seo` od £499/mc

---

## Ryzyka i zależności
- Mocno przestarzałe instalacje WordPress mogą wymagać płatnej pracy naprawczej przed baseline maintenance
- Jeśli kopie zapasowe nie istnieją, pierwszym zadaniem maintenance jest konfiguracja kopii — pierwszy miesiąc może mieć nakład onboardingowy
- Konfiguracje multi-site lub złożone e-commerce mogą wymagać wyceny wyższego poziomu

---

## Założenia
- Klient jest na standardowym CMS (WordPress lub obsługiwana platforma)
- Rolling monthly contract z 30-dniowym wypowiedzeniem
- Klient akceptuje, że 2-godzinny SLA odpowiedzi dotyczy problemów krytycznych, nie próśb o funkcje

---

## Otwarte pytania
- [ ] Czy ktoś jest aktualnie odpowiedzialny za aktualizacje strony i monitorowanie?
- [ ] Kiedy strona była ostatnio aktualizowana — rdzeń CMS, wtyczki, szablony?
- [ ] Czy są znane problemy bezpieczeństwa lub wydajności do zaadresowania w onboardingu?
- [ ] Czy potrzebna lub preferowana jest migracja do zarządzanego hostingu?
- [ ] Czy klient miał ostatnio health check `audits`?

---

## Rekomendowany następny krok
- **Zakwalifikowany** → Zaproponuj standardowy Maintenance (£149/mc); zaplanuj call onboardingowy; poproś o dane dostępowe
- **Warunkowo zakwalifikowany** → Najpierw rekomenduj `audits` (£299) do ustalenia baseline; wróć do maintenance po omówieniu
- **Niezakwalifikowany (jednorazowy)** → Dostarcz wycenę stałą na konkretny problem; pielęgnuj kierunek ku retainerowi maintenance
