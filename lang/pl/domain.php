<?php

return [

    // ── Etykiety statusów zamówień ────────────────────────────────────────────
    'status' => [
        'pending_payment' => 'Oczekuje na płatność',
        'paid'            => 'Opłacone',
        'registering'     => 'W trakcie rejestracji',
        'completed'       => 'Zrealizowane',
        'failed'          => 'Nie powiodło się',
        'cancelled'       => 'Anulowane',
    ],

    // ── Etykiety typów akcji ──────────────────────────────────────────────────
    'action' => [
        'register' => 'Rejestracja',
        'renew'    => 'Odnowienie',
        'transfer' => 'Transfer',
    ],

    // ── Przypomnienie o odnowieniu ────────────────────────────────────────────
    'reminder' => [
        'subject'   => 'Twoja domena :domain wygasa za :days dzień/dni',
        'days_30'   => 'Twoja domena :domain wygaśnie za 30 dni. Odnów ją, aby uniknąć przerwy w działaniu.',
        'days_14'   => 'Twoja domena :domain wygaśnie za 14 dni. Odnów ją teraz, aby utrzymać aktywność domeny.',
        'days_7'    => 'Twoja domena :domain wygaśnie za 7 dni. Pilne — odnów ją niezwłocznie.',
        'days_1'    => 'Twoja domena :domain wygasa jutro! Odnów ją teraz, aby nie przestała działać.',
        'expired'   => 'Twoja domena :domain wygasła. Skontaktuj się z nami w sprawie przywrócenia.',
    ],

    // ── Etykiety interfejsu ────────────────────────────────────────────────────
    'label' => [
        'domain_name'    => 'Nazwa domeny',
        'tld'            => 'Rozszerzenie (TLD)',
        'expires_at'     => 'Wygasa',
        'registered_at'  => 'Zarejestrowana',
        'years'          => ':count rok/lat',
        'auto_renew'     => 'Automatyczne odnowienie',
        'whois_privacy'  => 'Prywatność WHOIS',
        'nameservers'    => 'Serwery nazw',
        'register_price' => 'Cena rejestracji',
        'renew_price'    => 'Cena odnowienia',
        'transfer_price' => 'Cena transferu',
        'available'      => 'Dostępna',
        'taken'          => 'Niedostępna',
        'checking'       => 'Sprawdzanie dostępności…',
    ],

    // ── Komunikaty błędów ─────────────────────────────────────────────────────
    'error' => [
        'unavailable'           => 'Domena :domain nie jest dostępna do rejestracji.',
        'registration_failed'   => 'Rejestracja domeny :domain nie powiodła się. Nasz zespół został powiadomiony.',
        'renewal_failed'        => 'Odnowienie domeny :domain nie powiodło się. Prosimy skontaktować się z pomocą techniczną.',
        'transfer_failed'       => 'Transfer domeny :domain nie powiódł się. Sprawdź kod autoryzacyjny i spróbuj ponownie.',
        'nameservers_failed'    => 'Nie udało się zaktualizować serwerów nazw dla :domain.',
        'order_not_cancellable' => 'Tego zamówienia nie można anulować przy obecnym statusie.',
        'stripe_not_configured' => 'Płatności online nie są skonfigurowane. Prosimy skontaktować się z pomocą techniczną.',
    ],

    // ── Powiadomienia ─────────────────────────────────────────────────────────
    'notification' => [
        'order_placed_subject'   => 'Potwierdzenie zamówienia domeny — :domain',
        'order_placed_body'      => 'Dziękujemy za zamówienie. Przetwarzamy Twoją domenę :domain (:action).',
        'registered_subject'     => 'Twoja domena :domain została zarejestrowana',
        'registered_body'        => 'Świetna wiadomość! Twoja domena :domain została pomyślnie zarejestrowana i jest teraz aktywna.',
        'failed_subject'         => 'Rejestracja domeny nie powiodła się — :domain',
        'failed_body'            => 'Niestety nie byliśmy w stanie zarejestrować :domain. Nasz zespół został powiadomiony i skontaktuje się z Tobą wkrótce.',
    ],

];
