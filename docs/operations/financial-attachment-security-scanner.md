# Skaner bezpieczeństwa załączników finansowych

## Zakres

Aplikacja wysyła każdy nowy załącznik finansowy do asynchronicznego skanowania ClamAV. Dokument można pobrać, podejrzeć lub wykorzystać jako dowód VAT wyłącznie po wyniku `clean`.

Stany skanu:

- `pending` - dokument oczekuje na skan lub kontrolowane ponowienie;
- `clean` - ClamAV nie wykrył zagrożenia, a rozmiar i SHA-256 są zgodne;
- `rejected` - ClamAV wykrył zagrożenie;
- `failed` - skan nie zakończył się po wszystkich ponowieniach albo kontrola integralności nie przeszła.

## Jednorazowa konfiguracja Ubuntu 24.04

Zaloguj się na serwer:

```bash
ssh -i C:\Users\konrad\.ssh\id_ed25519 korey@website-expert.uk
```

Zainstaluj skaner i aktualne sygnatury:

```bash
sudo apt-get update
sudo apt-get install -y clamav clamav-freshclam
sudo systemctl stop clamav-freshclam
sudo freshclam
sudo systemctl enable --now clamav-freshclam
```

Nadaj PHP-FPM i workerowi dostęp przez wspólną grupę `www-data`:

```bash
cd /var/www/website-expert.uk
sudo chgrp -R www-data storage/app/private/finance
sudo find storage/app/private/finance -type d -exec chmod 2770 {} \;
sudo find storage/app/private/finance -type f -exec chmod 0660 {} \;
```

Nie uruchamiaj ręcznie komendy rescan. Po potwierdzeniu konfiguracji zostanie wykonana kontrola wersji, test bezpiecznego pliku, test sygnatury EICAR i dopiero wtedy wysłanie istniejących rekordów `pending` do kolejki.

## Weryfikacja operacyjna

```bash
clamscan --version
cd /var/www/website-expert.uk
find storage/app/private/finance -type f -readable -print -quit
php artisan migrate:status | grep 2026_11_20_000035
php artisan list | grep finance:scan-attachments
```

## Rescan i monitoring

Po pozytywnej weryfikacji:

```bash
cd /var/www/website-expert.uk
php artisan finance:scan-attachments --include-failed --limit=100
php artisan queue:restart
```

Panel kosztu i korekty VAT udostępnia również akcję `Ponów skan bezpieczeństwa` dla pojedynczego dokumentu w stanie `pending` albo `failed`.

## Zasady bezpieczeństwa

- Surowy output ClamAV, treść dokumentu i prywatna ścieżka nie są utrwalane w bazie.
- Każda próba skanu ponownie porównuje rozmiar i SHA-256.
- Brak skanera, timeout lub błąd techniczny nigdy nie daje statusu `clean`.
- Pliki `pending`, `rejected` i `failed` pozostają zablokowane dla pobierania.
- Worker ma wyłącznie dostęp wynikający z członkostwa w grupie `www-data`.

## Kopie przedwdrożeniowe z 2026-11-20

- kod: `storage/app/deploy-backups/20261120-220022-ukfin-202r2-predeploy.tar.gz`;
- baza: `storage/app/deploy-backups/20261120-220022-ukfin-202r2-database-before.sql.gz`;
- pakiet wdrożeniowy: `storage/app/deploy-backups/20261120-220022-ukfin-202r2-payload.tar.gz`.

## Wynik uruchomienia produkcyjnego

- ClamAV: `1.5.4`, baza sygnatur `28189` z 2026-11-20;
- test zwykłego dokumentu: `exit 0`;
- test EICAR: 68 bajtów, `exit 1`, plik testowy usunięty;
- 8 z 8 istniejących dokumentów: `clean`;
- kolejka `default`: 0 oczekujących;
- `failed_jobs`: 0;
- audit tenantów: 0 nieprawidłowych rekordów;
- smoke test: `/` zwraca 200, `/admin` dla gościa zwraca 302.
