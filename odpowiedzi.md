**Tożsamość i branding**

1. **Nazwa firmy** — "Website Expert" to roboczy tytuł. Masz już jakiś pomysł na docelową nazwę, tagline lub slogan, który powinien pojawić się w hero?
-ODPOWIEDŻ: Nie, nie mam zadnego pomyslu na chwile obecna.

2. **Logo** — czy masz już logo (plik .svg / .png), czy będziemy tworzyć placeholder typograficzny? Jeśli masz — jaka forma: symbol + tekst, sam tekst, monogram?
-ODPOWIEDŻ: Nie nie mam. narazie zrob cos w stylu symbol + text.

3. **Paleta kolorów** — czy masz preferowane kolory (np. hex), konkretny vibe (tech-dark, jasny SaaS, korporacyjny niebieski, agresywny start-upowy)? Dark mode / light mode / oba?
-ODPOWIEDŻ: Agresywny startup'owy assa z light i dark mode.

4. **Typografia** — masz ulubione fonty (np. Inter, Poppins, Syne, coś z Google Fonts), czy zostać przy domyślnym systemowym?
-ODPOWIEDŻ: NIe mam zadnych ulubionych. zastosuj najlepsze dla jezyka Polskiego/portugalskiego/angielskiego.

---

**Treść i oferta**

5. **Usługi** — jakie konkretnie usługi mają znaleźć się w sekcji Oferta? (np. strony wizytówkowe, sklepy e-commerce, aplikacje webowe, audyty UX, SEO, opieka techniczna — co z tej listy?)
-ODPOWIEDŻ: napewno wizytówki, e-comerce, SEO, Hosting WWW(jako opcjonalna cześć, web dewelopingu), Tworzenie tresci, audyty bezpieczenstwa/wydajnosci, kampanie reklamowe google ads, pixel ads.

6. **Portfolio** — ile projektów chcesz pokazać na starcie? Masz już screenshoty/opisy, czy robimy "dummy" karty? Czy filtry kategorii są potrzebne od razu?
-ODPOWIEDŻ: Bedzieby wyswietlac 3 (najlepsze) projekty.


7. **Zaufali nam / Testimonials** — preferujesz logotypy klientów, cytaty + zdjęcia klientów, czy combo? Ile pozycji?
-ODPOWIEDŻ: to robimy w Combo. 5-6 pozycji z mechanika karuzeli.

---

**Kalkulator kosztów**

8. **Zakres kalkulatora** — jakie parametry powinien zbierać? Np.: typ projektu (strona, sklep, aplikacja), liczba podstron, integracje (płatności, API), design (gotowy szablon vs custom), CMS (tak/nie), termin realizacji — co jest istotne?
-ODPOWIEDŻ: Wszystkie powyzsze. jeszcze bym cos do tego dodał. chodzi mi o to zeby klient dostarczyl jak najwiecej informacji. a kalkulator ma to umozliwic w bardzo łatwy sposób.

9. **Wynik kalkulatora** — ma pokazywać widełki cenowe (np. "8 000 – 15 000 zł"), przedziały (tani/średni/premium), czy zawsze kończyć CTA "wyślij zapytanie + twój szacunek"?
-ODPOWIEDŻ: na poczatej niech kalkulator pokazuje obliczenia, ale wydaje mi sie ze finalnie to bedzie CTA i dane z kalkulatora do bazy danych.
---

**Kontakt i formularz**

10. **Formularz kontaktowy** — pola: imię, email, wiadomość — wystarczy, czy dodajemy: telefon, budżet, rodzaj projektu, NIP/firma, preferowany termin kontaktu?
-ODPOWIEDŻ: dodajemy wszystko po za budzetem.

11. **Mapa** — osadzony Google Maps (konkretna lokalizacja), czy raczej ukryjemy adres (freelancer/praca zdalna) i mapa odpada?
-ODPOWIEDŻ: Mapka bedzie jak bede miał juz biuro. puki co nie trzeba.

---

**Techniczne / architektura**

12. **Blade vs SPA** — czy `layout.html` ma być czystym prototypem HTML (bez Blade na razie), który potem przekonwertujemy, czy od razu piszemy w składni Blade z `@include`, `@section` itd.?
-ODPOWIEDŻ: szablon ma byc typowym Html. nie bedziemy uzywac blade, zamiast tego bedzie react+inertia

13. **React — bundle czy CDN?** — komponenty interaktywne (kalkulator, filtry portfolio) przez `<script type="module">` z Vite/bundler, czy na potrzeby prototypu wrzucimy React z CDN + Babel in-browser?
-ODPOWIEDŻ: Na potrzeby prototypu wrzucimy React z CDN + Babel in-browser

14. **Animacje** — subtelne przejścia (fade-in przy scrollu, hover efekty) są OK, czy wolisz wersję bez żadnych animacji JS (czyste CSS transitions)?
-ODPOWIEDŻ: subtelne przejścia, to nie powinien byc problem dla react i tailwind css
---

**Strategia i SEO**

15. **Język i rynek** — strona wyłącznie po polsku, wyłącznie po angielsku, czy dwujęzyczna (language switcher)? Czy jest już domena / założony projekt w Google Search Console, którą trzeba mieć na uwadze przy meta tagach?
-ODPOWIEDŻ: strona bedzie w 3-5 jezykach, na pewno Polski, Angielski, Portugalski. szablon napisz w jezyku polskim.  aktualnie nie ma domeny.
---