# Google Ads (PPC) — Brief kwalifikacyjny
> Service: google-ads
> Market: PL
> Brief Type: Qualification
> Status: Wersja robocza
> Last Updated: 2026-04-17
> Source Anchors: ServiceItemSeeder.php, skrypt-sprzedazowy.md

---

## Cel
Określić, czy lead ma wystarczający budżet reklamowy, działającą stronę i śledzenie konwersji (lub gotowość do jego wdrożenia), aby prowadzić opłacalne kampanie Google Ads.

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
| Budżet reklamowy | ≥ £500/mc potwierdzony | Poniżej £300/mc |
| Opłata za zarządzanie | ≥ £399/mc zaakceptowana | Chce zarządzania za darmo lub za minimalną kwotę |
| Strona | Ma landing page z jasnym CTA | Brak strony lub tylko strona główna bez CTA |
| Śledzenie konwersji | Ma lub gotów skonfigurować | Odmawia dodania trackingu |
| Oczekiwania dotyczące harmonogramu | Rozumie rozruch 2–4 tygodnie | Chce ROI w 3 dni |
| Jasność oferty | Jasny produkt/usługa i docelowy klient | „Sprzedaję wszystko wszystkim" |

---

## Kryteria kwalifikacji

### Wymagania obligatoryjne
- [ ] Budżet reklamowy ≥ £500/mc (płatny do Google, oddzielnie od opłaty za zarządzanie)
- [ ] Opłata za zarządzanie ≥ £399/mc zaakceptowana
- [ ] Strona z co najmniej jedną akcją konwersji (formularz, telefon, zakup)
- [ ] Gotowość do wdrożenia śledzenia konwersji przed uruchomieniem kampanii
- [ ] Jasny produkt lub usługa ze zdefiniowanym docelowym klientem

### Czynniki podnoszące jakość dealu
- [ ] Istniejące konto Google Ads do audytu (dostarcza okazji na szybkie wygrane)
- [ ] Google Analytics / GA4 już skonfigurowane
- [ ] Zdefiniowany cel CPA lub ROAS
- [ ] Zainteresowanie grupami remarketingowymi (zwiększa efektywność kampanii)
- [ ] Landing page już zoptymalizowana pod konwersję

### Czerwone flagi
- 🚨 „Mój budżet to £200/mc razem — reklamy + zarządzanie"
- 🚨 „Nie zapłacę nic, dopóki nie zobaczę wyników"
- 🚨 „Sprzedaję globalnie wszystkim branżom bez fokusa"
- 🚨 Konkurenci mają znacznie wyższe budżety na wysoce konkurencyjnym rynku
- 🚨 Brak strony lub landing page w momencie startu kampanii
- 🚨 „Poprzednia agencja wydała mój budżet z zerowym wynikiem" — wymagany audyt przed zobowiązaniem

---

## Zakres i granice
**Minimalne zaangażowanie (£399/mc zarządzanie + £500/mc budżet):**
Kampanie Google Search, konfiguracja śledzenia konwersji, badanie słów kluczowych, treści reklam, miesięczne raportowanie.

**Zakres wymagający wyższego poziomu:**
- Kampanie Google Shopping → rekomendowany poziom Growth (£599/mc)
- Performance Max → poziom Growth
- Remarketing + warstwy grup odbiorców → poziom Growth
- Kampanie Display lub YouTube → poziom Scale lub wycena indywidualna

---

## Pricing anchors
| Scenariusz | Opłata za zarządzanie | Budżet reklamowy | Suma miesięczna |
|---|---|---|---|
| Starter (tylko Search) | **£399/mc** | **£500/mc** | **£899/mc** |
| Growth (Search + Shopping/PMax) | **£599/mc** | **£1 000/mc** | **£1 599/mc** |
| Scale (pełne konto) | **indywidualnie** | **£2 000+/mc** | **indywidualnie** |
| Bezpłatny audyt istniejącego konta | **£0** | — | Warunek wstępny |

---

## Ryzyka i zależności
- Brak śledzenia konwersji = brak sygnału optymalizacyjnego; kampanie będą gorzej działać
- Słaba landing page zmarnuje budżet reklamowy; połącz z `brochure-websites` jeśli potrzeba
- Konkurencyjny rynek (np. prawnicy, ubezpieczenia) = wysokie CPC; klient musi zaakceptować zmienne CPA
- Sezonowość: budżet reklamowy może wymagać skalowania w górę / w dół; elastyczność budżetu jest ważna

---

## Założenia
- Klient kontroluje lub może uzyskać dostęp do swojego konta Google Ads (lub gotów je założyć)
- Budżet reklamowy to osobna pozycja budżetowa, nie wliczona w opłatę za zarządzanie
- Minimalne zaangażowanie 3-miesięczne, żeby umożliwić uczenie się kampanii i optymalizację
- Dostęp do Google Tag Manager zapewniony do konfiguracji śledzenia konwersji

---

## Otwarte pytania
- [ ] Czy klient ma istniejące konto Google Ads? (Przeprowadź bezpłatny audyt)
- [ ] Czy śledzenie konwersji jest aktualnie aktywne?
- [ ] Jaka jest średnia wartość zamówienia / leada dla produktu/usługi?
- [ ] Czy klient określił maksymalne akceptowalne CPA?
- [ ] Czy istnieją listy wykluczeń słów kluczowych lub struktury kampanii?

---

## Rekomendowany następny krok
- **Zakwalifikowany** → Przeprowadź bezpłatny audyt konta (lub badanie słów kluczowych); przedstaw 3-miesięczną propozycję Starter
- **Warunkowo zakwalifikowany** → Najpierw zaadresuj lukę landing page lub śledzenia; wróć za 2–4 tygodnie
- **Niezakwalifikowany** → Przekieruj: jeśli potrzebny ruch organiczny, zaproponuj `seo`; jeśli zasięg w social, zaproponuj `meta-ads`
