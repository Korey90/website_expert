# Instrukcje konfiguracji serwera — Ubuntu 24.04.4 LTS + Apache + MySQL

> Wykonaj poniższe kroki po zalogowaniu się na serwer przez SSH.

---

## 1. Włącz wymagane moduły Apache

```bash
sudo a2enmod rewrite deflate brotli expires headers
sudo systemctl restart apache2
```

Sprawdź czy moduły są aktywne:

```bash
apache2ctl -M | grep -E "rewrite|deflate|brotli|expires|headers"
```

Oczekiwany wynik (każde `_module (shared)`):
```
brotli_module (shared)
deflate_module (shared)
expires_module (shared)
headers_module (shared)
rewrite_module (shared)
```

> **Jeśli `mod_brotli` nie jest dostępne** (starsze Ubuntu):
> ```bash
> sudo apt install libapache2-mod-brotli -y
> sudo a2enmod brotli
> sudo systemctl restart apache2
> ```

---

## 2. Konfiguracja VirtualHost (`AllowOverride All`)

Edytuj plik konfiguracyjny swojej domeny (przykład — nazwa pliku może się różnić):

```bash
sudo nano /etc/apache2/sites-available/website-expert.conf
```

Upewnij się, że sekcja `<Directory>` dla `public/` wygląda tak:

```apache
<VirtualHost *:80>
    ServerName website-expert.uk
    ServerAlias www.website-expert.uk
    DocumentRoot /var/www/website-expert/public

    <Directory /var/www/website-expert/public>
        AllowOverride All       # ← WYMAGANE, żeby .htaccess działał
        Require all granted
        Options -Indexes
    </Directory>

    ErrorLog ${APACHE_LOG_DIR}/website-expert-error.log
    CustomLog ${APACHE_LOG_DIR}/website-expert-access.log combined
</VirtualHost>
```

Po edycji:

```bash
sudo a2ensite website-expert.conf
sudo apache2ctl configtest       # sprawdź czy brak błędów
sudo systemctl reload apache2
```

---

## 3. Certbot (HTTPS + HTTP/2)

Jeśli SSL nie jest jeszcze skonfigurowany:

```bash
sudo apt install certbot python3-certbot-apache -y
sudo certbot --apache -d website-expert.uk -d www.website-expert.uk
```

Po ustawieniu SSL włącz HTTP/2 (znacząca poprawa dla SPA):

```bash
sudo a2enmod http2
```

W pliku VirtualHost HTTPS (automatycznie tworzonym przez Certbot) dodaj:

```apache
<VirtualHost *:443>
    Protocols h2 http/1.1       # ← HTTP/2 priorytetowo
    ...
</VirtualHost>
```

```bash
sudo systemctl reload apache2
```

---

## 4. PHP — opcimalizacje dla Laravel

Edytuj `php.ini` (znajdź właściwą wersję PHP):

```bash
php -r "echo php_ini_loaded_file();"   # wyświetla ścieżkę do aktywnego php.ini
sudo nano /etc/php/8.x/apache2/php.ini  # zastąp 8.x faktyczną wersją
```

Zmień lub dodaj:

```ini
opcache.enable=1
opcache.memory_consumption=256
opcache.interned_strings_buffer=16
opcache.max_accelerated_files=20000
opcache.validate_timestamps=0      ; ustaw na 1 jeśli często edytujesz pliki
opcache.revalidate_freq=0
opcache.fast_shutdown=1
```

```bash
sudo systemctl restart apache2
```

---

## 5. MySQL — upewnij się że connection pooling działa

Sprawdź czy `persistent connections` są aktywne w Laravel (opcjonalne):

```bash
# w pliku config/database.php, sekcja mysql — upewnij się że:
# 'options' => [PDO::ATTR_PERSISTENT => true],
# Uwaga: persistent connections mogą powodować problemy w środowisku wielowątkowym
```

---

## 6. Deploy aplikacji Laravel

Wykonuj po każdym wgraniu nowej wersji kodu:

```bash
cd /var/www/website-expert

# 1. Pobierz kod z repo
git pull origin main

# 2. Zainstaluj zależności PHP (produkcja)
composer install --no-dev --optimize-autoloader

# 3. Zainstaluj zależności JS i zbuduj assety
npm ci --legacy-peer-deps
npm run build

# 4. Migracje bazy danych
php artisan migrate --force

# 5. Wyczyść i zoptymalizuj cache Laravel
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache
php artisan icons:cache   # tylko jeśli używasz Blade-icons (FilamentPHP)

# 6. Restartuj kolejkę (jeśli używasz supervisor)
php artisan queue:restart
```

> **Skrót**: możesz zapisać powyższe jako skrypt `deploy.sh` w głównym katalogu projektu.

---

## 7. Supervisor — worker kolejki (jeśli używasz Jobs)

```bash
sudo apt install supervisor -y
sudo nano /etc/supervisor/conf.d/website-expert-worker.conf
```

Zawartość:

```ini
[program:website-expert-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/website-expert/artisan queue:work --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=2
redirect_stderr=true
stdout_logfile=/var/log/website-expert-worker.log
stopwaitsecs=3600
```

```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start website-expert-worker:*
```

---

## 8. Crontab — Laravel Scheduler

```bash
sudo crontab -e -u www-data
```

Dodaj linię:

```cron
* * * * * cd /var/www/website-expert && php artisan schedule:run >> /dev/null 2>&1
```

---

## 9. Uprawnienia katalogów

```bash
cd /var/www/website-expert
sudo chown -R www-data:www-data storage bootstrap/cache
sudo chmod -R 775 storage bootstrap/cache
```

---

## 10. Weryfikacja po wdrożeniu

```bash
# Sprawdź czy kompresja działa (powinno być "Content-Encoding: br" lub "gzip")
curl -I -H "Accept-Encoding: br, gzip" https://website-expert.uk/build/assets/app.js 2>/dev/null | grep -i "content-encoding"

# Sprawdź Cache-Control (powinno być "max-age=31536000, immutable")
curl -I https://website-expert.uk/build/assets/app.js 2>/dev/null | grep -i "cache-control"

# Sprawdź HTTP/2
curl --http2 -I https://website-expert.uk 2>/dev/null | grep "HTTP/"
```

---

## Podsumowanie oczekiwanego wpływu na PageSpeed

| Zmiana | Oczekiwany zysk mobile |
|--------|------------------------|
| Self-hosted fonts (brak bloku Google Fonts) | +8–12 pkt |
| Brotli/Gzip na Apache | +3–5 pkt |
| Cache-Control immutable (Vite assets) | +3–4 pkt |
| React.lazy dla below-fold | +4–6 pkt |
| Fix `.reveal` transition-all | +3–5 pkt |
| Ukrycie blur-3xl na mobile | +2–4 pkt |
| Defer GTM do `load` event | +1–2 pkt |
| HTTP/2 + OPcache | +2–4 pkt |
| **Razem** | **+26–42 pkt** → cel: ~95+ mobile |
