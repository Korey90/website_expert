# Hook: Pre-Commit

**Opis:** Ochrona jakości kodu przed każdym commitem.

**Instalacja jako prawdziwy Git hook:**

```bash
cat > .git/hooks/pre-commit << 'HOOK'
#!/bin/bash
set -e

echo "⚙️  Pre-commit: uruchamiam walidację..."

# PHP Pint
./vendor/bin/pint --test
if [ $? -ne 0 ]; then
  echo "❌ PHP Pint FAILED — uruchom: php artisan pint"
  exit 1
fi

# ESLint
npm run lint --silent
if [ $? -ne 0 ]; then
  echo "❌ ESLint FAILED — uruchom: npm run lint"
  exit 1
fi

# Sprawdź brakujące tłumaczenia
./.github/scripts/check-translations.sh
if [ $? -ne 0 ]; then
  echo "❌ Brakujące tłumaczenia — uzupełnij lang/pl, lang/en, lang/pt"
  exit 1
fi

# Skanuj tajne dane
if git diff --cached | grep -E "(sk_live|sk_test|AKIA|password\s*=\s*['\"][^'\"]{8,})" > /dev/null 2>&1; then
  echo "❌ BEZPIECZEŃSTWO: Wykryto potencjalne tajne dane w zmianach!"
  exit 1
fi

echo "✅ Pre-commit: wszystkie sprawdzenia przeszły"
HOOK
chmod +x .git/hooks/pre-commit
echo "✅ Pre-commit hook zainstalowany"
```

---

## Sprawdzenia (w kolejności)

1. **PHP Pint** — formatowanie PHP (`./vendor/bin/pint --test`)
2. **ESLint** — linting JavaScript/TypeScript (`npm run lint`)
3. **Tłumaczenia** — brak kluczy w PL/EN/PT (`check-translations.sh`)
4. **Scan sekretów** — hardkodowane klucze API, hasła

## Jeśli check się nie powiódł

- Pint: `php artisan pint` → naprawia automatycznie
- ESLint: `npm run lint -- --fix` → naprawia automatycznie
- Tłumaczenia: uzupełnij brakujące klucze manualnie
- Sekrety: przenieś do `.env`, usuń ze staged changes
