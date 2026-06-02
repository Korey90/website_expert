# Hook: Before Deploy

**Opis:** Obowiązkowy checklist przed wdrożeniem na produkcję.

**Trigger:** Przed każdym push na branch produkcyjny lub manualnym deploymentem.

---

## Kroki weryfikacji (NIE POMIJAJ)

### 1. Testy (wszystkie muszą przejść)

```bash
php artisan test
npm run test
echo "✅ Testy: $(php artisan test --format=json | jq '.totalCount') przechodzi"
```

### 2. Code quality

```bash
php artisan pint --test
npm run lint
echo "✅ Kod czysty"
```

### 3. Pełna walidacja

```bash
./.github/scripts/run-full-validation.sh
./.github/scripts/check-translations.sh
php ./.github/scripts/validate-multi-tenancy.php
```

### 4. Konfiguracja produkcyjna

```bash
# Sprawdź kluczowe zmienne .env produkcji
# (nie commituj .env — sprawdź na serwerze)
# APP_ENV=production
# APP_DEBUG=false
# Stripe live keys (nie test)
# Prawidłowe URL
```

### 5. Migracje

```bash
# Sprawdź czy są nowe migracje do uruchomienia
php artisan migrate:status

# Backup bazy przed migracją (jeśli zmiany schematu)
# Na serwerze: mysqldump -u user -p database > backup_$(date +%Y%m%d).sql
```

### 6. Cache refresh (po deploy)

```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan optimize
```

### 7. Sprawdź queue workers

```bash
# Upewnij się że queue worker jest uruchomiony
php artisan queue:status  # jeśli zainstalowany
# lub supervisor status na serwerze
```

---

## Checklist przed-deployowy

- [ ] `php artisan test` → wszystkie przechodzą
- [ ] `npm run test` → wszystkie przechodzą
- [ ] `php artisan pint --test` → OK
- [ ] `npm run lint` → 0 errors
- [ ] Tłumaczenia kompletne (pl + en + pt)
- [ ] Multi-tenancy walidacja OK
- [ ] Brak uncommitted zmian: `git status`
- [ ] Migracje sprawdzone i przetestowane
- [ ] `.env` produkcji ma `APP_DEBUG=false`
- [ ] Klucze Stripe produkcyjne (nie test)
- [ ] Google OAuth redirect URI na produkcyjnym URL
- [ ] Twilio numer produkcyjny

---

## Po deploy

```bash
# Na serwerze produkcyjnym
php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Sprawdź logi po deploy
tail -f storage/logs/laravel.log
```

---

## Rollback plan

Jeśli coś się psuje po deploy:
1. `git revert {commit_hash}` + deploy poprzedniej wersji
2. `php artisan migrate:rollback` jeśli były migracje
3. Sprawdź logi: `storage/logs/laravel.log`
