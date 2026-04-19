# Aplikacje internetowe — Brief do propozycji
> Service: web-applications
> Market: PL
> Brief Type: Proposal Input
> Status: Wersja robocza
> Last Updated: 2026-04-17
> Source Anchors: ServiceItemSeeder.php, skrypt-sprzedazowy.md

---

## Cel
Zebrać wyniki płatnej fazy discovery i przetłumaczyć je na stałocenową propozycję: potwierdzony zakres, decyzje architektoniczne, role użytkowników, mapa integracji, kamienie milowe i rejestr ryzyk.

*Uwaga: Ten brief jest wypełniany PO płatnej fazie discovery (£599), nie przed nią.*

---

## Kontekst klienta
| Pole | Odpowiedź |
|---|---|
| Nazwa klienta | `[client_name]` |
| Nazwa aplikacji / tytuł roboczy | `[app_name]` |
| Główny kontakt | `[contact_name]`, `[email]`, `[phone]` |
| Potwierdzony decydent | `[name]` |
| Uzgodniony zakres budżetu | `[budget]` |
| Twardy termin launchu | `[launch_date]` |
| Faza discovery ukończona | Tak / Częściowo |
| Data briefu | `[brief_date]` |

---

## Dopasowanie do oferty
- Usługa: **Aplikacja webowa** (slug: `web-applications`)
- Typ aplikacji: `[SaaS / portal / rezerwacje / narzędzie wewnętrzne]`
- Dopasowanie potwierdzone: Tak / Warunkowo / Do potwierdzenia
- Kluczowa wartość dla klienta: `[np. automatyzuje X, zastępuje arkusz Y, umożliwia nowy przychód przez Z]`

---

## Zakres i granice

### Role użytkowników
| Rola | Uprawnienia | Szac. liczba użytkowników |
|---|---|---|
| `[Admin]` | `[pełny dostęp]` | `[n]` |
| `[Klient]` | `[odczyt + ograniczony zapis]` | `[n]` |
| `[Pracownik]` | `[tylko przypisane zadania]` | `[n]` |

### Podstawowy zestaw funkcji (potwierdzony)
| Funkcja | Priorytet | Złożoność |
|---|---|---|
| `[Funkcja 1]` | Must-have | `[Niska / Średnia / Wysoka]` |
| `[Funkcja 2]` | Should-have | `[Niska / Średnia / Wysoka]` |
| `[Funkcja 3]` | Nice-to-have | `[Niska / Średnia / Wysoka]` |

### Mapa integracji
| System | Typ | Status |
|---|---|---|
| `[Stripe]` | Przetwarzanie płatności | `[potwierdzony / TBD]` |
| `[Nazwa CRM]` | Synchronizacja danych | `[potwierdzony / TBD]` |
| `[Nazwa API]` | `[cel]` | `[potwierdzony / TBD]` |

### Decyzje architektoniczne
| Decyzja | Wybór | Uzasadnienie |
|---|---|---|
| Framework backend | Laravel 13 | Wydajność, bezpieczeństwo, expertise zespołu |
| Framework frontend | React 18 + TypeScript | Bezpieczeństwo typów, reużycie komponentów |
| Multi-tenancy | Tak / Nie | `[powód]` |
| Funkcje real-time | Reverb / Pusher / Brak | `[powód]` |
| Hosting | `[VPS / cloud / zarządzany przez klienta]` | `[powód]` |

### Poza zakresem
- Natywna aplikacja mobilna (iOS/Android) — nie wliczona
- Funkcje AI/ML — nie wliczone, chyba że określono powyżej
- Integracje zewnętrzne nie wymienione powyżej → proces change-request
- Rozwój funkcji po launchu poza uzgodnionym zakresem → osobny retainer

---

## Pricing anchors
| Pozycja | Cena |
|---|---|
| Płatna faza discovery (ukończona / zaliczona) | **£599** |
| Faza buildu — potwierdzony zakres | `[£XXX]` |
| Konfiguracja integracji zewnętrznych (jeśli dotyczy) | `[+£XXX]` |
| Konfiguracja pipeline CI/CD | `[+£XXX]` |
| **Suma projektu** | `[£XXX]` |
| Dodatek: Retainer utrzymania | `[+£149/mc]` |
| Dodatek: SEO / content (dla marketingu SaaS) | `[+£499/mc + £199/mc]` |

**Warunki płatności:** 50% z góry / 25% przy kamień milowym mid-build / 25% przy launchu (lub wg umowy).

---

## Kamienie milowe
| Kamień milowy | Szacunkowa data |
|---|---|
| Podpisana umowa + pierwsza faktura | `[data]` |
| Konfiguracja architektury i środowiska | `[data]` |
| Sprint 1 dostarczony (główne przepływy użytkownika) | `[data]` |
| Sprint 2 dostarczony (integracje) | `[data]` |
| Sprint N dostarczony | `[data]` |
| Wewnętrzne QA ukończone | `[data]` |
| UAT z klientem | `[data]` |
| Launch / przekazanie | `[data]` |
| Koniec wsparcia po launchu | `[data]` |

---

## Upselle i cross-selle
| Możliwość | Wartość | Status |
|---|---|---|
| Retainer utrzymania (`maintenance`) | od £149/mc | `[zainteresowany / odrzucony / TBD]` |
| SEO dla landing page SaaS (`seo`) | od £499/mc | `[zainteresowany / odrzucony / TBD]` |
| Tworzenie treści (`content`) | od £199/mc | `[zainteresowany / odrzucony / TBD]` |
| Google Ads na launch SaaS (`google-ads`) | od £399/mc | `[zainteresowany / odrzucony / TBD]` |
| Meta Ads (`meta-ads`) | od £349/mc | `[zainteresowany / odrzucony / TBD]` |

---

## Ryzyka i zależności
- **Zmiana zakresu** — każda funkcja dodana po podpisaniu umowy podlega formalnemu change-request i dodatkowym kosztom
- **Opóźnienia API zewnętrznych** — jeśli API partnera integracyjnego jest niedostępne lub nieudokumentowane, harmonogram przesuwa się
- **SLA feedbacku klienta** — UAT musi być zakończone w ciągu 5 dni roboczych od dostarczenia, inaczej harmonogram się wydłuża
- **Infrastruktura** — provisioning serwera lub konfiguracja konta cloud musi być ukończona przed startem developmentu
- **Migracja danych** — jeśli istniejące dane muszą być migrowane, wymagany jest audyt danych przed zatwierdzeniem harmonogramu

---

## Założenia
- Wyniki fazy discovery (specyfikacja, wireframes) stanowią umowną podstawę buildu
- Formalny proces change-request reguluje wszelkie dodatkowe elementy zakresu
- Klient dostarcza wszystkie dane logowania do API i klucze zewnętrzne przed sprintami integracyjnymi
- Po launchu: naprawy błędów w uzgodnionym okresie gwarancyjnym wliczone; nowe funkcje fakturowane osobno

---

## Otwarte pytania
- [ ] Czy infrastruktura / środowisko hostingowe zostało skonfigurowane?
- [ ] Czy wszystkie dane logowania do API zewnętrznych są dostępne?
- [ ] Czy wymagane jest środowisko staging do UAT?
- [ ] Czy wewnętrzny zespół klienta będzie obecny na przeglądach sprintów?
- [ ] Czy wymagane są umowy o przetwarzaniu danych (DPA) z integracjami zewnętrznymi?

---

## Rekomendowany następny krok
1. Sfinalizuj specyfikację techniczną jako załącznik umowny
2. Wystaw harmonogram faktur fazowych (50% / 25% / 25%)
3. Skonfiguruj środowiska developerskie i rozpocznij Sprint 1
4. Zaplanuj tygodniowy rytm przeglądów sprintów z klientem
