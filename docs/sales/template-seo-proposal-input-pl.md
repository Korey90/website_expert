# SEO i Marketing Cyfrowy — Brief do propozycji
> Service: seo
> Market: PL
> Brief Type: Proposal Input
> Status: Wersja robocza
> Last Updated: 2026-04-17
> Source Anchors: ServiceItemSeeder.php, skrypt-sprzedazowy.md

---

## Cel
Zebrać wszystkie szczegóły potrzebne do napisania propozycji retainera SEO: bazowa wydajność, priorytetowe klastry słów kluczowych, plan contentu, harmonogram deliverables i możliwości upsellowe.

---

## Kontekst klienta
| Pole | Odpowiedź |
|---|---|
| Nazwa klienta | `[client_name]` |
| Adres strony | `[website_url]` |
| Branża / nisza | `[industry]` |
| Główny kontakt | `[contact_name]`, `[email]`, `[phone]` |
| Potwierdzony decydent | `[name]` |
| Uzgodniony miesięczny budżet | `[£XXX/mc]` |
| Poziom retainera | Starter (£499/mc) / Growth (£799/mc) / Indywidualny |
| Data startu retainera | `[data]` |
| Potwierdzony minimalny czas trwania | 3 miesiące / 6 miesięcy / 12 miesięcy |

---

## Dopasowanie do oferty
- Usługa: **SEO i Marketing Cyfrowy** (slug: `seo`)
- Poziom retainera: `[Starter / Growth / Indywidualny]`
- Dopasowanie potwierdzone: Tak / Warunkowo / Do potwierdzenia
- Kluczowa wartość dla klienta: `[np. organiczne generowanie leadów, widoczność lokalna, wzrost ruchu przez content]`

---

## Zakres i granice

### Audyt bazowy (miesiąc 1)
- [ ] Pełny audyt techniczny SEO (błędy crawlowania, Core Web Vitals, robots, sitemap)
- [ ] Badanie słów kluczowych — główne i drugorzędne klastry: `[n klastrów]`
- [ ] Analiza konkurencji: `[n konkurentów potwierdzonych]`
- [ ] Audyt Google Business Profile (jeśli lokalne SEO w zakresie)
- [ ] Przegląd profilu linków

### Bieżące miesięczne deliverables
| Deliverable | Częstotliwość | Poziom |
|---|---|---|
| Naprawy techniczne i optymalizacja on-page | Miesięcznie | Starter + Growth |
| Raport pozycji słów kluczowych (GSC + GA4) | Miesięcznie | Starter + Growth |
| Rekomendacje contentu / briefy | Miesięcznie | Starter + Growth |
| Link building outreach (n linków/mc) | Miesięcznie | Tylko Growth |
| Produkcja artykułów blogowych | Miesięcznie | Growth + dodatek `content` |
| Miesięczna rozmowa strategiczna | Miesięcznie | Starter + Growth |

### Fokus geograficzny i językowy
| Rynek | Język | Priorytet |
|---|---|---|
| `[Ogólnopolski / miasto lokalne]` | Polski | Wysoki |
| `[Inny]` | `[język]` | `[priorytet]` |

### Plan contentu (jeśli dotyczy)
| Klaster tematyczny | Docelowe słowa kluczowe | Właściciel contentu | Miesiąc |
|---|---|---|---|
| `[Temat 1]` | `[lista słów kluczowych]` | `[klient / WE]` | `[n]` |
| `[Temat 2]` | `[lista słów kluczowych]` | `[klient / WE]` | `[n]` |

### Poza zakresem
- Produkcja contentu (chyba że potwierdzono dodatek `content`)
- Development strony lub zmiany CMS poza prostymi edycjami on-page
- Reklamy płatne (→ `google-ads`, `meta-ads`)
- Gwarantowane pozycje (nie oferujemy — nieetyczne)

---

## Pricing anchors
| Pozycja | Cena miesięczna |
|---|---|
| Retainer SEO (`[Starter / Growth]`) | `[£499 / £799]` |
| Dodatek content (`content`) | `[+£199/mc]` (opcjonalny) |
| Jednorazowy audyt techniczny (`audits`) | `[£299]` (jednorazowo w miesiącu 1) |
| **Suma miesięczna** | `[£XXX/mc]` |

**Minimalne zobowiązanie:** `[3 / 6 / 12]` miesięcy.  
**Warunki płatności:** Miesięcznie z góry.

---

## Kamienie milowe
| Miesiąc | Kluczowe deliverables |
|---|---|
| Miesiąc 1 | Audyt techniczny, badanie słów kluczowych, analiza konkurencji, raport bazowy, wstępne naprawy on-page |
| Miesiąc 2 | Ciągła optymalizacja on-page, rekomendacje contentu, pierwszy link-building outreach (Growth) |
| Miesiąc 3 | Pierwszy raport ruchu w rankingach, rozmowa przeglądowa strategii, aktualizacja planu contentu |
| Miesiąc 6 | Przegląd wydajności mid-contract, rozmowa o odnowieniu retainera |

---

## Upselle i cross-selle
| Możliwość | Wartość | Status |
|---|---|---|
| Dodatek tworzenia contentu (`content`) | £199/mc | `[zainteresowany / odrzucony / TBD]` |
| Google Ads (`google-ads`) | od £399/mc | `[zainteresowany / odrzucony / TBD]` |
| Meta Ads (`meta-ads`) | od £349/mc | `[zainteresowany / odrzucony / TBD]` |
| Plan opieki (`maintenance`) | od £149/mc | `[zainteresowany / odrzucony / TBD]` |
| Jednorazowy audyt bezpieczeństwa (`audits`) | £299 | `[zainteresowany / odrzucony / TBD]` |

---

## Ryzyka i zależności
- **Szybkość strony / dług techniczny** — jeśli audyt z miesiąca 1 ujawni poważne problemy, pierwsze 4–6 tygodni skupia się na naprawach, nie na contencie
- **Wąskie gardło contentu** — bez regularnych nowych treści wzrost słów kluczowych zatrzymuje się po początkowych zyskach on-page
- **Dostęp do edycji CMS** — musi być potwierdzony przed onboardingiem; ograniczony CMS = ograniczony zakres on-page
- **Aktualizacje algorytmu** — aktualizacje rdzenia Google mogą tymczasowo wpłynąć na pozycje; zakomunikuj to ryzyko z góry
- **Harmonogram link buildingu** — organiczne pozyskiwanie linków potrzebuje 3–6 miesięcy, żeby pokazać poprawę autorytetu domeny

---

## Założenia
- GA4 i GSC udostępnione i zweryfikowane przed startem audytu miesiąca 1
- Klient zapewnia login do CMS z dostępem edytora (lub WE zarządza przez dostęp developerski)
- Wyniki mierzone są względem uzgodnionych KPI, a nie absolutnych pozycji rankingowych
- Raportowanie używa tylko danych GSC + GA4 (bez próżnych metryk)

---

## Otwarte pytania
- [ ] Czy klient skonfigurował śledzenie konwersji w GA4? (Wymagane do atrybucji leadów / przychodów)
- [ ] Czy są strony, które klient chce wykluczyć z zakresu SEO?
- [ ] Czy Google Business Profile jest zweryfikowany i zarządzany przez klienta?
- [ ] Czy jest planowany redesign lub migracja strony, która mogłaby zresetować pozycje?
- [ ] Czy są sezonowe szczyty, z którymi kalendarz contentu powinien się alignować?

---

## Rekomendowany następny krok
1. Potwierdź poziom retainera i minimalny czas trwania umowy
2. Wystaw umowę retainera z harmonogramem deliverables miesiąca 1
3. Poproś o dostęp do GA4 + GSC + login CMS w ciągu 48 godzin od podpisania umowy
4. Zaplanuj kick-off call do wyrównania priorytetów słów kluczowych i rytmu raportowania
