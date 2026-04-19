# Maintenance strony — Brief do oferty
> Service: maintenance
> Market: PL
> Brief Type: Proposal Input
> Status: Wersja robocza
> Last Updated: 2026-04-17
> Source Anchors: ServiceItemSeeder.php, skrypt-sprzedazowy.md

---

## Cel
Zebrać wszystkie szczegóły potrzebne do wystawienia umowy retainera serwisowego i rozpoczęcia onboardingu: dane strony, wymagania dostępowe, obecny stan kopii zapasowych i aktualizacji, decyzja o hostingu, potwierdzenie wyceny i możliwości upsell.

---

## Kontekst klienta
| Pole | Odpowiedź |
|---|---|
| Nazwa klienta | `[client_name]` |
| Adres strony | `[website_url]` |
| Branża | `[industry]` |
| Główny kontakt | `[contact_name]`, `[email]`, `[phone]` |
| Decydent potwierdzony | `[name]` |
| CMS / platforma | `[WordPress / Shopify / własna]` |
| Obecny dostawca hostingu | `[provider]` |
| Aktualna wersja WordPress | `[x.x.x / nieznana]` |
| Ostatnia aktualizacja wtyczek | `[data / nieznana]` |
| Istniejące kopie zapasowe | Tak (gdzie: `[lokalizacja]`) / Nie |
| Zarządzany hosting wymagany | Tak / Nie |
| Planowany start umowy | `[data]` |

---

## Dopasowanie do oferty
- Usługa: **Maintenance strony** (slug: `maintenance`)
- Dopasowanie potwierdzone: Tak
- Główny driver: `[gwarancja dostępności / zarządzanie aktualizacjami / bezpieczeństwo / odtwarzanie z kopii / SLA odpowiedzi]`
- Kluczowa wartość dla klienta: `[np. spokój, usunięcie obciążenia zarządzaniem, 2-godzinna odpowiedź na krytyczne problemy]`

---

## Zakres i granice

### Usługi wliczone
| Usługa | Wliczone | Częstotliwość | Uwagi |
|---|---|---|---|
| Aktualizacje wtyczek / szablonów / rdzenia CMS | ✅ | Po wydaniu (miesięczny sweep) | Specyficzne dla WordPress dla aktualizacji CMS |
| Monitoring dostępności 24/7 + natychmiastowe alerty | ✅ | Ciągłe | Próg alertu: `[1min / 5min]` |
| Cotygodniowe kopie zapasowe offsite | ✅ | Cotygodniowo | 30-dniowa retencja |
| Miesięczny raport wydajności i bezpieczeństwa | ✅ | Miesięcznie | Dostarczany do `[5. / 10.]` dnia miesiąca |
| Priorytetowe naprawianie błędów | ✅ | Na żądanie | 2-godzinny SLA odpowiedzi na problemy krytyczne |
| Odnowienie certyfikatu SSL | ✅ | Roczne | Automatycznie odnawiane tam gdzie możliwe |
| Zarządzanie DNS | ✅ | Na żądanie | Do `[n]` zmian rekordów DNS/mc |

### Zarządzany hosting (jeśli dotyczy)
| Element | Status |
|---|---|
| Dodatek zarządzanego hostingu | £29/mc (`[potwierdzony / odrzucony]`) |
| Migracja od obecnego hosta | `[wymagana / niewymagana]` |
| Harmonogram migracji | `[data lub do ustalenia]` |
| Transfer domeny | `[wymagany / niewymagany]` |

### Ocena obecnego stanu strony
| Element | Status | Ryzyko |
|---|---|---|
| Wersja WordPress / CMS | `[aktualna / przestarzała]` | `[Wysokie / Średnie / Niskie]` |
| Wtyczki ostatnio aktualizowane | `[data]` | `[Wysokie / Średnie / Niskie]` |
| Kopie zapasowe potwierdzone | `[tak / nie]` | `[Wysokie / Średnie / Niskie]` |
| SSL aktywny | `[tak / nie]` | `[Wysokie / Średnie / Niskie]` |
| Ostatnie ostrzeżenia bezpieczeństwa | `[tak / nie]` | `[Wysokie / Średnie / Niskie]` |
| Audyt przeprowadzony | `[tak (data) / nie]` | `[rekomenduj przed startem]` |

### Poza zakresem
- Nowy development funkcji (→ osobna wycena developerska)
- Zmiany designu lub treści (→ osobna wycena)
- Strategia SEO i optymalizacja (→ retainer `seo`)
- Zarządzanie płatnymi reklamami (→ `google-ads` / `meta-ads`)
- Wstępne usuwanie skutków naruszenia bezpieczeństwa jeśli strona jest aktywnie zainfekowana w momencie onboardingu (→ wycena usuwania skutków awaryjnych)

---

## Pricing anchors
| Pozycja | Koszt miesięczny |
|---|---|
| Standardowy Maintenance | **£149/mc** |
| Dodatek zarządzanego hostingu (opcjonalny) | **+ £29/mc** |
| **Suma miesięczna** | **`[£149 lub £178]/mc`** |

**Warunki płatności:** Miesięcznie z góry, rolling contract. Wymagane 30-dniowe pisemne wypowiedzenie.

Jednorazowy setup onboardingowy: wliczony w pierwszy miesiąc.
Usuwanie skutków awaryjnych (jeśli wymagane przy onboardingu): wycena indywidualna.

---

## Kamienie milowe
| Faza | Termin | Kluczowe działania |
|---|---|---|
| Tydzień 1 | Onboarding | Dane dostępowe otrzymane, narzędzia monitorowania zainstalowane, kopie zapasowe skonfigurowane |
| Tydzień 1 | Baseline check | Wersja CMS, audyt wtyczek, status SSL potwierdzony |
| Tydzień 2 | Pierwsze aktualizacje | Wtyczki, szablony i rdzeń CMS zaktualizowane do najnowszych stabilnych wersji |
| Tydzień 2 | Kopie zapasowe | Pierwsza kopia zapasowa offsite zweryfikowana i odtwarzanie przetestowane |
| Koniec miesiąca 1 | Pierwszy raport | Miesięczny raport wydajności i bezpieczeństwa dostarczony |
| Miesiąc 2 wzwyż | Bieżące | Miesięczny cykl aktualizacji, alerty monitorowania, raporty |
| Miesiąc 3 | Przegląd | Przegląd jakości usługi; omówienie dodatkowych potrzeb |

---

## Upsells i cross-sells
| Możliwość | Wartość | Status |
|---|---|---|
| Audyt bezpieczeństwa i wydajności (`audits`) | £299 jednorazowo | `[zrealizowany / rekomendowany / odrzucony]` |
| Retainer SEO (`seo`) | od £499/mc | `[zainteresowany / odrzucony / do ustalenia]` |
| Tworzenie treści (`content`) | od £199/mc | `[zainteresowany / odrzucony / do ustalenia]` |
| Przebudowa strony (`brochure-websites`) | od £799 | `[zainteresowany / odrzucony / do ustalenia]` |
| Dodatek zarządzanego hostingu | £29/mc | `[potwierdzony / odrzucony]` |

---

## Ryzyka i zależności
- **Mocno przestarzały CMS** — jeśli WordPress lub rdzeń CMS jest znacząco nieaktualny, główne aktualizacje mogą wymagać testowania w środowisku staging przed wdrożeniem na produkcję; wymagana zgoda klienta
- **Brak kopii zapasowych przy onboardingu** — priorytetem pierwszego miesiąca jest konfiguracja kopii; do momentu weryfikacji strona nie ma disaster recovery
- **Złożone środowisko hostingowe** — zamknięte konta resellera lub niestandardowy hosting mogą ograniczać instalację narzędzi monitorowania
- **Zewnętrzni deweloperzy** — jeśli klient ma innych deweloperów z dostępem, możliwe są konflikty aktualizacji; koordynacja zarządzania dostępem
- **Aktywnie zainfekowana strona** — jeśli strona jest już zhackowana lub zainfekowana, wymagane jest awaryjne usuwanie skutków przed standardowym maintenance (dodatkowy koszt)

---

## Założenia
- Klient zapewnia pełen dostęp admin do CMS i panelu kontrolnego hostingu przed startem miesiąca 1
- Rolling monthly contract — wypowiedzenie wymaga 30-dniowego pisemnego zawiadomienia
- Standardowy maintenance obejmuje standardowe platformy CMS (WordPress); niestandardowe platformy do uzgodnienia na piśmie
- 2-godzinny SLA dotyczy problemów krytycznych (strona down, naruszenie bezpieczeństwa) — niekrytyczne prośby w ciągu 24 godzin
- Migracja zarządzanego hostingu (jeśli dotyczy) zaplanowana w ciągu pierwszych 30 dni

---

## Otwarte pytania
- [ ] Czy dostęp admin do CMS i hostingu został potwierdzony?
- [ ] Czy są aktualnie aktywne ostrzeżenia bezpieczeństwa, malware lub znane problemy?
- [ ] Czy wymagana lub preferowana jest migracja do zarządzanego hostingu?
- [ ] Czy przeprowadzono health check `audits`? (Rekomenduj jeśli nie)
- [ ] Czy są zewnętrzni deweloperzy lub agencje z obecnym dostępem do strony do koordynacji?

---

## Rekomendowany następny krok
1. Jeśli brak ostatniego audytu: przeprowadź `audits` (£299) przed onboardingiem dla czystego baseline
2. Potwierdź dane CMS, hostingu i dostępu admin
3. Wystaw umowę maintenance (£149/mc ± £29/mc hosting) z 30-dniowym wypowiedzeniem
4. Zaplanuj call onboardingowy — zainstaluj narzędzia monitorowania i skonfiguruj kopie zapasowe w tygodniu 1
