<?php

return [
    // Sections
    'section_profile'       => 'Dane osobiste',
    'section_profile_desc'  => 'Zaktualizuj swoje imię, adres e-mail i inne dane profilowe.',
    'section_password'      => 'Zmiana hasła',
    'section_password_desc' => 'Upewnij się, że Twoje konto używa silnego, losowego hasła.',
    'section_2fa'           => 'Uwierzytelnianie dwuskładnikowe (2FA)',
    'section_2fa_desc'      => 'Zwiększ bezpieczeństwo konta, dodając drugi czynnik weryfikacji przez aplikację TOTP (np. Google Authenticator, Authy).',

    // Fields
    'name'             => 'Imię i nazwisko',
    'email'            => 'Adres e-mail',
    'phone'            => 'Telefon',
    'locale'           => 'Język interfejsu',
    'avatar'           => 'Zdjęcie profilowe',
    'current_password' => 'Aktualne hasło',
    'new_password'     => 'Nowe hasło',
    'confirm_password' => 'Powtórz nowe hasło',
    'totp_code'        => 'Kod z aplikacji TOTP (6 cyfr)',
    'totp_code_disable'=> 'Kod TOTP potwierdzający wyłączenie',

    // Buttons
    'save_profile'    => 'Zapisz profil',
    'change_password' => 'Zmień hasło',
    'cancel'          => 'Anuluj',

    // 2FA
    '2fa_active'           => '2FA aktywne',
    '2fa_inactive'         => '2FA nieaktywne',
    '2fa_enable'           => 'Włącz 2FA',
    '2fa_disable'          => 'Wyłącz 2FA',
    '2fa_confirm'          => 'Potwierdź i aktywuj',
    '2fa_scan_instruction' => 'Zeskanuj poniższy kod QR aplikacją TOTP, a następnie wpisz wygenerowany kod 6-cyfrowy.',
    '2fa_manual_key'       => 'Klucz ręczny',
    '2fa_enabled'          => '2FA zostało włączone.',
    '2fa_disabled'         => '2FA zostało wyłączone.',
    '2fa_code_invalid'     => 'Nieprawidłowy kod TOTP. Sprawdź czas w urządzeniu i spróbuj ponownie.',
    '2fa_secret_missing'   => 'Brakuje sekretu 2FA. Zacznij proces od nowa.',

    // Notifications
    'profile_saved'       => 'Profil został zapisany.',
    'password_changed'    => 'Hasło zostało zmienione.',
    'password_incorrect'  => 'Aktualne hasło jest nieprawidłowe.',
];
