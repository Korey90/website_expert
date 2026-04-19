# Audyty bezpieczeństwa i wydajności — Brief discovery
> Service: audits
> Market: PL
> Brief Type: Discovery
> Status: Wersja robocza
> Last Updated: 2026-04-17
> Source Anchors: ServiceItemSeeder.php, skrypt-sprzedazowy.md

---

## Cel
Zrozumieć stos technologiczny strony klienta, obawy dotyczące bezpieczeństwa, problemy z wydajnością i kontekst biznesowy, aby zaprojektować i pozycjonować audyt jako wyraźny, niskoryzykowy punkt wejścia identyfikujący konkretne, możliwe do realizacji problemy.

---

## Kontekst klienta
| Pole | Odpowiedź |
|---|---|
| Nazwa firmy | `[client_name]` |
| Adres strony | `[website_url]` |
| Branża | `[industry]` |
| CMS / platforma | `[WordPress / Shopify / własna / inna]` |
| Dostawca hostingu | `[hosting_provider]` |
| Ostatni przegląd bezpieczeństwa | `[nigdy / miesiąc-rok / nieznane]` |
| Znane problemy | `[wolne ładowanie / włamanie w przeszłości / zidentyfikowane luki / brak]` |
| Przetwarzane dane | `[dane klientów / płatności / brak]` |
| Termin decyzji | `[decision_deadline]` |
| Orientacyjny budżet | `[one-off_budget]` |

---

## Dopasowanie do oferty
Audyt bezpieczeństwa i wydajności Website Expert pasuje firmom, które:
- Mają stronę, która nie była profesjonalnie przeglądana pod kątem bezpieczeństwa lub wydajności
- Martwią się lukami bezpieczeństwa, przestarzałymi wtyczkami lub wolnym ładowaniem wpływającym na konwersje
- Potrzebują obiektywnej, priorytetyzowanej listy napraw przed zobowiązaniem do większego zaangażowania deweloperskiego
- Chcą zrozumieć swoje narażenie na ryzyko przed odnowieniem lub anulowaniem umowy hostingowej lub serwisowej

**Brak dopasowania gdy:** Strona jest w trakcie natychmiastowej przebudowy (wyniki audytu mogą być nieistotne). Klient w ogóle nie ma strony. Klient chce stałego monitorowania zamiast jednorazowego raportu (→ `maintenance`).

---

## Przebieg discovery

### Kontekst biznesowy
> „Czy Twoja strona jest krytyczna dla biznesu — czy klienci rezerwują, kupują lub kontaktują się przez nią?"
> „Czy doświadczyłeś jakichkolwiek incydentów bezpieczeństwa — włamanie, malware, naruszenie danych lub nieoczekiwane przekierowania?"
> „Czy przechowujesz lub przetwarzasz jakiekolwiek dane osobowe klientów lub płatności kartą?"

### Obecny stos technologiczny
> „Na jakim CMS lub platformie jest zbudowana strona? (WordPress, Shopify, własna?)"
> „Kto hostuje Twoją stronę i czy SSL/HTTPS jest aktualnie aktywny?"
> „Kiedy ostatnio aktualizowane były wtyczki, szablony lub rdzeń CMS?"

### Obawy dotyczące wydajności
> „Czy klienci lub pracownicy skarżyli się, że strona jest wolna?"
> „Czy znasz swoje aktualne wyniki Google PageSpeed lub Core Web Vitals?"
> „Czy straciłeś ostatnio jakiekolwiek pozycje Google, co może wskazywać na problem Core Web Vitals?"

### Obawy dotyczące bezpieczeństwa
> „Czy Twoja strona była flagowana przez Google Safe Browsing lub narzędzia antywirusowe?"
> „Czy są nieużywane konta użytkowników, stare wtyczki lub zewnętrzne skrypty działające na stronie?"
> „Czy masz wymagania compliance (RODO, PCI DSS, ISO 27001), które strona musi spełniać?"

### Oczekiwane wyniki
> „Jak wygląda dla Ciebie udany audyt — lista napraw, wynik ryzyka czy obydwoje?"
> „Kto wewnętrznie wdroży wyniki — Twój deweloper, dostawca hostingu czy my?"
> „Czy celem jest samodzielne naprawienie problemów, czy chciałbyś, żebyśmy wdrożyli poprawki?"

---

## Zakres i granice
**W zakresie (£299 jednorazowo):**
- Skan luk bezpieczeństwa OWASP Top 10
- Ocena Core Web Vitals: LCP, CLS, INP
- Przegląd konfiguracji serwera i SSL/HTTPS
- Audyt zależności i CVE (Common Vulnerability Exposure)
- Priorytetyzowana lista napraw z szacunkami nakładu pracy
- Raport PDF + 1-godzinne omówienie wyników (w ciągu 5–7 dni roboczych)

**Poza zakresem:**
- Testy penetracyjne / etyczne hakowanie (osobna wycena, usługa specjalistyczna)
- Naprawianie zidentyfikowanych problemów (→ może być wycenione jako następna praca)
- Stałe monitorowanie po audycie (→ retainer `maintenance`)
- Audyt SEO słów kluczowych lub linków (→ retainer `seo`)
- Pełna przebudowa strony (→ `brochure-websites` lub `web-applications`)

---

## Pricing anchors
| Pozycja | Cena |
|---|---|
| Audyt bezpieczeństwa i wydajności | **£299 jednorazowo** |
| Termin dostarczenia | **5–7 dni roboczych** |
| Deliverables | Raport PDF + 1-godzinne omówienie |
| Wdrożenie napraw po audycie | **wycena indywidualna** |

- Upsell po audycie: `maintenance` od £149/mc (monitorowanie + aktualizacje)
- Upsell po audycie: `seo` od £499/mc (jeśli problemy wydajności wpływają na pozycje)
- Upsell po audycie: `web-applications` jeśli strona wymaga znaczącej przebudowy

---

## Ryzyka i zależności
- **Wymagania dostępowe** — audyt wymaga dostępu tylko do odczytu lub ograniczonego do panelu hostingu, backendu CMS i najlepiej ustawień DNS/SSL; potwierdź, że dostęp może być zapewniony
- **Scope creep** — klient może oczekiwać napraw wliczonych w cenę £299; wyraźnie zaznacz, że audyt = tylko raport
- **Zależność harmonogramowa** — dostarczenie w 5–7 dni roboczych zależy od szybkiego otrzymania danych dostępowych
- **Branże regulowane** — strony opieki zdrowotnej, finansowe lub prawne mogą mieć dodatkowe wymagania compliance poza OWASP/Core Web Vitals

---

## Założenia
- Klient zapewnia dostęp tylko do odczytu lub staging do CMS i panelu kontrolnego hostingu
- Strona jest aktualnie aktywna i dostępna (nie w trybie maintenance)
- Klient akceptuje, że audyt za £299 obejmuje tylko ocenę i raport; naprawy są wyceniane osobno
- 1-godzinne omówienie jest zaplanowane w ciągu 10 dni roboczych od dostarczenia raportu

---

## Otwarte pytania
- [ ] Na jakiej platformie / CMS jest zbudowana strona?
- [ ] Kto może zapewnić dostęp do hostingu i CMS na czas audytu?
- [ ] Czy strona była zaangażowana w jakiekolwiek incydenty bezpieczeństwa lub otrzymała ostrzeżenia?
- [ ] Czy są wymagania compliance (RODO, PCI, ISO) do sprawdzenia?
- [ ] Czy klient szuka jednorazowego health check czy stałego maintenance po audycie?

---

## Rekomendowany następny krok
1. Potwierdź URL strony, platformę i metodę dostępu
2. Wystaw umowę audytu (£299 jednorazowo, dostarczenie w 5–7 dni roboczych)
3. Zaplanuj termin omówienia wyników przy podpisaniu
4. Po audycie: przedstaw retainer `maintenance` lub wycenę wdrożenia napraw na podstawie wyników
