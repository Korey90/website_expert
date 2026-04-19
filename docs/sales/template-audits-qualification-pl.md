# Audyty bezpieczeństwa i wydajności — Brief kwalifikacyjny
> Service: audits
> Market: PL
> Brief Type: Qualification
> Status: Wersja robocza
> Last Updated: 2026-04-17
> Source Anchors: ServiceItemSeeder.php, skrypt-sprzedazowy.md

---

## Cel
Określić, czy lead ma aktywną stronę wymagającą audytu, może zapewnić niezbędny dostęp i rozumie deliverable audytu (raport + omówienie, bez wdrożenia napraw w cenie).

---

## Kontekst klienta
| Pole | Odpowiedź |
|---|---|
| Nazwa firmy | `[client_name]` |
| Adres strony | `[website_url]` |
| Źródło leada | `[lead_source]` |
| Platforma / CMS | `[WordPress / Shopify / własna]` |
| Imię, nazwisko i stanowisko | `[contact_name]`, `[role]` |
| Decydent | Tak / Nie / Wspólnie |
| Budżet na audyt | £299 potwierdzone / Pyta / Za wysoko |
| Intencja po audycie | Naprawią sami / Chcą, żebyśmy naprawili / Niepewny |

---

## Dopasowanie do oferty
| Kryterium | Zakwalifikowany | Niezakwalifikowany |
|---|---|---|
| Aktywna strona | Aktywna, publicznie dostępna strona | Brak strony lub strona w aktywnej przebudowie |
| Budżet | £299 jednorazowo zaakceptowane | Odmawia zapłaty; oczekuje bezpłatnego audytu |
| Dostęp | Może zapewnić dostęp do CMS / hostingu | „Nie mam dostępu do własnej strony" |
| Oczekiwania | Rozumie: audyt = raport + omówienie | Oczekuje, że audyt obejmuje naprawę wszystkiego |
| Harmonogram | Standardowe 5–7 dni roboczych akceptowalne | „Potrzebuję tego naprawionego do dziś" |
| Istotność biznesowa | Strona kluczowa dla biznesu lub przechowuje dane | „To tylko placeholder, nikt go nie odwiedza" |

---

## Kryteria kwalifikacji

### Wymagania obligatoryjne
- [ ] Aktywna strona istnieje i jest publicznie dostępna
- [ ] Budżet £299 jednorazowo zaakceptowany
- [ ] Klient może zapewnić lub ułatwić dostęp do backendu CMS i/lub panelu kontrolnego hostingu
- [ ] Klient rozumie deliverable: raport PDF + 1-godzinne omówienie (naprawy są osobno)
- [ ] Standardowy harmonogram dostarczenia 5–7 dni roboczych jest akceptowalny

### Czynniki podnoszące jakość dealu
- [ ] Znane konkretne obawy (wolna strona, ostrzeżenia bezpieczeństwa, ostatnie włamanie, wymóg compliance)
- [ ] Zainteresowanie następczym retainerem `maintenance` po audycie
- [ ] Zainteresowanie retainerem `seo` jeśli podejrzewa się, że problemy wydajności wpływają na pozycje
- [ ] Strona obsługuje dane klientów lub płatności (zwiększa wartość i pilność audytu)
- [ ] Klient ma dewelopera lub agencję, która może wdrożyć wyniki

### Czerwone flagi
- 🚨 „Czy możecie zrobić audyt za darmo i wycenić nam naprawy później?" — wyjaśnij, że nie; audyt to płatna, samodzielna usługa
- 🚨 „Nie mam dostępu do hostingu ani CMS — moja stara agencja tym zarządza" — dostęp musi być rozwiązany przed startem audytu
- 🚨 „I tak przebudowujemy stronę za 3 miesiące" — wyniki audytu mogą być nieistotne; rozważ odroczenie
- 🚨 „Chcę tylko wycenę na naprawę wszystkiego bez audytu" — przekieruj do rozmowy o wycenie
- 🚨 „Mieliśmy włamanie i pilnie potrzebujemy naprawy dziś" — natychmiastowe usuwanie skutków to inna (wyżej pilna) usługa; odnieś do procesu wsparcia awaryjnego

---

## Zakres i granice
**Wliczone w £299:**
Skan OWASP Top 10, Core Web Vitals (LCP/CLS/INP), przegląd konfiguracji serwera + SSL, audyt zależności/CVE, priorytetyzowana lista napraw, raport PDF, 1-godzinne omówienie.

**Nie wliczone — wymaga osobnej wyceny:**
- Wdrożenie napraw (czas dewelopera wyceniany osobno)
- Stałe monitorowanie i aktualizacje (→ retainer `maintenance`)
- Testy penetracyjne / etyczne hakowanie
- Audyt SEO słów kluczowych lub linków (→ `seo`)

---

## Pricing anchors
| Pozycja | Cena |
|---|---|
| Audyt bezpieczeństwa i wydajności | **£299 jednorazowo** |
| Dostarczenie | 5–7 dni roboczych |
| Deliverables | Raport PDF + 1-godzinne omówienie |
| Wdrożenie napraw po audycie | Wycena indywidualna (po wynikach) |
| Maintenance po audycie | Od £149/mc (`maintenance`) |

---

## Ryzyka i zależności
- Audyt nie może się rozpocząć, dopóki nie zostanie zapewniony dostęp do CMS i/lub hostingu
- Jeśli strona jest w aktywnym developmencie lub tylko staging, wyniki audytu mogą różnić się od produkcji
- Harmonogram 5–7 dni roboczych startuje od otrzymania danych dostępowych, nie od podpisania

---

## Założenia
- Klient posiada domenę i kontroluje lub może uzyskać dostęp do CMS i hostingu
- Strona to wersja produkcyjna/live, nie środowisko staging
- Klient nie oczekuje, że audyt obejmuje wdrożenie napraw

---

## Otwarte pytania
- [ ] Czy klient aktualnie ma dostęp do własnego CMS i panelu hostingu?
- [ ] Czy są konkretne obawy do priorytetyzacji — bezpieczeństwo, wydajność czy compliance?
- [ ] Czy klient planuje wdrożyć wyniki samodzielnie czy przez Website Expert?
- [ ] Czy jest budżet na maintenance lub development po audycie?
- [ ] Czy strona obsługuje płatności, dane osobowe lub działa w sektorze regulowanym?

---

## Rekomendowany następny krok
- **Zakwalifikowany** → Wystaw umowę audytu (£299); zaplanuj slot omówienia wyników; rozpocznij przekazywanie dostępu
- **Warunkowo zakwalifikowany** → Rozwiąż problem dostępu lub wyjaśnij oczekiwania co do zakresu; wróć w ciągu 5 dni roboczych
- **Niezakwalifikowany** → Jeśli potrzebne natychmiastowe usuwanie skutków, skieruj do eskalacji wsparcia; jeśli potrzebna przebudowa strony, skieruj do `brochure-websites`
