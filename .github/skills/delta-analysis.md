# Skill: Delta Analysis (Odkrycie przed implementacją)

**Opis:** Obowiązkowy krok przed każdą implementacją. Zapobiega duplikacji i łamaniu istniejącego kodu.

**Kiedy używać:** Zawsze — przed napisaniem pierwszej linii kodu.

---

## Kroki

### 1. Znajdź anchor files

Wyszukaj istniejący kod podobny do planowanej funkcji:

```bash
# Szukaj podobnych Actions
find app/Actions -name "*.php" | head -20

# Szukaj podobnych komponentów
find resources/js/Components -name "*.tsx" | grep -i {domain}

# Szukaj podobnych modeli
grep -r "class.*Model" app/Models --include="*.php" -l

# Szukaj istniejących testów
find tests -name "*{Domain}*" -o -name "*{Feature}*"
```

### 2. Analiza multi-tenancy w module

```bash
# Sprawdź czy moduł używa business_id
grep -r "business_id" app/Models/{Domain}.php
grep -r "BelongsToTenant" app/Models/{Domain}.php
```

### 3. Sprawdź istniejące tłumaczenia

```bash
# Sprawdź istniejące klucze
ls lang/pl/
grep -r "{feature_keyword}" lang/pl/
```

### 4. Przejrzyj powiązane Events/Jobs

```bash
find app/Events -name "*{Domain}*"
find app/Jobs -name "*{Domain}*"
find app/Listeners -name "*{Domain}*"
```

### 5. Dokumentuj wyniki

Przed implementacją zapisz w `.github/live-docs/current-task.md`:
- Anchor files (co reużywasz)
- Luki (co musisz stworzyć)
- Ryzyka (co może się zepsuć)
- Zależności (kto musi to wiedzieć)

---

## Output Delta Analysis

```markdown
## Delta Analysis: {Nazwa funkcji}

### Anchor files (reużywam)
- `app/Actions/Leads/CreateLeadAction.php` — wzorzec Action
- `resources/js/Components/Leads/LeadCard.tsx` — wzorzec komponentu

### Do stworzenia
- [ ] app/Actions/{Domain}/CreateSomethingAction.php
- [ ] app/Http/Requests/StoreSomethingRequest.php
- [ ] resources/js/Pages/{Domain}/Create.tsx
- [ ] database/migrations/xxxx_create_something_table.php

### Ryzyka
- Model Something potrzebuje BelongsToTenant
- Tłumaczenia w lang/pl/something.php nie istnieją

### Wpływ na istniejący kod
- LeadController musi być zaktualizowany
- Istniejące testy mogą wymagać Factory dla Something
```
