# Maintenance strony — Brief discovery
> Service: maintenance
> Market: PL
> Brief Type: Discovery
> Status: Wersja robocza
> Last Updated: 2026-04-17
> Source Anchors: ServiceItemSeeder.php, skrypt-sprzedazowy.md

---

## Cel
Zrozumieć obecną sytuację zarządzania stroną klienta, narażenie na ryzyko i wymagania operacyjne, aby pozycjonować retainer serwisowy jako niezbędną infrastrukturę ciągłą — nie opcjonalny dodatek.

---

## Kontekst klienta
| Pole | Odpowiedź |
|---|---|
| Nazwa firmy | `[client_name]` |
| Adres strony | `[website_url]` |
| Branża | `[industry]` |
| CMS / platforma | `[WordPress / Shopify / własna / inna]` |
| Dostawca hostingu | `[hosting_provider]` |
| Kto aktualnie zarządza stroną | `[in-house / stara agencja / nikt / nieznane]` |
| Ostatnia aktualizacja / aktualizacja wtyczek | `[nigdy / miesiąc-rok / nieznane]` |
| Kopie zapasowe | Tak / Nie / Nieznane |
| Krytyczność biznesowa | `[rezerwacje / sprzedaż / leady / tylko informacje]` |
| Termin decyzji | `[decision_deadline]` |
| Orientacyjny budżet | `[monthly_budget]` |

---

## Dopasowanie do oferty
Maintenance strony od Website Expert pasuje firmom, które:
- Mają aktywną stronę generującą przychody, leady lub rezerwacje
- Aktualnie nie mają nikogo, kto aktywnie aktualizuje, monitoruje lub tworzy kopie zapasowe strony
- Doświadczyły lub obawiają się przestojów, włamań lub awarii strony
- Chcą spokoju z zdefiniowanym SLA odpowiedzi (2-godzinna odpowiedź na problemy krytyczne)

**Brak dopasowania gdy:** Strona jest w trakcie natychmiastowej przebudowy. Klient zarządza w pełni własną aplikacją enterprise z wewnętrznym zespołem DevOps. Klient ma statyczną stronę HTML bez CMS i zerowych wymagań aktualizacyjnych.

---

## Przebieg discovery

### Obecne zarządzanie stroną
> „Kto opiekuje się Twoją stroną dziś — osoba in-house, Twoja stara agencja czy nikt?"
> „Kiedy ostatnio aktualizowane były wtyczki, szablony lub rdzeń CMS?"
> „Czy masz pewność, że kopie zapasowe istnieją i mogłyby być użyte do szybkiego przywrócenia strony?"

### Ryzyko i niezawodność
> „Czy Twoja strona kiedykolwiek niespodziewanie wypadła? Jak długo trwała naprawa?"
> „Czy byłeś kiedykolwiek zhackowany lub miałeś malware na stronie?"
> „Czy otrzymujesz alerty monitorowania, jeśli strona pójdzie offline?"

### Wpływ biznesowy
> „Ile przychodów lub biznesu tracisz, jeśli Twoja strona jest niedostępna przez 1 godzinę? Przez 1 dzień?"
> „Czy klienci rezerwują, kupują lub kontaktują się z Tobą przez stronę?"
> „Czy prowadzisz płatne kampanie reklamowe, które kierują ruch na stronę? Przestój to zmarnowane wydatki."

### Aktualizacje i wydajność
> „Czy wiesz, którą wersję WordPress (lub Twojego CMS) używasz?"
> „Czy są wtyczki, które nie były aktualizowane od miesięcy — czy znasz zagrożenia?"
> „Czy zauważyłeś, że strona zwalnia lub otrzymujesz ostrzeżenia bezpieczeństwa?"

### Wsparcie i czas odpowiedzi
> „Kiedy coś się psuje na stronie, do kogo dzwonisz? Jak długo zazwyczaj to trwa?"
> „Czy 2-godzinny SLA odpowiedzi na krytyczne naprawy dałby Ci znaczącą ciągłość biznesową?"
> „Czy są planowane większe aktualizacje lub kampanie, gdzie potrzebowałbyś szybkiego wsparcia?"

---

## Zakres i granice
**W zakresie (od £149/mc):**
- Aktualizacje wtyczek, szablonów i rdzenia CMS
- Monitoring dostępności 24/7 z natychmiastowymi alertami
- Cotygodniowe kopie zapasowe offsite z 30-dniową retencją
- Miesięczny raport wydajności i bezpieczeństwa
- Priorytetowe naprawianie błędów z 2-godzinnym SLA odpowiedzi
- Odnowienie certyfikatu SSL i zarządzanie DNS

**Opcjonalny dodatek:**
- Zarządzany hosting: od £29/mc

**Poza zakresem:**
- Nowe funkcje lub zmiany designu (→ wyceniane osobno)
- Poprawa SEO (→ retainer `seo`)
- Audyt bezpieczeństwa / wstępny health check (→ `audits` jednorazowo, dobry punkt wejścia)
- Zarządzanie Google Ads, Meta Ads

---

## Pricing anchors
| Plan | Opłata miesięczna | Kluczowe elementy |
|---|---|---|
| Standardowy | **£149/mc** | Aktualizacje, monitoring, kopie zapasowe, 2h SLA, SSL/DNS |
| + Zarządzany hosting | **+ £29/mc** | Zoptymalizowane środowisko hostingowe w cenie |

- Punkt wejścia: `audits` (£299 jednorazowo) → ujawnia problemy uzasadniające maintenance
- Cross-sell: `seo` od £499/mc (monitoring wydajności zasila SEO)
- Cross-sell: `brochure-websites` jeśli strona wymaga przebudowy przed sensownym maintenance

---

## Ryzyka i zależności
- **Wersja WordPress / CMS** — mocno przestarzałe instalacje mogą wymagać pracy naprawczej przed ustaleniem czystego baseline maintenance
- **Brak istniejących kopii zapasowych** — jeśli kopie zapasowe nie istnieją, nie ma punktu przywracania; pierwszym priorytetem jest ustanowienie schematu kopii zapasowych
- **Nieznane dane dostępowe** — jeśli klient nie ma dostępu do hostingu ani admina CMS, onboarding jest zablokowany
- **Hosting zewnętrzny** — jeśli hosting klienta jest u dostawcy ograniczającego dostęp (np. zamknięte konta resellera), może być wymagany dodatek zarządzanego hostingu dla pełnego monitorowania

---

## Założenia
- Klient ma lub może uzyskać dostęp administracyjny do CMS i panelu kontrolnego hostingu
- Strona jest na standardowym CMS (preferowany WordPress) lub platformie obsługiwanej przez Website Expert
- Potrzebna będzie 1-godzinna sesja onboardingowa do konfiguracji narzędzi monitorowania i kopii zapasowych
- Minimalne zaangażowanie miesięczne (rolling, z 30-dniowym wypowiedzeniem)

---

## Otwarte pytania
- [ ] Kto aktualnie posiada dostęp administracyjny do CMS strony i hostingu?
- [ ] Czy istnieją kopie zapasowe — gdzie są przechowywane i jak są aktualne?
- [ ] Co jest największą obawą klienta — bezpieczeństwo, dostępność, aktualizacje czy czas odpowiedzi?
- [ ] Czy potrzebna jest migracja do zarządzanego hostingu, czy klient zostaje przy swoim obecnym?
- [ ] Czy ostatnio przeprowadzono audyt `audits` jednorazowo? (Idealny punkt wejścia dla nowych klientów maintenance)

---

## Rekomendowany następny krok
1. Jeśli brak ostatniego audytu: rekomenduj `audits` (£299) do ustalenia baseline strony przed startem maintenance
2. Potwierdź dane CMS, hostingu i dostępu
3. Zaproponuj **standardowy plan Maintenance** (£149/mc) ± zarządzany hosting (£29/mc)
4. Wystaw rolling monthly agreement z 30-dniowym wypowiedzeniem
