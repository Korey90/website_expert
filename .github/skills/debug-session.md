# Skill: Debug Session

**Opis:** Systematyczny workflow debugowania dla trudnych bugów.

**Kiedy używać:** Bug którego przyczyna nie jest oczywista, lub problem w produkcji.

---

## Zasada debugowania

Nie zgaduj — zbieraj dowody. Każdy krok hipotezy musi być potwierdzony przez logi lub kod.

---

## Kroki Debug Session

### 1. Zbierz informacje (co dokładnie się dzieje)

```bash
# Logi aplikacji
php artisan pail
tail -n 100 storage/logs/laravel.log

# Logi kolejki
php artisan queue:work --verbose

# Logi HTTP (jeśli API)
grep "500\|ERROR" storage/logs/laravel.log | tail -20
```

### 2. Reprodukuj bug

- Minimalny przypadek odtworzenia
- Które kroki dokładnie prowadzą do błędu
- Czy dzieje się zawsze, czy sporadycznie?

### 3. Izoluj obszar

```bash
# Znajdź gdzie rzucany jest wyjątek
grep -rn "{ErrorClass}\|{error_message}" app --include="*.php"

# Sprawdź ostatnie zmiany w tym obszarze
git log --oneline -10 -- {file_or_directory}
git diff HEAD~1 -- {file}
```

### 4. Narzędzia debugowania

```bash
# Laravel Tinker — testuj logikę bez HTTP
php artisan tinker
>>> $model = App\Models\Lead::find(1);
>>> app(App\Actions\Leads\CreateLeadAction::class)->execute($data);

# Tymczasowe logowanie (usuń po debugowaniu)
Log::debug('Debug point', ['variable' => $value, 'context' => $context]);

# Sprawdź request w kontrolerze (tymczasowo)
Log::debug('Request data', $request->all());
```

### 5. Weryfikuj hipotezę

Przed naprawą:
- Napisz test który reprodukuje bug: `php artisan test --filter=BugReproductionTest`
- Test powinien FAILOWAĆ
- Napraw kod
- Test powinien PRZECHODZIĆ

### 6. Fix i cleanup

```bash
# Usuń wszystkie tymczasowe Log::debug
grep -rn "Log::debug" app --include="*.php"

# Uruchom testy całego obszaru
php artisan test --filter={Module}

# Sprawdź że pint jest zadowolony
php artisan pint
```

---

## Typowe bugi i szybkie diagnozy

| Objaw | Sprawdź |
|-------|---------|
| 403 Forbidden | Policy + role użytkownika |
| 422 Unprocessable | Form Request rules + dane wejściowe |
| 500 Server Error | `storage/logs/laravel.log` + stack trace |
| Brakujące dane | `business_id` scope + `BelongsToTenant` |
| Job się nie wykonuje | `php artisan queue:work` uruchomiony? DB queue działa? |
| WebSocket nie działa | `php artisan reverb:start` uruchomiony? Channel name? |
| Tłumaczenie missing | `check-translations.sh` + klucz w lang/ |
| TypeScript error | `npm run lint` + sprawdź interfejs komponentu |

---

## Dokumentacja buga (po naprawie)

Zapisz w commit message:
```
fix: {krótki opis}

Root cause: {co powodowało błąd i dlaczego}
Fix: {co zmieniono}
Test: Dodano {TestName} który pokrywa ten przypadek
```
