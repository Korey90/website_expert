# Skill: Quick Fix (Hotfix Workflow)

**Opis:** Szybka naprawa buga bez pełnego workflow zadania. Dla zmian < 1h.

**Kiedy używać:** Bug w produkcji lub oczywista drobna poprawka (< 30 linii kodu).

---

## Zasada Quick Fix

Tylko dla bugów które:
- Są izolowane (nie dotykają architektury)
- Mają wyraźną przyczynę
- Wymagają < 30 linii kodu
- Nie zmieniają API ani DB schema

Jeśli którekolwiek z powyższych nie jest spełnione → użyj pełnego workflow.

---

## Kroki Quick Fix

### 1. Zlokalizuj bug (max 5 min)

```bash
# Sprawdź logi
php artisan pail
tail -f storage/logs/laravel.log

# Szukaj w kodzie
grep -rn "{error_message}" app resources --include="*.php" --include="*.tsx"
```

### 2. Zrozum przyczynę przed naprawą

Zadaj sobie pytania:
- Dlaczego to się stało?
- Czy ten bug jest w innych miejscach?
- Czy fix nie złamie czegoś innego?

### 3. Napraw

- Minimalna zmiana — tylko to co potrzebne
- Nie refaktoryzuj przy okazji
- Nie dodawaj feature'ów

### 4. Zweryfikuj

```bash
# Pint dla PHP
php artisan pint {changed_file}

# Test dla dotkniętego obszaru
php artisan test --filter={RelatedTest}

# Jeśli nie ma testu — dodaj go
```

### 5. Commit z opisem

```bash
git add {specific_files}
git commit -m "fix: {short description of what was wrong and how fixed}"
```

---

## Wzorzec commit message dla bugfixa

```
fix: {krótki opis problemu}

Przyczyna: {co powodowało błąd}
Rozwiązanie: {jak naprawiono}
Wpływ: {co to dotyka}
```

Przykład:
```
fix: lead status not updating on pipeline drag

Przyczyna: UpdateLeadAction nie respektowała business_id przy update
Rozwiązanie: Dodano where business_id w query
Wpływ: Tylko LeadController@updateStatus
```

---

## Kiedy Quick Fix jest niewystarczający

Jeśli podczas naprawy odkryjesz:
- Problem architektoniczny (zły wzorzec, zła struktura)
- Bug wynikający z braku testów
- Problem w wielu miejscach

→ **Zatrzymaj się**, udokumentuj w `.github/live-docs/current-task.md` jako nowe zadanie, i wróć do pełnego workflow.

---

## Post-fix checklist

- [ ] Bug naprawiony i zweryfikowany manualnie
- [ ] Test dodany (zapobiega regresji)
- [ ] `php artisan test` przechodzi
- [ ] Commit zrobiony z opisowym message
