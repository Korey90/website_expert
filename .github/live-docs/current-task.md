# Current Task

**Status:** Brak aktywnego zadania

**Last Updated:** 2026-07-01

---

Zadanie modułu zarządzania kontem/profilem admina zostało zakończone.
Szczegóły w: `completed-tasks/2026-07-01 - Modul konta admina.md` (opcjonalnie).

---

## Zadanie: Moduł zarządzania kontem/profilem w panelu admina `/admin/account`

> Scope: tylko panel Filament (`/admin`). Żadnych zmian w `ProfileController` ani portalu klienta.

### Pliki do stworzenia

| Plik | Opis |
|------|------|
| `database/migrations/..._add_two_factor_to_users_table.php` | Kolumny: `google_2fa_secret` (nullable, encrypted), `two_factor_enabled` (bool, default false) |
| `app/Actions/Account/UpdateAdminProfileAction.php` | Aktualizacja name/email/phone/locale/avatar_url |
| `app/Actions/Account/ChangePasswordAction.php` | Weryfikacja current_password + zmiana hasła |
| `app/Actions/Account/EnableTwoFactorAction.php` | Generowanie sekretu TOTP + QR code (pragmarx/google2fa-qrcode) |
| `app/Actions/Account/DisableTwoFactorAction.php` | Weryfikacja kodu TOTP + wyłączenie 2FA |
| `app/Filament/Pages/AccountProfilePage.php` | Strona `/admin/account`, BasePage, 3 sekcje |
| `resources/views/filament/pages/account-profile.blade.php` | Widok blade |
| `lang/pl/account.php` | Tłumaczenia PL |
| `lang/en/account.php` | Tłumaczenia EN |
| `lang/pt/account.php` | Tłumaczenia PT |

### Pliki do modyfikacji

| Plik | Zmiana |
|------|--------|
| `app/Models/User.php` | Dodać `google_2fa_secret`, `two_factor_enabled` do `#[Fillable]` + `$casts` |

### Sekcje strony `/admin/account`

**Sekcja 1 — Dane osobiste**
- `name` (wymagane), `email` (wymagane, unique), `phone`, `locale` (select: en/pl/pt), `avatar_url` (FileUpload → `public/avatars`)

**Sekcja 2 — Zmiana hasła**
- `current_password` (password input), `password` (nowe), `password_confirmation`
- Akcja `changePassword()` — osobna od save()

**Sekcja 3 — Uwierzytelnianie dwuskładnikowe (TOTP)**
- Toggle `two_factor_enabled`
- Gdy włączanie: generuj sekret → wyświetl QR (inline SVG) + kod tekstowy → pole na potwierdzenie kodu → zapis
- Gdy wyłączanie: pole na kod TOTP → weryfikacja → wyłączenie
- Sekret szyfrowany w DB (`google_2fa_secret` via Laravel `encrypted:string`)

### Nawigacja Filament
- Icon: `heroicon-o-user-circle`
- Bez grupy (top-level)
- Sort: `-1` (nad Dashboard)
- Label: `My Account`

### Co NIE wchodzi w zakres
- Zmiany w `ProfileController` (portal klienta) — izolacja zachowana
- `AccountDeletionService` — już istnieje, nie ruszamy
- Aktywne sesje, WebAuthn — osobne zadania
