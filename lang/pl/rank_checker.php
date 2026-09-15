<?php

return [

    'meta' => [
        'title' => 'Darmowy Google Rank Checker — Website Expert',
        'description' => 'Sprawdź, na której pozycji w Google znajduje się Twoja strona dla dowolnej frazy i lokalizacji. Za darmo, natychmiast, bez rejestracji.',
    ],

    'h1' => 'Darmowy Google Rank Checker',
    'intro' => 'Sprawdź, na której pozycji w Google znajduje się Twoja strona dla dowolnej frazy i lokalizacji.',

    'form' => [
        'keyword_label' => 'Fraza kluczowa',
        'keyword_placeholder' => 'np. strony internetowe belfast',
        'website_label' => 'Strona internetowa',
        'website_placeholder' => 'np. twojafirma.co.uk',
        'location_label' => 'Lokalizacja',
        'location_custom_option' => 'Inna lokalizacja…',
        'location_custom_placeholder' => 'np. Manchester, United Kingdom',
        'device_label' => 'Urządzenie',
        'device_desktop' => 'Komputer',
        'device_mobile' => 'Telefon',
        'submit_label' => 'Sprawdź pozycję',
        'loading_label' => 'Sprawdzanie wyników Google…',
    ],

    'result' => [
        'position_label' => 'Pozycja',
        'ranking_page_label' => 'Znaleziona podstrona',
        'checked_for' => 'Wyniki dla „:keyword” — :location, :device',
        'first_page_message' => 'Twoja strona pojawia się na pierwszej stronie wyników Google dla tej frazy.',
        'interpretation' => [
            'top3' => 'Doskonała widoczność',
            'top10' => 'Pierwsza strona Google',
            'top20' => 'Blisko pierwszej strony',
            'top50' => 'Strona jest widoczna w wynikach, ale zasięg jest ograniczony',
            'top100' => 'Strona rankuje poza głównymi, widocznymi wynikami',
        ],
        'not_found_title' => 'Nie znaleziono w TOP 100 wyników',
        'not_found_message' => 'Nie udało się znaleźć domeny :domain w TOP 100 organicznych wyników Google dla frazy „:keyword” z wybranej lokalizacji.',
        'error_title' => 'Nie udało się dokończyć sprawdzenia pozycji',
        'error_message' => 'Nie udało się teraz sprawdzić pozycji. Spróbuj ponownie za chwilę.',
        'disclaimer' => 'Wyniki Google różnią się w zależności od lokalizacji, urządzenia, języka i historii wyszukiwania. Ten wynik odzwierciedla wybraną lokalizację i urządzenie, a nie gwarantowaną pozycję widoczną dla każdego użytkownika.',
    ],

    'cta' => [
        'good_title' => 'Twoja strona już dobrze rankuje. Sprawdź, co może pomóc jej wskoczyć jeszcze wyżej.',
        'good_button' => 'Zamów przegląd SEO',
        'bad_title' => 'Chcesz poprawić widoczność w Google?',
        'bad_body' => 'Zamów bezpłatny audyt SEO i dowiedz się, co ogranicza Twoją stronę.',
        'bad_button' => 'Zamów bezpłatny audyt SEO',
        'contact_button' => 'Skontaktuj się z nami',
    ],

    'faq' => [
        ['q' => 'Czy ten Google Rank Checker jest darmowy?', 'a' => 'Tak. Możesz bezpłatnie i bez rejestracji sprawdzić pozycję swojej strony dla dowolnej frazy, domeny, lokalizacji i urządzenia.'],
        ['q' => 'Dlaczego moja pozycja różni się od tej widocznej w Google?', 'a' => 'Wyniki Google zależą od lokalizacji, urządzenia, języka, historii wyszukiwania i centrum danych, z którym się łączysz. Narzędzie pokazuje reprezentatywny wynik dla wybranej lokalizacji i urządzenia, a nie gwarantowaną pozycję dla każdego użytkownika.'],
        ['q' => 'Ile wyników przeszukuje narzędzie?', 'a' => 'Narzędzie sprawdza TOP 100 organicznych wyników Google dla podanej frazy i lokalizacji.'],
        ['q' => 'Co jeśli moja strona nie zostanie znaleziona?', 'a' => 'Jeśli strona nie występuje w TOP 100 wyników organicznych, poinformujemy o tym — to prawidłowy wynik, a nie błąd.'],
        ['q' => 'Czy narzędzie sprawdza subdomeny?', 'a' => 'Jeśli podasz domenę główną (np. example.com), uwzględniane są też jej subdomeny (np. shop.example.com). Jeśli podasz konkretną subdomenę, sprawdzana jest tylko ona.'],
        ['q' => 'Jak często mogę sprawdzać frazę?', 'a' => 'Aby narzędzie pozostało darmowe dla wszystkich, liczba sprawdzeń jest ograniczona, a identyczne sprawdzenia są cache’owane przez kilka godzin.'],
    ],

    'errors' => [
        'keyword_required' => 'Podaj frazę kluczową do sprawdzenia.',
        'website_required' => 'Podaj adres swojej strony internetowej.',
        'location_required' => 'Wybierz lokalizację wyszukiwania.',
        'invalid_domain' => 'Podaj poprawny adres strony (np. example.co.uk).',
        'recaptcha_failed' => 'Nie udało się zweryfikować żądania. Spróbuj ponownie.',
        'rate_limited' => 'Osiągnięto limit sprawdzeń. Spróbuj ponownie za chwilę.',
    ],

    'contact' => [
        'ctaFormTitle' => 'Powiedz nam o swoich potrzebach',
        'labelName' => 'Imię i nazwisko',
        'labelContact' => 'Email lub numer telefonu',
        'labelMessage' => 'Wiadomość',
        'labelWebsite' => 'Strona internetowa',
        'placeholderName' => 'Jan Kowalski',
        'placeholderContact' => 'email lub telefon',
        'placeholderWebsite' => 'twojafirma.co.uk',
        'gdprText' => 'Wyrażam zgodę na przetwarzanie moich danych osobowych zgodnie z',
        'gdprLink' => 'polityką prywatności',
        'submitBtn' => 'Wyślij zapytanie',
        'sending' => 'Wysyłanie…',
        'successTitle' => 'Odezwiemy się wkrótce!',
        'successMsg' => 'Dziękujemy za kontakt. Odpiszemy w ciągu 24 godzin roboczych.',
        'errorMsg' => 'Wystąpił błąd. Sprawdź dane i spróbuj ponownie.',
        'validationMsg' => 'Uzupełnij imię, dane kontaktowe i wiadomość.',
        'clearMessage' => 'Wyczyść wiadomość',
    ],

];
