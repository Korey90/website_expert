# Aplikacje internetowe — Brief discovery
> Service: web-applications
> Market: PL
> Brief Type: Discovery
> Status: Wersja robocza
> Last Updated: 2026-04-17
> Source Anchors: ServiceItemSeeder.php, skrypt-sprzedazowy.md

---

## Cel
Zrozumieć problem biznesowy, który klient chce rozwiązać dedykowaną aplikacją — granice zakresu, potrzeby integracyjne, role użytkowników i model komercyjny — zanim zobowiążemy się do fazy discovery lub wyceny stałocenowej.

---

## Kontekst klienta
| Pole | Odpowiedź |
|---|---|
| Nazwa firmy | `[client_name]` |
| Branża | `[industry]` |
| Typ aplikacji | `[SaaS / portal / rezerwacje / dashboard / narzędzie wewnętrzne]` |
| Obecny stack technologiczny | `[current_platform]` |
| Główne typy użytkowników | `[użytkownicy końcowi / admini / pracownicy / klienci]` |
| Szacowana liczba użytkowników przy launchu | `[n]` |
| Główny cel | `[automatyzacja / zastąpienie procesu ręcznego / nowy produkt / rozbudowa istniejącego]` |
| Twardy deadline | `[deadline]` |
| Orientacyjny budżet | `[budget_indication]` |

---

## Dopasowanie do oferty
Dedykowana aplikacja webowa od Website Expert pasuje firmom, które:
- Mają konkretny workflow lub problem, którego gotowe oprogramowanie nie rozwiązuje wystarczająco
- Potrzebują stosu Laravel (backend) + React/TypeScript (frontend)
- Wymagają ról użytkowników, kontroli dostępu lub architektury multi-tenant
- Planują trwałą relację rozwoju lub utrzymania

**Brak dopasowania gdy:** problem można rozwiązać sklepem WooCommerce lub stroną wizytówkową. W przypadku marketplace wielosprzedawcowego bez złożonej logiki, rozważ najpierw wycenę jako `ecommerce`.

---

## Przebieg discovery

### Problem
> „Jaki ręczny proces lub ból próbujesz rozwiązać tą aplikacją?"
> „Z czego korzystasz dziś — arkusze, gotowe oprogramowanie, ręczne przepływy pracy?"
> „Jaki jest koszt nierozwiązania tego? Utracony przychód, zmarnowany czas, popełniane błędy?"

### Użytkownicy i role
> „Kto używa tego systemu — wewnętrzni pracownicy, Twoi klienci, partnerzy, czy wszyscy trzej?"
> „Czy różne typy użytkowników potrzebują różnych uprawnień lub widoków?"
> „Ilu użytkowników oczekujesz przy launchu vs. za 12 miesięcy?"

### Kluczowe funkcje
> „Opowiedz mi o najważniejszej rzeczy, którą system musi robić."
> „Czy są systemy zewnętrzne, z którymi musi się komunikować — CRM, bramka płatności, ERP, API?"
> „Czy potrzebujesz funkcji real-time — powiadomienia, aktualizacje na żywo, komunikator?"

### Kontekst techniczny
> „Czy masz istniejący kod? Jeśli tak, w jakim języku / frameworku?"
> „Czy zaczęłeś specyfikację lub wireframes?"
> „Czy Twój zespół ma wewnętrznych developerów, którzy będą utrzymywać system po budowie?"

### Model komercyjny (dla SaaS)
> „Czy to narzędzie wewnętrzne, czy produkt, który będziesz sprzedawać innym firmom?"
> „Jeśli SaaS: model subskrypcji, per-seat, usage-based czy freemium?"

### Harmonogram i budżet
> „Czy jest deadline regulacyjny, inwestycyjny lub operacyjny, który to napędza?"
> „Dedykowane aplikacje zaczynają się od £5 999 dla projektów ze zdefiniowanym zakresem. Większe buildy lub platformy SaaS mieszczą się w przedziale £15k–£50k+. Czy to pasuje do Twojego planowania?"

---

## Zakres i granice
**Faza discovery (wymagana dla wszystkich projektów aplikacji webowych):**
- Płatna discovery (od £599): zbieranie wymagań, projektowanie architektury, wycena stałocenowa
- Deliverable: specyfikacja techniczna, wireframes i stałocenowa oferta projektu

**Typowe elementy buildu:**
- Backend Laravel 13 + frontend React/TypeScript
- Kontrola dostępu oparta na rolach (RBAC)
- Integracja REST API lub GraphQL
- Kolejki, eventy, powiadomienia real-time (Laravel Reverb)
- Architektura multi-tenancy (jeśli SaaS)
- Testy automatyczne + konfiguracja CI/CD

**Domyślnie poza zakresem:**
- Natywne aplikacje mobilne (iOS/Android)
- DevOps / infrastruktura serwerowa poza standardowym deploymentem
- Ciągły rozwój produktu po przekazaniu (wymaga `maintenance` lub osobnego retainera)
- Funkcje data science / ML

---

## Pricing anchors
| Poziom | Cena | Opis |
|---|---|---|
| Płatna faza discovery | od **£599** | Zakres, architektura, wireframes → wycena stałocenowa |
| Mała aplikacja | od **£5 999** | Dobrze zdefiniowany zakres, jedna rola, brak złożonych integracji |
| Średnia złożoność | od **£15 000** | Wiele ról, integracje zewnętrzne, zaawansowane przepływy |
| Platforma SaaS | od **£25 000+** | Multi-tenancy, billing, panel admin, złożona logika domenowa |
| Audyt kodu (istniejąca aplikacja) | od **£299** | Skan OWASP + przegląd jakości kodu przed przejęciem |

- Dodatek: Retainer utrzymania — od £149/mc (`maintenance`)
- Dodatek: SEO i content dla marketingu SaaS — od £499/mc (`seo`) + od £199/mc (`content`)

---

## Ryzyka i zależności
- **Niepewność zakresu** — najczęstsza przyczyna przekroczenia kosztów; płatna faza discovery jest obowiązkowa
- **Ograniczenia API zewnętrznych** — nieudokumentowane lub limitowane API mogą opóźnić integracje
- **Istniejący kod** — przejęcie kodu legacy wymaga audytu przed jakimkolwiek zobowiązaniem stałocenowym
- **Nietech. interesariusze** — zmieniające się wymagania w trakcie buildu bez formalnego procesu change-request
- **Infrastruktura / hosting** — klient musi posiadać lub zarządzać infrastrukturą serwera/cloud, lub zgodzić się na dodatek managed hosting

---

## Założenia
- Płatna faza discovery (od £599) poprzedza każdą stałocenową wycenę developmentu
- Klient może zapewnić dedykowany czas na feedback podczas sprintów projektowych i developerskich
- Wszystkie dane logowania do API i konta zewnętrzne są dostępne przed startem buildu
- Utrzymanie po launchu obsługuje albo wewnętrzny zespół klienta, albo plan `maintenance` Website Expert

---

## Otwarte pytania
- [ ] Czy zaczęto specyfikację lub dokument wymagań?
- [ ] Czy jest istniejący kod do audytu lub przejęcia?
- [ ] Jakiej infrastruktury hostingowej / cloud używa lub preferuje klient?
- [ ] Czy jest wewnętrzny zasób techniczny do utrzymania aplikacji po launchu?
- [ ] Czy istnieją wymogi compliance lub rezydencji danych (RODO, ISO, opieka zdrowotna)?

---

## Rekomendowany następny krok
1. Zakwalifikuj na podstawie budżetu (czy £5 999+ jest zaakceptowane?) i jasności problemu
2. Zaproponuj **Płatną fazę discovery** (od £599) jako bezpośredni następny krok
3. Zaplanuj 60-minutowy warsztat discovery do mapowania przepływów użytkownika i integracji
4. Po discovery: wystaw stałocenową ofertę z kamieniami milowymi i zdefiniowanym procesem change-request
