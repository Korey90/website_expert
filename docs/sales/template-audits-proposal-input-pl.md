# Audyty bezpieczeństwa i wydajności — Brief do oferty
> Service: audits
> Market: PL
> Brief Type: Proposal Input
> Status: Wersja robocza
> Last Updated: 2026-04-17
> Source Anchors: ServiceItemSeeder.php, skrypt-sprzedazowy.md

---

## Cel
Zebrać wszystkie szczegóły potrzebne do wystawienia umowy audytu i rozpoczęcia zaangażowania: dane strony, wymagania dostępowe, konkretne obawy do priorytetyzacji, harmonogram deliverables i możliwości upsell po audycie.

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
| Dostawca hostingu | `[provider]` |
| SSL aktywny | Tak / Nie / Nieznane |
| Audyt uzgodniony | Tak — £299 jednorazowo |
| Termin omówienia wyników | `[data]` |

---

## Dopasowanie do oferty
- Usługa: **Audyt bezpieczeństwa i wydajności** (slug: `audits`)
- Dopasowanie potwierdzone: Tak
- Główna obawa klienta: `[luki bezpieczeństwa / wolna strona / compliance / ogólny health check]`
- Kluczowa wartość: Obiektywny, priorytetyzowany raport ryzyka z możliwą do realizacji listą napraw

---

## Zakres i granice

### Lista kontrolna zakresu audytu
| Obszar | Wliczony | Priorytet (W/Ś/N) |
|---|---|---|
| Skan luk bezpieczeństwa OWASP Top 10 | ✅ | `[W/Ś/N]` |
| Core Web Vitals: LCP | ✅ | `[W/Ś/N]` |
| Core Web Vitals: CLS | ✅ | `[W/Ś/N]` |
| Core Web Vitals: INP | ✅ | `[W/Ś/N]` |
| Przegląd konfiguracji serwera | ✅ | `[W/Ś/N]` |
| Konfiguracja SSL/HTTPS | ✅ | `[W/Ś/N]` |
| Audyt zależności i CVE | ✅ | `[W/Ś/N]` |
| Przegląd przestarzałych wtyczek / szablonów | ✅ (tylko WordPress) | `[W/Ś/N]` |
| Przegląd konfiguracji DNS | ✅ | `[W/Ś/N]` |

### Dodatkowe obawy (określone przez klienta)
| Obawa | Źródło | Uwagi |
|---|---|---|
| `[np. Ostatnie ostrzeżenie malware]` | `[Raport klienta / Google Search Console]` | `[Priorytetyzuj]` |
| `[np. Wolny checkout]` | `[Skargi klientów]` | `[Skup się na LCP/INP]` |

### Wymagania dostępowe
| Zasób | Wymagany | Status |
|---|---|---|
| Backend CMS (admin) | Tylko do odczytu / pełny | `[udzielony / oczekujący]` |
| Panel kontrolny hostingu | Tylko do odczytu | `[udzielony / oczekujący]` |
| Panel zarządzania DNS | Tylko do odczytu | `[udzielony / oczekujący]` |
| Google Search Console | Dostęp do podglądu | `[udzielony / oczekujący]` |
| Google Analytics / GA4 | Dostęp do podglądu | `[udzielony / oczekujący]` |

### Poza zakresem
- Wdrożenie napraw (→ osobna wycena dewelopera po audycie)
- Stałe monitorowanie (→ retainer `maintenance`)
- Testy penetracyjne / etyczne hakowanie (osobna usługa specjalistyczna)
- Audyt SEO słów kluczowych lub linków (→ retainer `seo`)

---

## Pricing anchors
| Pozycja | Cena |
|---|---|
| Audyt bezpieczeństwa i wydajności | **£299** |
| **Suma (jednorazowo)** | **£299** |

**Warunki płatności:** Pełna płatność z góry przed rozpoczęciem audytu.

Wdrożenie napraw po audycie: do ustalenia — wycena indywidualna wystawiona po raporcie.

---

## Kamienie milowe
| Faza | Termin | Kluczowe działania |
|---|---|---|
| Dzień 0 | Rejestracja | Umowa podpisana, płatność otrzymana, prośba o dane dostępowe |
| Dzień 1–2 | Dostęp | CMS, hosting i dostęp do narzędzi zweryfikowany |
| Dzień 2–5 | Audyt | Wszystkie kontrole audytu zakończone; wyniki przygotowane |
| Dzień 5–7 | Raport | Raport PDF skompilowany; podsumowanie kierownicze napisane |
| Dzień 7 | Dostarczenie | Raport wysłany e-mailem do klienta |
| W ciągu 10 dni | Omówienie | 1-godzinne omówienie: przegląd wyników i priorytetów napraw |

---

## Upsells i cross-sells
| Możliwość | Wartość | Status |
|---|---|---|
| Website Maintenance (`maintenance`) | od £149/mc | `[zainteresowany / odrzucony / do ustalenia]` |
| Retainer SEO (`seo`) | od £499/mc | `[zainteresowany / odrzucony / do ustalenia]` |
| Wdrożenie napraw (godziny dewelopera) | wycena indywidualna | `[zainteresowany / odrzucony / do ustalenia]` |
| Pełna przebudowa strony (`brochure-websites`) | od £799 | `[zainteresowany / odrzucony / do ustalenia]` |
| Przebudowa aplikacji webowej (`web-applications`) | od £5 999 | `[zainteresowany / odrzucony / do ustalenia]` |

---

## Ryzyka i zależności
- **Opóźnienia dostępu** — jeśli dostęp do CMS lub hostingu nie zostanie zapewniony w ciągu 2 dni roboczych od rejestracji, harmonogram dostarczenia przesuwa się odpowiednio
- **Staging vs live** — audyt musi być przeprowadzony na produkcyjnej (live) stronie; wyniki na staging mogą nie odzwierciedlać rzeczywistych luk
- **Krytyczne wyniki** — jeśli zostanie odkryta krytyczna luka (np. aktywne malware, ryzyko naruszenia danych), klient zostanie powiadomiony natychmiast poza standardowym harmonogramem raportu
- **Złożona platforma własna** — niestandardowy CMS lub mocno dostosowane bazy kodu mogą wymagać dodatkowego czasu; poinformuj klienta jeśli zakres się rozszerza
- **Sektor regulowany** — strony opieki zdrowotnej, finansowe lub prawne mogą mieć specyficzne frameworki compliance (RODO, PCI DSS) wymagające specjalistycznego przeglądu poza standardowym zakresem audytu

---

## Założenia
- Klient zapewnia wszystkie wymagane dostępy w ciągu 2 dni roboczych od płatności
- Strona jest w stanie live/produkcyjnym, nie pod aktywnym developmentem
- Klient akceptuje, że naprawy nie są wliczone w £299 i będą wyceniane osobno
- W omówieniu uczestniczy decydent klienta lub kierownik techniczny
- Jeśli zostaną znalezione krytyczne problemy, klient zgadza się na natychmiastowy kontakt

---

## Otwarte pytania
- [ ] Czy wszystkie wymagane dostępy (CMS, hosting, Search Console) zostały potwierdzone?
- [ ] Czy są konkretne strony lub funkcje do priorytetyzacji podczas audytu?
- [ ] Czy jest znane wymaganie compliance (RODO, PCI, ISO) do sprawdzenia?
- [ ] Kto wdroży wyniki — wewnętrzny zespół klienta czy Website Expert?
- [ ] Czy jest budżet na maintenance lub development po audycie do omówienia?

---

## Rekomendowany następny krok
1. Wystaw umowę usługi audytu i link do płatności (£299)
2. Wyślij klientowi listę kontrolną wymagań dostępowych; ustaw termin 2 dni roboczych
3. Rozpocznij audyt w ciągu 24 godzin od otrzymania wszystkich dostępów
4. Po omówieniu: przedstaw retainer `maintenance` lub wycenę wdrożenia napraw indywidualnie
