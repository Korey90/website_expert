# Skill: Sprint Planning

**Opis:** Planowanie nowego sprintu dla solo developera. Definiuje zakres, priorytety i podział na zadania.

**Kiedy używać:** Na początku nowego bloku pracy (nowa funkcja, moduł, refaktor > 1 dzień).

---

## Kroki planowania sprintu

### 1. Przegląd backlogu

Przeczytaj:
- `.github/live-docs/project-analysis.md` — stan projektu
- `.github/live-docs/status-dashboard.md` — zdrowie kodu
- `.github/completed-tasks/` — ostatnie zakończone zadania

### 2. Zdefiniuj cel sprintu

Jeden jasny cel: *"Po tym sprincie użytkownik może [X]"*

### 3. Podziel na zadania (max 8h każde)

Dla każdego zadania:
- ID: `{PREFIX}-{numer}` (np. `DOMAIN-1`, `FEAT-1`)
- Estymata: XS (< 1h), S (1–3h), M (3–8h), L (> 8h → podziel)
- Zależności: co musi być przed

### 4. Zaktualizuj current-sprint.md

---

## Wzorzec current-sprint.md

```markdown
# Current Sprint

**Sprint Name:** {Nazwa funkcji/modułu}
**Date:** {YYYY-MM-DD} → {YYYY-MM-DD}
**Goal:** {Jeden jasny cel — co user może robić po sprincie}

## Backlog

### To Do
- [ ] {PREFIX}-1 — {Opis zadania} [S]
- [ ] {PREFIX}-2 — {Opis zadania} [M]
- [ ] {PREFIX}-3 — {Opis zadania} [M]

### In Progress
- (brak)

### Done
- (brak)

## Status
**Translation Status:** ⏳ W trakcie
**Test Coverage:** {X}/{X}
**Multi-Tenancy Compliance:** ✅ / ⏳
```

---

## Kryteria gotowości zadania (Definition of Done)

- [ ] Kod napisany i działa
- [ ] PHP Pint przeszedł
- [ ] ESLint + Prettier przeszedł
- [ ] Testy napisane i przechodzą
- [ ] Tłumaczenia pl + en + pt uzupełnione
- [ ] Multi-tenancy sprawdzone
- [ ] Live-docs zaktualizowane

---

## Priorytety dla solo developera

1. **Krytyczne** — coś nie działa w produkcji
2. **Blokujące** — zależność dla kolejnych zadań
3. **Wartość biznesowa** — przynosi wartość użytkownikowi
4. **Tech debt** — utrudnia dalszy rozwój
5. **Nice-to-have** — wygodne ale nieważne

---

## Rozmiar sprintu (solo dev)

- 1 tydzień = 3–5 zadań M lub 6–10 zadań S
- Zostaw 20% bufor na niespodziewane
- Nie planuj L bez podziału na mniejsze
