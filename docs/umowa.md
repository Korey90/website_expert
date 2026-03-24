# UMOWA O TWORZENIE OPROGRAMOWANIA / STRONY INTERNETOWEJ

## 1. Strony Umowy

Niniejsza Umowa ("Umowa") zostaje zawarta pomiędzy:

**Wykonawcą:** {{legal.company_name}}, spółką zarejestrowaną w Anglii i Walii (nr: {{legal.company_number}}, VAT: {{legal.vat_number}}), adres: {{legal.company_address}} ("Wykonawca")

a

**Zamawiającym:** [DANE KLIENTA] ("Zamawiający")

Łącznie: „Strony”.

Umowa wchodzi w życie z chwilą:
(a) akceptacji Oferty lub
(b) dokonania pierwszej płatności.

---

## 2. Definicje

* **Projekt** – zakres prac określony w Ofercie
* **Rezultaty** – kod, aplikacja, UI, systemy backendowe, dokumentacja
* **Treści** – materiały dostarczone przez Zamawiającego
* **Change Request** – zmiana zakresu
* **IP Rights** – prawa własności intelektualnej

---

## 3. Zakres Usług

Zakres określony jest w Ofercie i może obejmować:

* Laravel (backend)
* React / Inertia (frontend)
* Tailwind / custom CSS
* integracje API
* CMS / e-commerce
* deployment

Oferta ma pierwszeństwo nad ogólnym opisem.

---

## 4. Model Współpracy (Software House)

Projekt realizowany jest w modelu:

* fixed price lub milestone-based
* iteracyjnym (feedback loops)

Zamawiający akceptuje, że:

* development jest procesem iteracyjnym
* nie wszystkie elementy mogą być przewidziane na start

---

## 5. Harmonogram

Terminy zależą od:

* terminowego feedbacku (do 5 dni roboczych)
* dostarczenia materiałów
* braku zmian scope

Brak feedbacku = automatyczna akceptacja etapu.

---

## 6. Wynagrodzenie i Płatności

* opłata początkowa (non-refundable onboarding fee): {{legal.deposit_percent}}%
* płatności wg milestone
* termin płatności: {{legal.payment_terms_days}} dni

Brak płatności:

* możliwość wstrzymania prac
* naliczanie odsetek ustawowych

---

## 7. Własność Intelektualna (UK compliant)

Po pełnej zapłacie:

Wykonawca przenosi na Zamawiającego wszelkie prawa do Rezultatów:

* worldwide
* perpetual
* exclusive

w zakresie dopuszczonym przez prawo.

---

### 7.1 Wyłączenia

Nie podlegają przeniesieniu:

* frameworki (Laravel, React itd.)
* open-source
* reusable components Wykonawcy

---

### 7.2 Licencja na komponenty Wykonawcy

Zamawiający otrzymuje:

* niewyłączną
* bezterminową
* globalną licencję

na używanie tych elementów w Projekcie.

---

### 7.3 Portfolio

Wykonawca może prezentować projekt w portfolio.

---

## 8. Change Requests

Zmiany poza zakresem:

* wymagają akceptacji (email wystarczy)
* są dodatkowo płatne
* wpływają na timeline

---

## 9. Usługi Zewnętrzne

Wykonawca nie odpowiada za:

* hosting
* API
* integracje zewnętrzne

Koszty tych usług ponosi Zamawiający.

---

## 10. SLA / Utrzymanie (jeśli dotyczy)

Jeśli nie zawarto osobnej umowy:

* brak obowiązku maintenance
* brak SLA

Opcjonalnie:

* maintenance może być wykupiony oddzielnie

---

## 11. Odbiór Projektu

Projekt uznaje się za zaakceptowany gdy:

* Zamawiający zatwierdzi lub
* nie zgłosi uwag w ciągu 5 dni

---

## 12. Gwarancja

30 dni na poprawki błędów:

* wynikających z implementacji

Nie obejmuje:

* zmian po stronie klienta
* aktualizacji zewnętrznych
* zmian serwera

---

## 13. Odpowiedzialność

W maksymalnym zakresie dopuszczonym przez prawo:

* odpowiedzialność ograniczona do wartości umowy
* brak odpowiedzialności za:

  * utracone zyski
  * dane
  * straty pośrednie

Nie wyłącza:

* odpowiedzialności za śmierć / obrażenia
* fraud
* obowiązków ustawowych

---

## 14. Dane Osobowe

Strony przestrzegają:

* UK GDPR
* EU GDPR (jeśli dotyczy)

W razie potrzeby zawierana jest DPA.

---

## 15. Poufność

Obowiązuje przez 3 lata od zakończenia.

---

## 16. Prawo Konsumenta (UK + EU)

Jeśli Zamawiający jest konsumentem:

* ma prawo odstąpienia w ciągu 14 dni
* jeśli zażąda rozpoczęcia prac:

  * traci pełne prawo zwrotu proporcjonalnie

Nic w tej Umowie nie ogranicza praw konsumenta wynikających z prawa kraju jego zamieszkania.

---

## 17. Rozwiązanie Umowy

Zamawiający:

* 14 dni wypowiedzenia
* płatność za wykonane prace

Wykonawca:

* natychmiast w przypadku naruszenia lub braku płatności

---

## 18. Siła Wyższa

Standardowa klauzula.

---

## 19. Prawo i Jurysdykcja

Umowa podlega prawu Anglii i Walii.

Dla konsumentów:

* zachowane są prawa do sądów lokalnych

---

## 20. Całość Umowy

Umowa + Oferta = całość porozumienia.

---

## 21. Podpisy

Akceptacja elektroniczna jest wiążąca.

---

## 22. Repozytoria, Kod Źródłowy i Workflow

### 22.1 Repozytorium

Projekt będzie utrzymywany w systemie kontroli wersji (np. Git).

Domyślnie:

* repozytorium należy do Wykonawcy do momentu pełnej płatności
* po płatności może zostać:

  * przekazane Zamawiającemu lub
  * współdzielone (access)

### 22.2 Workflow

Development odbywa się zgodnie z dobrymi praktykami:

* branch-based workflow
* code review (jeśli stosowane)
* deployment pipeline (jeśli uzgodniony)

### 22.3 Backupy

Wykonawca nie gwarantuje długoterminowego przechowywania repozytorium po zakończeniu projektu, chyba że zawarto umowę maintenance.

---

## 23. Hosting, DevOps i Deployment

### 23.1 Hosting

Jeśli nie ustalono inaczej:

* Zamawiający odpowiada za hosting
* Wykonawca może rekomendować dostawców

### 23.2 Deployment

Wykonawca może:

* skonfigurować środowisko
* wdrożyć aplikację

ale nie odpowiada za:

* ciągłość działania infrastruktury
* awarie serwera

### 23.3 DevOps (opcjonalne)

Jeśli wykupione:

* CI/CD
* monitoring
* automatyczne deploye

---

## 24. SLA (Service Level Agreement – opcjonalne)

Jeśli Strony zawarły umowę SLA:

### 24.1 Czas reakcji

* krytyczny błąd: do 24h
* wysoki: 48h
* niski: 72h

### 24.2 Definicje

* krytyczny: system nie działa
* wysoki: funkcjonalność ograniczona
* niski: bug kosmetyczny

### 24.3 Wyłączenia SLA

Nie obejmuje:

* problemów z hostingiem
* integracji zewnętrznych
* działań Zamawiającego

---

## 25. Bezpieczeństwo (Security Clause)

### 25.1 Standardy

Wykonawca stosuje dobre praktyki bezpieczeństwa, w tym:

* walidację danych
* ochronę przed podstawowymi atakami (XSS, CSRF, SQL injection)

### 25.2 Odpowiedzialność

Po wdrożeniu:

* Zamawiający odpowiada za:

  * aktualizacje systemu
  * zarządzanie dostępem
  * bezpieczeństwo infrastruktury

### 25.3 Brak gwarancji absolutnego bezpieczeństwa

Żaden system nie jest w 100% bezpieczny — Wykonawca nie gwarantuje odporności na wszystkie ataki.

---

## 26. Maintenance i Wsparcie

Jeśli nie zawarto osobnej umowy:

* brak obowiązku wsparcia
* brak aktualizacji

Maintenance może obejmować:

* aktualizacje Laravel / React
* poprawki bezpieczeństwa
* monitoring

---

## 27. White-label i Odsprzedaż

### 27.1 Zakaz odsprzedaży jako produkt

Zamawiający nie może:

* sprzedawać Rezultatów jako template
* dystrybuować jako SaaS bez zgody

### 27.2 White-label (opcjonalne)

Możliwe po uzgodnieniu:

* agencje mogą sprzedawać dalej
* bez ujawniania Wykonawcy

---

## 28. Licencje i Open Source

Projekt może zawierać:

* Laravel (MIT)
* React (MIT)
* inne biblioteki

Zamawiający zobowiązuje się:

* przestrzegać licencji open-source

---

## 29. SEO i Wydajność

Wykonawca:

* stosuje dobre praktyki

ale nie gwarantuje:

* pozycji w Google
* ruchu
* konwersji

---

## 30. Testy i Jakość

Projekt może obejmować:

* testy manualne
* testy przeglądarek

Automatyczne testy tylko jeśli uzgodnione.

---

## 31. Odpowiedzialność za Dane

Zamawiający odpowiada za:

* backup danych
* treści użytkowników
* zgodność z prawem

---

## 32. Migracje i Integracje

Wykonawca nie odpowiada za:

* błędy danych źródłowych
* ograniczenia API

---

## 33. Komunikacja Projektowa

Oficjalne kanały:

* email
* system ticketowy (jeśli używany)

Brak odpowiedzi = opóźnienia.

---

## 34. Priorytetyzacja i Kolejka

Wykonawca:

* obsługuje wielu klientów
* nie gwarantuje wyłączności zasobów

---

## 35. Audit i Compliance

Na życzenie:

* możliwy audit (płatny)

---

## 36. Klauzula Rozsądnego Użytkowania

Zamawiający nie może:

* przeciążać systemu
* używać w sposób niezgodny z przeznaczeniem

---

## 37. Przeniesienie Umowy

Zamawiający nie może przenieść umowy bez zgody.

Wykonawca może:

* korzystać z podwykonawców

---

## 38. Podwykonawcy

Wykonawca może:

* delegować prace

ale odpowiada za ich wykonanie.

---

## 39. Non-solicitation

Zamawiający nie może zatrudniać pracowników Wykonawcy przez:

* 12 miesięcy

bez zgody.

---

## 40. Kary umowne (opcjonalne)

Mogą być ustalone indywidualnie.

---

## 41. Postanowienia Końcowe

W przypadku konfliktu:

* pierwszeństwo ma Oferta

---

### 42. Model Umowy: MSA + SOW

#### 42.1 Master Services Agreement (MSA)

Niniejsza Umowa stanowi ramową umowę współpracy (MSA).

#### 42.2 Statement of Work (SOW)

Każdy projekt / etap może być realizowany na podstawie osobnego dokumentu SOW, który określa:

* zakres prac
* harmonogram
* wynagrodzenie
* deliverables

W przypadku sprzeczności: SOW ma pierwszeństwo w zakresie danego projektu.

---

### 43. Model Rozliczeń Time & Materials (T&M)

#### 43.1 Zastosowanie

Jeśli Strony uzgodnią model T&M:

* rozliczenie odbywa się na podstawie rzeczywistego czasu pracy
* stosowana jest stawka godzinowa / dzienna

#### 43.2 Ewidencja czasu

Wykonawca może prowadzić:

* timesheety
* raporty pracy

#### 43.3 Estymacje

Estymacje nie stanowią gwarancji ceny końcowej.

---

### 44. Equity / Współpraca Startupowa (opcjonalne)

Strony mogą uzgodnić częściowe wynagrodzenie w formie udziałów.

W takim przypadku:

* warunki określa osobna umowa
* niniejsza Umowa nadal obowiązuje operacyjnie

Wykonawca nie ponosi ryzyka biznesowego projektu Zamawiającego.

---

### 45. Zaawansowane Ograniczenie Odpowiedzialności (Pro-Level)

#### 45.1 Limit odpowiedzialności

O ile prawo nie stanowi inaczej:

* odpowiedzialność ograniczona do:

  * 100% wartości umowy LUB
  * wartości z ostatnich 12 miesięcy współpracy

#### 45.2 Wyłączenia

Wykonawca nie odpowiada za:

* utratę danych
* utracone korzyści
* utratę klientów
* przerwy w działalności

#### 45.3 Proporcjonalność

Odpowiedzialność jest proporcjonalna do wkładu Wykonawcy w powstanie szkody.

---

### 46. Ubezpieczenie (opcjonalne)

Wykonawca może posiadać ubezpieczenie odpowiedzialności zawodowej (PI insurance).

Na żądanie możliwe jest przedstawienie potwierdzenia.

---

### 47. Disaster Recovery i Backup

Jeśli nie uzgodniono inaczej:

* Zamawiający odpowiada za backup

Opcjonalnie:

* automatyczne backupy
* disaster recovery plan

---

### 48. Vendor Lock-in i Przenoszalność

Po zakończeniu współpracy:

* Zamawiający może korzystać z projektu niezależnie
* Wykonawca nie blokuje migracji

Nie obejmuje to narzędzi wewnętrznych Wykonawcy.

---

### 49. Dokumentacja

Dokumentacja może obejmować:

* instrukcje użytkownika
* dokumentację techniczną

Zakres dokumentacji określa Oferta lub SOW.

---

### 50. Wersjonowanie i Aktualizacje

Wykonawca nie ma obowiązku aktualizacji technologii po zakończeniu projektu, chyba że:

* zawarto umowę maintenance

---

### 51. Klauzula Całościowa

Niniejsza Umowa wraz z:

* Ofertą
* SOW

stanowi pełne porozumienie Stron.

---

# 🧠 Real talk (ważne)

To co masz teraz to:

👉 poziom **pełnoprawnej umowy software house (UK/EU compliant)**
👉 możesz spokojnie używać dla klientów:

* UK
* Northern Ireland
* Polska
* UE

---

