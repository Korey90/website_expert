# Google Ads (PPC) — Brief discovery
> Service: google-ads
> Market: PL
> Brief Type: Discovery
> Status: Wersja robocza
> Last Updated: 2026-04-17
> Source Anchors: ServiceItemSeeder.php, skrypt-sprzedazowy.md, plan-kampanii.md

---

## Cel
Zrozumieć cele biznesowe klienta, historię reklamową, grupę docelową, cele konwersji i budżet, aby zdefiniować strategię Google Ads, która dostarcza mierzalne ROI od pierwszego miesiąca.

---

## Kontekst klienta
| Pole | Odpowiedź |
|---|---|
| Nazwa firmy | `[client_name]` |
| Adres strony | `[website_url]` |
| Branża | `[industry]` |
| Obecne konto Google Ads | Tak (potrzebny dostęp MCC) / Nie |
| Aktualny miesięczny budżet reklamowy | `[£XXX/mc lub „brak"]` |
| Główny cel | `[leady / sprzedaż / telefony / wizyty w sklepie]` |
| Docelowa geografia | `[miasto / region / ogólnopolski / międzynarodowy]` |
| Termin decyzji | `[decision_deadline]` |
| Orientacyjny budżet | `[management_fee_budget]` |

---

## Dopasowanie do oferty
Zarządzanie Google Ads od Website Expert pasuje firmom, które:
- Potrzebują natychmiastowego ruchu opartego na intencji (w przeciwieństwie do długoterminowego SEO)
- Mają stronę z jasną akcją konwersji (formularz, telefon, zakup)
- Są gotowe zainwestować minimum £500/mc w budżet reklamowy (oddzielnie od opłaty za zarządzanie)
- Chcą profesjonalnego zarządzania kampaniami Search, Shopping lub Performance Max ze śledzeniem konwersji

**Brak dopasowania gdy:** klient nie ma strony (→ najpierw `brochure-websites`). Jeśli klient chce zasięgu w social media zamiast intencji wyszukiwania (→ `meta-ads`). Jeśli klient nie może zaangażować minimalnego budżetu reklamowego.

---

## Przebieg discovery

### Biznes i produkt
> „Co reklamujesz — produkt, usługę czy obydwoje? Jaka jest średnia wartość transakcji?"
> „Co oznacza nowy klient w kategoriach przychodów? (LTV lub wartość pierwszej sprzedaży)"
> „Jaki jest Twój docelowy koszt na lead lub koszt na akwizycję?"

### Obecne reklamy
> „Czy aktualnie prowadzisz Google Ads? Co działa, a co nie?"
> „Czy masz wdrożone śledzenie konwersji — formularze, telefony, zakupy?"
> „Czy poprzednia agencja zarządzała Twoim kontem? Co się stało?"

### Konkurencja
> „Kim są Twoi główni konkurenci? Czy aktywnie reklamują się na Google?"
> „Czy korzystałeś z narzędzia podglądu reklam Google, żeby sprawdzić, kto licytuje na Twoje słowa?"

### Grupa docelowa i geografia
> „Kim jest Twój idealny klient — wiek, zawód, lokalizacja, intencja?"
> „Czy celujesz w konkretne miasto, region czy całą Polskę / UK?"

### Landing pages
> „Dokąd trafia ruch z reklam — strona główna, podstrona usługi, czy dedykowana landing page?"
> „Czy landing page jest zoptymalizowana pod konwersję? (Możemy to przejrzeć)"

### Budżet i oczekiwania
> „Jaki jest Twój planowany miesięczny budżet reklamowy? (Rekomendujemy minimum £500/mc na reklamy)"
> „Nasza opłata za zarządzanie zaczyna się od £399/mc — to jest oddzielne od budżetu reklamowego. Czy to pasuje do Twojego planowania?"
> „Które KPI są dla Ciebie najważniejsze — leady, ROAS, CPA, wyświetlenia?"

---

## Zakres i granice
**W zakresie (od £399/mc opłata za zarządzanie + minimum £500/mc budżet):**
- Kampanie Google Search, Shopping i/lub Performance Max
- Konfiguracja śledzenia konwersji + Google Tag Manager
- Badanie słów kluczowych i zarządzanie wykluczeniami
- Pisanie treści reklam i testy A/B
- Miesięczne raporty wydajności z podziałem na ROI
- Remarketing i targetowanie grup odbiorców

**Poza zakresem:**
- Reklamy Meta / Facebook / Instagram (→ `meta-ads`)
- SEO lub wzrost organiczny (→ `seo`)
- Kreacje reklamowe (display / wideo) — dostępne jako dodatek
- Tworzenie landing pages (→ `brochure-websites`)
- Produkcja reklam YouTube / wideo

---

## Pricing anchors
| Poziom | Opłata za zarządzanie | Minimalny budżet | Opis |
|---|---|---|---|
| Starter | **£399/mc** | £500/mc | Kampanie Search, śledzenie konwersji, miesięczny raport |
| Growth | **£599/mc** | £1 000/mc | + Shopping / PMax, remarketing, testy A/B |
| Scale | **indywidualnie** | £2 000+/mc | Pełne zarządzanie kontem, Display, Video |

*Budżet reklamowy jest płacony bezpośrednio do Google i jest oddzielny od opłaty za zarządzanie.*

- Dodatek: Tworzenie landing page → `brochure-websites` od £799
- Dodatek: Retainer SEO → `seo` od £499/mc
- Bezpłatny audyt istniejącego konta dostępny przed podpisaniem umowy

---

## Ryzyka i zależności
- **Brak śledzenia konwersji** — nie można optymalizować kampanii ani udowodnić ROI bez trackingu; musi być wdrożone w miesiącu 1
- **Słaba landing page** — ruch reklamowy konwertujący poniżej 1% sugeruje problem z landing page, nie z reklamami; może być potrzebne `brochure-websites` równolegle
- **Niski budżet reklamowy** — budżety poniżej £500/mc często nie zbierają wystarczająco danych do sensownej optymalizacji
- **Zmienność sezonowa** — CPC rosną w szczytowych sezonach (Boże Narodzenie, Black Friday); planowanie budżetu musi to uwzględniać
- **Historia konta** — przejęcie źle zarządzanego konta może wymagać restrukturyzacji przed poprawą wydajności

---

## Założenia
- Klient ma lub założy konto Google Ads (lub udzieli dostępu MCC do Website Expert)
- Śledzenie konwersji zostanie wdrożone przed uruchomieniem jakiejkolwiek kampanii
- Budżet reklamowy jest zaplanowany oddzielnie i dodatkowo do opłaty za zarządzanie
- Klient dostarcza wytyczne brandingowe i kluczowe komunikaty do treści reklam
- Preferowane minimalne zaangażowanie 3-miesięczne dla zebrania danych i optymalizacji

---

## Otwarte pytania
- [ ] Czy klient ma istniejące konto Google Ads? (Poproś o dostęp MCC tylko do odczytu na bezpłatny audyt)
- [ ] Czy śledzenie konwersji jest aktualnie skonfigurowane? Jeśli tak, jakie zdarzenia są śledzone?
- [ ] Jaka jest główna landing page — strona główna, podstrona usługi czy dedykowana LP?
- [ ] Czy klient określił docelowe CPA lub ROAS?
- [ ] Czy są sezonowe kampanie lub promocje do zaplanowania?

---

## Rekomendowany następny krok
1. Zaproponuj **bezpłatny audyt konta** (jeśli istniejące) lub **raport okazji słów kluczowych** (jeśli nowe)
2. Przedstaw szacunkowe CPC i projekcje wolumenu leadów dla docelowych słów kluczowych
3. Zaproponuj **3-miesięczny plan Starter** (£399/mc + £500/mc budżet reklamowy)
4. Połącz z landing page `brochure-websites` lub retainerem `seo` dla pełno-lejkowej strategii
