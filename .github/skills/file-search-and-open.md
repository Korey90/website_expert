# Skill: File Search and Open (VS Code Tabs)

**Opis:** Wyszukiwanie plików po nazwie/patternie i otwieranie ich bezpośrednio w interfejsie VS Code.

**Kiedy używać:**
- Użytkownik prosi o "otwórz plik X"
- Użytkownik prosi o "otwórz losowe pliki"
- Użytkownik chce szybko przeskakiwać po kodzie bez ręcznego klikania Enter w Quick Open

---

## Zasada

Najpewniejsza metoda w tym projekcie to użycie CLI VS Code:

- `code -r -g <path>:1` dla pojedynczego pliku
- Powtórzenie komendy dla wielu plików

`-r` otwiera plik w aktualnym oknie, a `-g` przechodzi od razu do wskazanej linii.

---

## Workflow

### 1. Znajdź kandydatów

Preferuj szybkie wyszukiwanie przez `rg --files` lub `rg`.

Przykłady:

```bash
rg --files | rg -i "kalkulator|calculator"
rg --files resources/js/Pages | rg -i "welcome|kalkulator"
```

### 2. Otwórz 1 plik

```bash
code -r -g resources/js/Pages/Kalkulator.jsx:1
```

### 3. Otwórz wiele plików (pewna metoda)

```bash
code -r -g resources/js/Pages/Kalkulator.jsx:1 ; \
code -r -g resources/js/Pages/Welcome.jsx:1 ; \
code -r -g app/Events/LeadCaptured.php:1
```

### 4. Otwórz N losowych plików (PowerShell)

```powershell
Get-ChildItem -Path . -Recurse -File -Include *.php,*.js,*.jsx,*.ts,*.tsx,*.md |
  Get-Random -Count 5 |
  ForEach-Object { code -r -g "$($_.FullName):1" }
```

---

## Fallback

Jeśli otwarcie po `vscode.open` lub `quickOpen` nie przełącza kart, użyj wyłącznie metody `code -r -g`.

---

## Output dla użytkownika

Po wykonaniu podaj listę otwartych plików w formie linków do ścieżek repo.
