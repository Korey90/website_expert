# Meta / Pixel Ads — Brief discovery
> Service: meta-ads
> Market: PL
> Brief Type: Discovery
> Status: Wersja robocza
> Last Updated: 2026-04-17
> Source Anchors: ServiceItemSeeder.php, skrypt-sprzedazowy.md, plan-kampanii.md

---

## Cel
Zrozumieć cele biznesowe klienta, obecną obecność w social media, grupy docelowe, zasoby kreacyjne i gotowość budżetową, aby określić, czy Meta Ads (Facebook + Instagram) może dostarczyć mierzalne rezultaty i zdefiniować właściwe podejście do kampanii.

---

## Kontekst klienta
| Pole | Odpowiedź |
|---|---|
| Nazwa firmy | `[client_name]` |
| Adres strony | `[website_url]` |
| Branża | `[industry]` |
| URL Fanpage Facebook | `[facebook_url]` |
| Instagram | `[instagram_handle]` |
| Meta Pixel zainstalowany | Tak / Nie / Nieznane |
| Aktualny miesięczny budżet reklamowy | `[£XXX/mc lub „brak"]` |
| Główny cel | `[leady / sprzedaż / świadomość marki / instalacje aplikacji]` |
| Docelowa geografia | `[miasto / region / ogólnopolski / międzynarodowy]` |
| Termin decyzji | `[decision_deadline]` |
| Orientacyjny budżet | `[management_fee_budget]` |

---

## Dopasowanie do oferty
Zarządzanie Meta Ads od Website Expert pasuje firmom, które:
- Mają produkt lub usługę odpowiednią do odkrycia w social media (nie wyłącznie opartą na intencji)
- Mogą dostarczyć lub zatwierdzić zasoby kreacyjne (zdjęcia, wideo, treść)
- Są gotowe zainwestować minimum £300/mc w budżet reklamowy (oddzielnie od opłaty za zarządzanie)
- Chcą dotrzeć do niestandardowych, podobnych lub zainteresowaniowych grup odbiorców na Facebooku i Instagramie

**Brak dopasowania gdy:** klient potrzebuje wyłącznie ruchu opartego na intencji (→ `google-ads`). Brak produktu lub zasobów wizualnych. Budżet poniżej £300/mc na reklamy.

---

## Przebieg discovery

### Biznes i produkt
> „Co sprzedajesz — produkt, usługę czy obydwoje? Jaka jest średnia wartość transakcji lub leada?"
> „Kim jest Twój idealny klient — wiek, styl życia, lokalizacja, zainteresowania, problemy?"
> „Co wyróżnia Cię od konkurentów pojawiających się w tych samych feedach?"

### Obecna obecność w social mediach
> „Masz aktywny Fanpage na Facebooku i konto na Instagramie?"
> „Czy Meta Pixel jest zainstalowany na stronie? Czy są skonfigurowane niestandardowe zdarzenia?"
> „Czy uruchamiałeś wcześniej Meta Ads? Co działało, co nie?"

### Cele i KPI
> „Jaki jest główny cel — leady, bezpośrednia sprzedaż, świadomość marki czy retargeting obecnych odwiedzających?"
> „Jak wygląda dla Ciebie udana kampania — koszt na lead, ROAS, zasięg?"
> „W jakim czasie oczekujesz znaczących wyników?"

### Zasoby kreacyjne
> „Masz zdjęcia marki, wideo lub zdjęcia produktów, których możemy użyć?"
> „Czy możesz zatwierdzać treść reklam i kreację przed publikacją?"
> „Czy masz nadchodzące promocje sezonowe, oferty lub premiery produktów?"

### Landing pages i lejek
> „Gdzie trafia ruch z reklam — strona główna, strona produktu czy dedykowana landing page?"
> „Czy jest jasna akcja konwersji — formularz, kasa, połączenie telefoniczne?"
> „Czy prowadzisz kampanię remarketingową dla odwiedzających stronę?"

### Budżet
> „Jaki jest Twój planowany miesięczny budżet reklamowy? (Rekomendujemy minimum £300/mc)"
> „Nasza opłata za zarządzanie zaczyna się od £349/mc — to jest oddzielne od budżetu reklamowego. Czy pasuje to do Twojego planowania?"
> „Rozważasz też Google Ads? Możemy prowadzić obie platformy z skoordynowaną strategią."

---

## Zakres i granice
**W zakresie (od £349/mc opłata za zarządzanie + minimum £300/mc budżet):**
- Konfiguracja i zarządzanie kampaniami na Facebooku i Instagramie
- Konfiguracja Meta Pixel i Conversions API
- Targetowanie niestandardowych, podobnych i zainteresowaniowych grup odbiorców
- Tworzenie treści reklam i kierunek kreacyjny (pozyskiwanie zasobów lub dostarczone przez klienta)
- Formaty reklam: karuzela, obraz statyczny, wideo, Reels, Lead Form
- Kampanie remarketingowe dla odwiedzających stronę
- Miesięczne raporty wyników: metryki ROAS + CPA

**Poza zakresem:**
- Kampanie Google Ads lub reklamy w wyszukiwarce (→ `google-ads`)
- Organiczne zarządzanie social media lub publikowanie postów
- Pełna produkcja wideo (drobna edycja w cenie; pełna produkcja to dodatek)
- Budowa strony lub landing page (→ `brochure-websites`)
- Kampanie e-mail marketingowe

---

## Pricing anchors
| Poziom | Opłata za zarządzanie | Minimalny budżet | Opis |
|---|---|---|---|
| Starter | **£349/mc** | £300/mc | Podstawowe kampanie, Pixel, raportowanie |
| Growth | **£499/mc** | £600/mc | + Lookalike audiences, dynamiczne reklamy produktowe, Reels |
| Scale | **indywidualnie** | £1 500+/mc | Pełny lejek, wiele kampanii, pełna produkcja kreacyjna |

*Budżet reklamowy jest płacony bezpośrednio do Meta i jest oddzielny od opłaty za zarządzanie.*

- Dodatek: Budowa landing page → `brochure-websites` od £799
- Dodatek: Retainer tworzenia treści → `content` od £199/mc
- Dodatek: Google Ads → `google-ads` od £399/mc zarządzanie

---

## Ryzyka i zależności
- **Brak Meta Pixel** — bez śledzenia konwersji optymalizacja kampanii jest ograniczona do metryk górnej części lejka; musi być skonfigurowany przed startem
- **Brak zasobów kreacyjnych** — kampanie Meta opierają się mocno na wizualiach; słaba kreacja = słabe wyniki niezależnie od budżetu
- **Wąska grupa docelowa** — wysoce niszowe grupy B2B słabo działają na Meta; `google-ads` może być lepszym dopasowaniem dla B2B opartego na intencji
- **Ograniczenia iOS 14+** — zmiany prywatności Apple zmniejszyły dokładność atrybucji Meta; klienci muszą rozumieć, że dane mogą być zaniżone
- **Ograniczenia polityk** — niektóre branże (nieruchomości, finanse, zatrudnienie) podlegają specjalnym kategoriom reklam Meta z ograniczonym targetowaniem

---

## Założenia
- Klient ma lub założy Facebook Business Manager i powiązane konto reklamowe
- Meta Pixel zostanie zainstalowany i zweryfikowany przed uruchomieniem kampanii
- Klient dostarcza zasoby marki (logo, zdjęcia) lub zatwierdza kreację Website Expert
- Minimalne zaangażowanie 3-miesięczne, żeby umożliwić uczenie się grup i optymalizację
- Budżet reklamowy jest osobnym zobowiązaniem finansowym od opłaty za zarządzanie

---

## Otwarte pytania
- [ ] Czy Meta Pixel jest aktualnie zainstalowany? Czy zdarzenia konwersji się uruchamiają?
- [ ] Czy klient ma istniejące niestandardowe grupy odbiorców (odwiedzający stronę, lista e-mail)?
- [ ] Jakie zdjęcia produktu/usługi lub wideo są dostępne na kreację?
- [ ] Czy są nadchodzące promocje, premiery lub szczytowe sezony?
- [ ] Czy zaangażowany jest e-commerce lub feed katalogu produktów (dla dynamicznych reklam produktowych)?

---

## Rekomendowany następny krok
1. Przeprowadź bezpłatny audyt konta Meta (jeśli istniejące) lub ćwiczenie szacowania wielkości grup odbiorców
2. Przedstaw brief kreacyjny specyficzny dla platformy i strukturę kampanii startowej
3. Zaproponuj **3-miesięczny plan Starter** (£349/mc + £300/mc budżet reklamowy)
4. Połącz z retainerem `content` jeśli zasoby kreacyjne są ograniczone, lub `google-ads` dla pełnej strategii płatnych mediów
