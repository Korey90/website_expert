Oto 20 pytań — odpowiedz na każde choćby krótko, a zacznę kodować:

---

**Użytkownicy i dostęp**

1. Czy panel ma być tylko dla Ciebie (1 admin), czy będą też pracownicy / podwykonawcy z różnymi rolami (np. admin, manager, developer)?
-ODPOWIEDŻ: Tak, podtzrbujemy pełen ACL. wykorzystaj pakiet laravela do tego.
2. Jeśli role — jakie uprawnienia ma mieć każda rola? (np. manager widzi projekty, ale nie finanse)
-ODPOWIEDŻ: Jeszcze nie wiem, ale chce miec interface ktory pozwoli zarzadzac zasobami i rolami.
---

**Klienci (CRM)**

3. Jakie dane klienta chcesz przechowywać? (firma, NIP, adresy, kontakty, notatki, historia projektów?)
-ODPOWIEDŻ: Wszystkie wymagane na rynku brytyjskim i dodatkowe na potrzeby CRM.
4. Czy potrzebujesz pipeline'u sprzedażowego — np. statusy leadów: *Nowy → Kontakt → Wycena → Podpisana umowa*?
-ODPOWIEDŻ: Tak chciałbym mieć pipeline oraz mozliwość modyfikowania go.
5. Czy klient ma mieć własne konto w panelu (client portal), żeby śledzić postęp swojego projektu?
-ODPOWIEDŻ: Tak jest to 'Must Have'.

---

**Projekty**

6. Jakie fazy ma projekt? (np. Brief → Design → Development → Testy → Wdrożenie → Wsparcie)
-ODPOWIEDŻ: jesli te fazy deda pasowac do wszystkich usługo to, tak. ogulnie fazy powinny byc ustandaryzowane pod wykonywana usługe badz zestaw usług.
7. Czy chcesz zarządzanie zadaniami w projekcie (task board / kanban / lista)?
-ODPOWIEDŻ: Tak. zastosuj najpopularniesze, jakie stosuja konkurenci.
8. Czy projekty mają budżet, śledzenie godzin pracy (time tracking)?
-ODPOWIEDŻ: nie planuje czegos takiego jak sledzenie godzin pracy, moze kiedys. co do budrzetu to nie rozumiem o co ci chodzi.
9. Czy chcesz system plików / załączników per projekt (przesyłanie designów, briefów, umów)?
-ODPOWIEDŻ: Tak zdecydowanie.
---

**Finanse**

10. Czy panel ma wystawiać faktury? Jeśli tak — w jakiej walucie (GBP / PLN / wielowalutowo), z jakim numerowaniem?
-ODPOWIEDŻ: Tak. 
11. Czy potrzebujesz śledzenia płatności (np. faktura zapłacona / częściowa / zaległa)?
-ODPOWIEDŻ: Tak.
12. Kosztorysy / oferty — czy mają być generowane z panelu i wysyłane klientowi?
-ODPOWIEDŻ: Tak. 
13. Czy chcesz integrację z systemem (np. Stripe do płatności online, FakturaXL, Fakturownia)?
-ODPOWIEDŻ: Tak, chciałbym integracje Stripe.

---

**Komunikacja**

14. Czy chcesz wbudowany system wiadomości / komentarzy per projekt (między Tobą a klientem)?
-ODPOWIEDŻ: Tak. feedback jest bardzo wazny.
15. Powiadomienia email — jakie zdarzenia mają triggerować maila? (np. nowy lead, faktura, zmiana statusu)
-ODPOWIEDŻ: Chciałbym miec mozliwosc definiowania zdarzeń, przypisywania akcji typu, wyslij email/sms.

---

**Kalkulator i oferty**

16. Czy kalkulator ze strony frontowej ma być powiązany z panelem — czyli zgłoszenia z kalkulatora mają trafiać do admina jako leady?
-ODPOWIEDŻ: Tak, zdecydowanie.
17. Czy chcesz edytować ceny z kalkulatora bezpośrednio z panelu (bez zmiany kodu)?
-ODPOWIEDŻ: Tak, chce miec pełna kontrole nad kalkulatorem z poziomu panelu admina.
---

**Dashboard i raporty**

18. Co chcesz widzieć na głównym dashboardzie? (np. aktywne projekty, przychód miesięczny, zaległe faktury, nowe leady),
-ODPOWIEDŻ: dodałbym jeszcze jakies szybkie akcje.
19. Czy potrzebujesz raportów / wykresów (przychód miesięczny, liczba projektów, źródła leadów)?
-ODPOWIEDŻ: Tak, im wiecej informacji i wykresów ktore mierza konkretne rzeczy tym lepiej. generowanie raportow powinno byc do wielu formatow. wymagane to PDF,excell,csv,html.
---

**Techniczne i UI**

20. Czy panel ma używać tego samego designu co strona frontowa (brand-500, Tailwind, ciemny motyw), czy wolisz klasyczny neutralny design panelu?
-ODPOWIEDŻ: Klasyczny layout. wykozystaj/rozbuduj to co daje laravel.
21. Czy chcesz PWA / aplikację mobilną, czy wystarczy responsywny panel w przeglądarce?
-ODPOWIEDŻ: nie wiem co to jest PWA. aplikacje mobilna zrobimy w przyszłości. wystarczy panel w przegladarce.
22. Czy masz już bazę danych z danymi klientów / projektów do zaimportowania?
-ODPOWIEDŻ: mam sporo projektów. dodam je jak juz bedziemy mieli panel admina skonczony.

**Dodatkowe**

Pamietaj ze front bedzie w kilku jezykach. dostosuj tabelki i interface CMS tak zeby to obslugiwały.

Panel administracyjny musi zawierac mozliwosc dodawania stron tj: regulaminem/ polityka-prywatnosci/ oraz cookies/ itp.
