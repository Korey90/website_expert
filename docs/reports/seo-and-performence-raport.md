# Audyt SEO endpointu

**Adres:** [website-expert.uk/pl/strony-internetowe-uk](https://website-expert.uk/pl/strony-internetowe-uk)
**Data audytu:** 17 września 2026
**Ocena ogólna: 48/100**

Strona ma dobrą ofertę, właściwą intencję sprzedażową i sensowną strukturę po wyrenderowaniu. Jej największym problemem jest jednak sposób renderowania: serwer wysyła niemal pusty dokument, a treść, nagłówki, linki i docelowe metadane pojawiają się dopiero po wykonaniu JavaScript. To ogranicza indeksowalność i utrudnia Google ocenę strony.

## Najważniejsze wyniki

| Obszar                | Ocena | Wniosek                                                                             |
| --------------------- | ----: | ----------------------------------------------------------------------------------- |
| Indeksowalność        |  4/10 | Brak blokady, ale właściwa treść zależy od JS                                       |
| Techniczne SEO        |  5/10 | Canonical i hreflang są, lecz występują problemy z SSR, cache i odpowiedzią serwera |
| On-page               |  7/10 | Dobry H1, oferta i struktura po renderowaniu                                        |
| Treść                 |  6/10 | Dobre dopasowanie sprzedażowe, za mało pokrytych pytań i fraz                       |
| Linkowanie wewnętrzne |  4/10 | Część menu prowadzi do sekcji strony głównej zamiast bieżącego landingu             |
| Dane strukturalne     |  1/10 | Brak JSON-LD                                                                        |
| Media/social SEO      |  3/10 | Niepełne Open Graph, brak Twitter Cards                                             |
| UX i konwersja        |  8/10 | Jasna cena, CTA, proces i formularz                                                 |
| Wydajność             |  3/10 | Wolna odpowiedź i ciężkie zasoby                                                    |

---

## 1. Krytyczny problem: brak SSR

Surowa odpowiedź HTML zawiera:

* zero `h1`, `h2` i `h3`,
* zero akapitów,
* zero linków,
* zero obrazów,
* zero danych strukturalnych,
* ogólny tytuł `WebsiteExpert`,
* brak meta description.

Dopiero po uruchomieniu Reacta pojawiają się właściwa treść i metadane.

Google potrafi renderować JavaScript, ale jest to dodatkowy etap indeksowania. Strona może zostać najpierw oceniona na podstawie pustego HTML, jej indeksacja może być opóźniona, a w razie błędu JS Google nie zobaczy właściwej zawartości. Fakt, że dokładny adres nie pojawił się w wynikach wyszukiwania podczas audytu, jest z tym zgodny, choć bez Google Search Console nie można potwierdzić przyczyny.

### Zalecenie P0

Wdrożyć SSR albo statyczne prerenderowanie tego landingu. Pierwsza odpowiedź HTML powinna zawierać co najmniej:

```html
<title>Strony internetowe UK dla polskich firm | Website Expert</title>
<meta
  name="description"
  content="Profesjonalne strony internetowe dla polskich firm w UK. Obsługa po polsku, projekt pod rynek brytyjski, SEO i responsywny design. Ceny od £799."
>

<h1>Strony internetowe dla polskich firm w Wielkiej Brytanii</h1>
```

W Laravel + Inertia + React najlepszym rozwiązaniem będzie Inertia SSR. Jeżeli pełne SSR jest teraz zbyt kosztowne, można prerenderować najważniejsze strony marketingowe podczas deploymentu.

---

## 2. Metadane

### Title

Po wykonaniu JavaScript:

> Strony internetowe dla polskich firm w UK — Website Expert - WebsiteExpert

Problemy:

* podwójny branding: `Website Expert - WebsiteExpert`,
* title jest niepotrzebnie długi,
* główna fraza jest dobra, ale końcówka wygląda automatycznie,
* początkowy title w odpowiedzi serwera to wyłącznie `WebsiteExpert`.

### Rekomendowany title

> Strony internetowe UK dla polskich firm | Website Expert

Alternatywa nastawiona na usługę:

> Projektowanie stron internetowych UK | Obsługa po polsku

Nie należy zmieniać title często. Najpierw warto wybrać główną intencję: „strony internetowe UK” albo „projektowanie stron internetowych UK”.

### Meta description

Obecna wersja po renderowaniu:

> Profesjonalna strona internetowa dla polskiej firmy działającej w Wielkiej Brytanii. Obsługa po polsku, projekt pod rynek brytyjski. Od £799. Bez ukrytych kosztów.

Jest konkretna i zawiera cenę, ale jest stosunkowo długa i używa liczby pojedynczej zamiast częściej poszukiwanej frazy „strony internetowe”.

### Rekomendowana wersja

> Profesjonalne strony internetowe dla polskich firm w UK. Obsługa po polsku, projekt pod rynek brytyjski, SEO i responsywny design. Od £799.

---

## 3. Canonical, robots i sitemap

Elementy działające poprawnie:

* endpoint zwraca `200 OK`,
* canonical wskazuje na właściwy adres,
* wariant z końcowym `/` przekierowuje `301` do wersji bez ukośnika,
* HTTP przekierowuje `301` do HTTPS,
* `robots.txt` nie blokuje crawlowania,
* endpoint znajduje się w `sitemap.xml`,
* dokument ma `lang="pl"`.

Problemy:

* strona jest wysyłana z `Cache-Control: no-cache, private`,
* odpowiedź serwera jest wolna,
* wersja z parametrem UTM zwraca `200`, choć canonical prawidłowo powinien konsolidować sygnały,
* sitemap nie zawiera `lastmod` dla tego endpointu,
* serwer ujawnia wersję Apache: `Apache/2.4.58 (Ubuntu)`.

### Zalecenia

* Publiczne strony marketingowe powinny mieć cache, np.:

```http
Cache-Control: public, max-age=300, s-maxage=3600, stale-while-revalidate=86400
```

* Dodać realny `lastmod` do sitemap.
* Nie generować różnych treści strony na podstawie cookies walutowych dla crawlera.
* Ukryć szczegółową wersję serwera.
* Sprawdzić wszystkie warianty hosta i wymusić jeden: `https://website-expert.uk`.

---

## 4. Hreflang i wersje językowe

W kodzie źródłowym znajdują się:

* `pl` → bieżący endpoint,
* `en` → `/en/web-design-northern-ireland`,
* `x-default` → wersja angielska.

Po hydratacji każda deklaracja pojawia się jednak drugi raz. Oznacza to, że React dodaje hreflang do wpisów już wygenerowanych przez backend.

### Problem semantyczny

Polska strona kieruje do firm w całym UK, natomiast angielski odpowiednik jest pozycjonowany jako „web design Northern Ireland”. Nie są to w pełni równoważne wersje językowe.

Hreflang powinien łączyć strony o zasadniczo tej samej treści i intencji, przetłumaczone na różne języki.

### Zalecenia

* Usunąć duplikowanie hreflang podczas hydratacji.
* Stworzyć faktyczny angielski odpowiednik, np. `/en/web-design-for-polish-businesses-uk`, albo usunąć powiązanie, jeśli strony mają różne cele.
* Upewnić się, że angielski endpoint posiada zwrotny hreflang do wersji polskiej.
* Rozważyć `en-GB` zamiast ogólnego `en`.

---

## 5. Nagłówki i struktura treści

Po renderowaniu strona ma jeden, bardzo dobry H1:

> Strony internetowe dla polskich firm w Wielkiej Brytanii

Hierarchia H1–H3 jest zasadniczo logiczna. Ważne sekcje to:

* polska obsługa i rynek brytyjski,
* grupy klientów,
* cena i zakres,
* realizacje,
* wybór języka,
* doświadczenie,
* proces,
* FAQ,
* formularz wyceny.

Problemy:

* nagłówki stopki `USŁUGI` i `FIRMA` są oznaczone jako H3, chociaż nie należą do głównej hierarchii dokumentu,
* część istotnych tematów SEO występuje jedynie jako krótki punkt,
* FAQ ma nagłówek, ale odpowiedzi są ukryte w interakcji i nie występują w surowym HTML.

### Zalecana rozbudowa H2

Warto w naturalny sposób dodać sekcje:

* „Projektowanie stron internetowych w UK — co obejmuje usługa?”
* „Strona internetowa dla firmy lokalnej w Wielkiej Brytanii”
* „Strona po polsku, angielsku czy dwujęzyczna?”
* „Ile kosztuje strona internetowa w UK?”
* „SEO i Google Business Profile dla firm w UK”
* „Obsługiwane lokalizacje w Wielkiej Brytanii”

Nie zalecam tworzenia kilkudziesięciu niemal identycznych landingów miejskich. Osobne strony dla Belfastu, Londynu czy Manchesteru mają sens dopiero wtedy, gdy zawierają lokalne realizacje, opinie, proces i faktycznie unikalną ofertę.

---

## 6. Jakość i kompletność treści

Strona ma około 890 widocznych słów. Jak na landing sprzedażowy jest to wystarczające, ale konkurencyjne strony pokrywają temat znacznie szerzej: ceny, technologie, lokalizacje, SEO, utrzymanie, porównania i szczegółowe poradniki. Przykłady konkurencji pokazują rozbudowane klastry tematyczne oraz landingi lokalne: [Worcester Web Maker](https://worcesterwebmaker.co.uk/), [22 Studio](https://www.22studio.co.uk/) i [DiSpace](https://dispace.uk/strony-internetowe-uk).

Brakuje przede wszystkim:

* wskazania technologii lub CMS,
* informacji o hostingu i utrzymaniu,
* szczegółów dotyczących SEO,
* rzeczywistych przykładów i linków do realizacji,
* nazw klientów lub weryfikowalnych opinii,
* informacji o autorze i doświadczeniu,
* wyjaśnienia, co wpływa na cenę,
* odpowiedzi na obawy dotyczące własności strony, domeny i danych,
* lokalnych sygnałów biznesowych.

### Strategia słów kluczowych

**Fraza główna:**

* strony internetowe UK

**Frazy uzupełniające:**

* projektowanie stron internetowych UK,
* tworzenie stron internetowych UK,
* strony internetowe dla polskich firm w UK,
* polski web developer UK,
* strony internetowe Wielka Brytania,
* strona internetowa dla firmy w Anglii,
* ile kosztuje strona internetowa w UK,
* dwujęzyczna strona internetowa UK.

Nie należy mechanicznie powtarzać fraz. Najważniejsze jest pokrycie intencji: użytkownik chce znać cenę, zakres, termin, przykłady, warunki współpracy i wiarygodność wykonawcy.

---

## 7. E-E-A-T i wiarygodność

Strona mówi o „10+ latach doświadczenia”, ale dowody są słabe:

* brak pełnego imienia i nazwiska twórcy w głównej treści,
* brak rozbudowanej biografii,
* brak profilu autora,
* brak możliwych do zweryfikowania danych o firmie,
* realizacje są opisane ogólnie,
* rezultaty typu „wyraźny wzrost” nie zawierają danych ani źródeł,
* brak widocznych opinii klientów.

### Zalecenia

Dodać:

* imię, nazwisko, zdjęcie i krótką biografię,
* lokalizację działalności i dane firmy,
* link do właściwie uzupełnionego LinkedIn,
* prawdziwe case studies z URL-em projektu,
* referencje z imieniem, firmą i lokalizacją,
* mierzalne rezultaty, jeśli można je udokumentować,
* stronę „O nas” z doświadczeniem i sposobem pracy.

Zdjęcie założyciela ma poprawny `alt="Założyciel"`, ale plik waży około **1,79 MB**. Należy go skompresować i nadać opisowy alt, np. „[Imię i nazwisko], web developer Website Expert w Belfaście”.

---

## 8. Linkowanie wewnętrzne

Wiele elementów menu na audytowanym landingu prowadzi do:

* `https://website-expert.uk/#about`
* `https://website-expert.uk/#services`
* `https://website-expert.uk/#process`
* `https://website-expert.uk/#faq`
* `https://website-expert.uk/#contact`

To oznacza przejście na stronę główną, zamiast przewinięcia do odpowiedniej sekcji bieżącej strony. Tymczasem sam landing posiada odpowiadające tym pozycjom sekcje.

Dodatkowo:

* „Zadzwoń” ma pusty adres `tel:`,
* CTA „Ile to będzie kosztować?” prowadzi do `/#calculate`, podczas gdy istnieje właściwa strona `/calculate`,
* przykładowe realizacje nie mają widocznych linków do case studies,
* anchor text „O nas”, „Oferta” i „Kontakt” nie wspiera tematyki landingu.

### Zalecenia

* Dla sekcji bieżącej strony użyć lokalnych `#about`, `#services`, `#process`, `#faq`, `#contact`.
* Jeśli CTA ma otwierać kalkulator, kierować do `/calculate`.
* Naprawić lub usunąć pusty `tel:`.
* Podlinkować realizacje do stron portfolio.
* Dodać kontekstowe linki do SEO, e-commerce, hostingu i kalkulatora.
* Dodać breadcrumbs, np. `Strona główna → Strony internetowe UK`.

---

## 9. Dane strukturalne

Strona nie ma żadnego JSON-LD. Rekomendowane typy:

* `ProfessionalService` albo `LocalBusiness`,
* `Service`,
* `FAQPage`,
* `BreadcrumbList`,
* `Person` dla właściciela,
* `WebSite` na poziomie całej domeny.

Nie dodawałbym `Review` ani `AggregateRating`, dopóki opinie nie są autentyczne, widoczne i zgodne z zasadami Google.

Minimalna struktura dla usługi:

```json
{
  "@context": "https://schema.org",
  "@type": "Service",
  "name": "Projektowanie stron internetowych dla polskich firm w UK",
  "url": "https://website-expert.uk/pl/strony-internetowe-uk",
  "areaServed": {
    "@type": "Country",
    "name": "United Kingdom"
  },
  "provider": {
    "@type": "ProfessionalService",
    "name": "Website Expert",
    "url": "https://website-expert.uk/"
  },
  "offers": {
    "@type": "Offer",
    "priceCurrency": "GBP",
    "price": "799",
    "description": "Strony firmowe od £799"
  }
}
```

Schema musi odpowiadać treści widocznej na stronie.

---

## 10. Open Graph i udostępnianie

Obecne są tylko:

* `og:title`,
* `og:description`.

Brakuje:

* `og:image`,
* `og:url`,
* `og:type`,
* `og:locale`,
* Twitter/X Cards.

### Zalecenie

Dodać kompletny zestaw:

```html
<meta property="og:type" content="website">
<meta property="og:url" content="https://website-expert.uk/pl/strony-internetowe-uk">
<meta property="og:locale" content="pl_PL">
<meta property="og:image" content="https://website-expert.uk/images/og/strony-internetowe-uk.jpg">
<meta name="twitter:card" content="summary_large_image">
```

Grafika OG powinna mieć około 1200 × 630 px i własny tekst dostosowany do oferty.

---

## 11. Wydajność i Core Web Vitals

Zaobserwowane podczas audytu:

* dokument HTML: około 117 KB,
* arkusz CSS: około 234 KB,
* wspólny pakiet marketingowy: około 359 KB,
* zdjęcie założyciela: około 1,79 MB,
* odpowiedzi serwera i assetów miały bardzo wysoki czas oczekiwania,
* dokument jest generowany prywatnie i bez cache,
* w HTML znajduje się bardzo duży payload Inertia z tłumaczeniami i danymi niezwiązanymi z landingiem,
* payload ujawnia dużą listę tras panelu administracyjnego.

To wpływa zarówno na SEO, jak i bezpieczeństwo architektury aplikacji.

### Najważniejsze działania

1. Ograniczyć globalne `shared props` Inertia.
2. Nie przesyłać na publiczną stronę wszystkich tłumaczeń panelu.
3. Wyłączyć publiczne generowanie całego obiektu Ziggy lub użyć ograniczonej grupy tras.
4. Przenieść ciężkie komponenty marketingowe do dynamicznych chunków.
5. Skompresować zdjęcie do WebP/AVIF, docelowo poniżej 150–250 KB.
6. Ustawić `width` i `height` obrazu, aby ograniczyć CLS.
7. Dodać `loading="lazy"` dla zdjęć poniżej pierwszego ekranu.
8. Włączyć CDN lub cache reverse proxy.
9. Zweryfikować Brotli/Gzip.
10. Uruchomić testy PageSpeed Insights osobno dla mobile i desktop po wdrożeniu SSR.

---

## 12. Konwersja i UX

To jeden z mocniejszych obszarów strony.

Plusy:

* cena „od £799” widoczna wysoko,
* konkretny termin 3–5 tygodni,
* komunikacja po polsku jako silny wyróżnik,
* kilka spójnych CTA,
* jasny zakres pakietu,
* wyjaśnienie procesu,
* formularz kwalifikujący lead,
* informacje o własności domeny i strony,
* dobra zgodność intencji SEO z ofertą.

Do poprawy:

* przykłady realizacji wyglądają jak opisy, a nie weryfikowalne case studies,
* formularz jest dość długi,
* CTA są liczne, ale prawie wszystkie prowadzą do tego samego celu,
* brakuje drugiej opcji kontaktu, np. telefonu lub umówienia krótkiej rozmowy,
* pusty link `tel:` obniża zaufanie,
* FAQ powinno zawierać kompletne, indeksowalne odpowiedzi w HTML.

---

# Plan działań

## P0 — wykonać natychmiast

1. Wdrożyć SSR/prerendering treści, nagłówków, linków i metadanych.
2. Naprawić title oraz podwójny branding.
3. Dodać server-side meta description.
4. Usunąć duplikaty hreflang.
5. Naprawić menu prowadzące do `/#sekcja`.
6. Naprawić pusty `tel:`.
7. Ograniczyć publiczny payload Inertia i Ziggy.
8. Włączyć cache dla stron marketingowych.

## P1 — kolejny etap

1. Dodać `Service`, `ProfessionalService`, `FAQPage` i breadcrumbs JSON-LD.
2. Rozbudować FAQ o odpowiedzi dostępne w HTML.
3. Dodać prawdziwe case studies i referencje.
4. Skompresować obraz 1,79 MB.
5. Uzupełnić Open Graph oraz Twitter Cards.
6. Poprawić zgodność polskiej i angielskiej wersji hreflang.
7. Dodać istotne linki kontekstowe.

## P2 — rozwój widoczności

1. Zbudować klaster artykułów wspierających landing:

   * ile kosztuje strona internetowa w UK,
   * strona polska czy angielska,
   * WordPress czy rozwiązanie dedykowane,
   * SEO lokalne dla polskiej firmy w UK,
   * jak wybrać wykonawcę strony,
   * koszty domeny, hostingu i utrzymania.

2. Pozyskiwać brytyjskie linki i cytowania firmowe.

3. Rozwinąć Google Business Profile dla realnej lokalizacji.

4. Tworzyć lokalne strony wyłącznie dla faktycznie obsługiwanych obszarów.

5. Monitorować zapytania, CTR i kanibalizację w Google Search Console.

## Oczekiwany efekt

Największy wzrost da nie dopisywanie kolejnych słów kluczowych, lecz udostępnienie obecnej strony w pełnym HTML. Połączenie SSR, lepszego cache, prawdziwych case studies, danych strukturalnych i klastra treści powinno znacząco poprawić crawlability, indeksowanie, CTR oraz wiarygodność domeny.

Audyt nie obejmuje danych prywatnych z Google Search Console, GA4 ani płatnych narzędzi backlinkowych, więc nie można na jego podstawie potwierdzić liczby zaindeksowanych URL-i, obecnych pozycji, profilu linków ani rzeczywistych Core Web Vitals użytkowników.
