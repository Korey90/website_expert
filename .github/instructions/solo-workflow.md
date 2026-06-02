# Solo Developer Workflow

**Kontekst:** Projekt rozwijany przez jedną osobę. Zasady workflow dostosowane do pracy solo.

---

## Podstawowa zasada

Pracujesz sam, więc masz pełną kontrolę — ale też pełną odpowiedzialność za jakość. Kompensuj brak drugiej osoby przez systematyczny proces i automatyzację.

---

## Codzienny rytm pracy

### Na początku sesji (5 min)

1. Przeczytaj `.github/live-docs/current-task.md` — gdzie skończyłeś
2. Sprawdź `.github/live-docs/status-dashboard.md` — stan projektu
3. Uruchom testy: `php artisan test` — czy baseline jest OK
4. Uruchom środowisko: `npm run dev:all`

### W trakcie pracy

- Commity często — każdy logiczny krok osobno
- `git stash` przed eksperymentami
- Aktualizuj `current-task.md` po każdym etapie
- Nie pracuj nad dwoma zadaniami jednocześnie

### Na końcu sesji (5 min)

- Commit niezakończonej pracy: `git commit -m "wip: {opis}"`
- Zaktualizuj `current-task.md` — co następne
- Sprawdź testy: `php artisan test`

---

## Priorytety decyzyjne (sam zdecydujesz)

Gdy masz wątpliwość architektoniczną, zadaj sobie pytania w tej kolejności:

1. **Czy to jest zgodne z project-rules.md?** → jeśli nie, odrzuć
2. **Czy istniejący kod robi to samo?** → jeśli tak, reużyj
3. **Czy to jest najprostsze rozwiązanie?** → złożoność = dług techniczny
4. **Czy to da się łatwo przetestować?** → jeśli nie, zmień podejście
5. **Czy za 3 miesiące zrozumiem to bez notatek?** → jeśli nie, nazwij lepiej lub skomentuj

---

## Zarządzanie scope (feature creep)

Pracując solo łatwo się "rozlec" na więcej niż planowano.

Jeśli w trakcie implementacji odkryjesz coś nowego:
- Zapisz w `current-task.md` sekcja "Do zrobienia później"
- **Nie implementuj teraz** — skończ bieżące zadanie
- Wróć do nowego po zamknięciu bieżącego sprintu

---

## Komunikacja z agentem

- Zawsze opisz kontekst na początku: "Pracuję nad [zadaniem] w [modułu]"
- Powiedz co już sprawdziłeś: "Patrzyłem na [X] i [Y]"
- Bądź konkretny: zamiast "coś nie działa" podaj błąd i plik
- Zatwierdź plan przed implementacją: "OK, zrób to"

---

## Kiedy zatrzymać się i przemyśleć

- Implementujesz coś > 200 linii kodu bez testu → zatrzymaj się, napisz test
- Widzisz że naruszasz project-rules.md → zatrzymaj się, zapytaj agenta
- Zadanie trwa 2x dłużej niż estymowane → zrób retrospektywę
- Pojawiły się 3+ nowe pliki których nie planowałeś → scope creep

---

## Retrospektywa (co 2 tygodnie, 15 min)

Przejrzyj `.github/completed-tasks/` i odpowiedz:
1. Co zajęło więcej czasu niż planowano? Dlaczego?
2. Jakie bugi były łatwe do uniknięcia?
3. Co można uprościć lub zautomatyzować?
4. Czy tech debt rośnie czy maleje?
