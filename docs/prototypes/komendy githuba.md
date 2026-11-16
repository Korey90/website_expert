# **Git i GitHub – najczęściej używane komendy**

Praktyczna ściągawka Git i GitHub dla programistów pracujących z Laravel, React i Tailwind CSS.

## **1. Podstawowe komendy Git**

| Komenda                | Opis                                                  | Praktyczna sytuacja                                                                      |
| ---------------------- | ----------------------------------------------------- | ---------------------------------------------------------------------------------------- |
| `git init`             | Inicjalizuje nowe lokalne repozytorium Git.           | Tworzysz nowy projekt Laravel i chcesz rozpocząć śledzenie zmian w kodzie.               |
| `git clone <url>`      | Klonuje zdalne repozytorium na komputer.              | Dołączasz do projektu zespołowego i pobierasz kod z GitHuba.                             |
| `git status`           | Wyświetla aktualny stan repozytorium.                 | Chcesz sprawdzić, które pliki zmodyfikowałeś przed utworzeniem commita.                  |
| `git add .`            | Dodaje wszystkie zmiany do staging.                   | Zakończyłeś pracę nad funkcjonalnością i chcesz przygotować wszystkie zmiany do commita. |
| `git add <plik>`       | Dodaje wybrany plik do staging.                       | Zmodyfikowałeś kilka plików, ale chcesz zatwierdzić tylko jeden z nich.                  |
| `git commit -m "opis"` | Zapisuje przygotowane zmiany w historii repozytorium. | Dodałeś formularz logowania i zapisujesz zmiany jako `feat: add login form`.             |
| `git log`              | Wyświetla historię commitów.                          | Chcesz sprawdzić, jakie zmiany zostały wcześniej wprowadzone w projekcie.                |
| `git log --oneline`    | Wyświetla skróconą historię commitów.                 | Potrzebujesz szybko znaleźć identyfikator konkretnego commita.                           |
| `git diff`             | Pokazuje zmiany, które nie zostały dodane do staging. | Chcesz zobaczyć dokładnie, jakie linie kodu zmieniłeś.                                   |
| `git diff --staged`    | Pokazuje zmiany dodane do staging.                    | Sprawdzasz zawartość przygotowanego commita, zanim go zapiszesz.                         |






## **2. Zarządzanie branchami**

| Komenda                 | Opis                                                              | Praktyczna sytuacja                                                   |
| ----------------------- | ----------------------------------------------------------------- | --------------------------------------------------------------------- |
| `git branch`            | Wyświetla lokalne branche.                                        | Chcesz sprawdzić, jakie branche istnieją w repozytorium.              |
| `git branch <nazwa>`    | Tworzy nowy branch bez przełączania na niego.                     | Przygotowujesz branch dla kolejnej funkcjonalności.                   |
| `git switch <nazwa>`    | Przełącza na istniejący branch.                                   | Kończysz pracę nad panelem administratora i wracasz na `main`.        |
| `git switch -c <nazwa>` | Tworzy nowy branch i od razu na niego przełącza.                  | Rozpoczynasz pracę nad logowaniem: `git switch -c feature/auth`.      |
| `git branch -d <nazwa>` | Usuwa lokalny branch, jeśli jego zmiany zostały scalone.          | Pull Request został zaakceptowany i usuwasz niepotrzebny branch.      |
| `git branch -D <nazwa>` | Wymusza usunięcie lokalnego brancha.                              | Porzuciłeś eksperymentalną funkcjonalność i chcesz usunąć jej branch. |
| `git merge <branch>`    | Scala wskazany branch z aktualnym.                                | Chcesz połączyć ukończoną funkcjonalność z branchem `develop`.        |
| `git rebase <branch>`   | Przenosi commity aktualnego brancha na koniec wskazanego brancha. | Twój branch jest nieaktualny i chcesz oprzeć go na najnowszym `main`. |

## **3. Praca ze zdalnym repozytorium GitHub**

| Komenda                             | Opis                                                                     | Praktyczna sytuacja                                                               |
| ----------------------------------- | ------------------------------------------------------------------------ | --------------------------------------------------------------------------------- |
| `git remote -v`                     | Wyświetla skonfigurowane zdalne repozytoria.                             | Sprawdzasz, z którym repozytorium GitHub połączony jest projekt.                  |
| `git remote add origin <url>`       | Dodaje zdalne repozytorium o nazwie `origin`.                            | Utworzyłeś lokalnie projekt React i chcesz połączyć go z GitHubem.                |
| `git fetch`                         | Pobiera informacje o zmianach ze zdalnego repozytorium bez ich scalania. | Chcesz sprawdzić, co zmienili inni programiści, bez modyfikowania lokalnego kodu. |
| `git pull`                          | Pobiera i integruje zmiany ze śledzonego zdalnego brancha.               | Rozpoczynasz pracę i aktualizujesz swój branch.                                   |
| `git pull origin main`              | Pobiera i integruje zmiany z `origin/main` do aktualnego brancha.        | Chcesz wprowadzić aktualizacje z `main` do brancha, nad którym pracujesz.         |
| `git push`                          | Wysyła lokalne commity do skonfigurowanego zdalnego brancha.             | Zakończyłeś pracę i wysyłasz commity na GitHub.                                   |
| `git push origin main`              | Wysyła lokalny branch `main` do zdalnego repozytorium.                   | Aktualizujesz główny branch projektu, jeśli masz do niego uprawnienia.            |
| `git push -u origin <branch>`       | Wysyła branch i konfiguruje jego śledzenie.                              | Pierwszy raz publikujesz branch `feature/user-profile` na GitHubie.               |
| `git push origin --delete <branch>` | Usuwa branch ze zdalnego repozytorium.                                   | Po zaakceptowaniu Pull Requesta sprzątasz stare branche na GitHubie.              |

## **4. Cofanie zmian i naprawianie błędów**

| Komenda                       | Opis                                                                           | Praktyczna sytuacja                                                                            |
| ----------------------------- | ------------------------------------------------------------------------------ | ---------------------------------------------------------------------------------------------- |
| `git restore <plik>`          | Przywraca plik do stanu z indeksu, odrzucając zmiany w katalogu roboczym.      | Przypadkowo zmodyfikowałeś komponent React i chcesz cofnąć niezapisane zmiany.                 |
| `git restore --staged <plik>` | Usuwa plik ze staging, ale zachowuje jego zmiany.                              | Przypadkowo dodałeś plik konfiguracyjny do przygotowywanego commita.                           |
| `git reset --soft HEAD~1`     | Cofa ostatni commit, pozostawiając jego zmiany w staging.                      | Utworzyłeś commit z błędnym opisem i chcesz przygotować go ponownie.                           |
| `git reset --hard HEAD~1`     | Cofa ostatni commit i odrzuca zmiany w śledzonych plikach do wskazanego stanu. | Chcesz całkowicie wycofać ostatni lokalny commit, świadomie rezygnując z jego zmian.           |
| `git revert <hash>`           | Tworzy nowy commit odwracający zmiany wskazanego commita.                      | Błędna funkcjonalność została wysłana na współdzielony branch i musisz ją bezpiecznie wycofać. |
| `git cherry-pick <hash>`      | Przenosi zmiany z wybranego commita na aktualny branch.                        | Poprawka błędu znajduje się na innym branchu i chcesz przenieść tylko ten commit.              |
| `git show <hash>`             | Wyświetla szczegóły wskazanego commita.                                        | Analizujesz, jakie zmiany spowodowały błąd w aplikacji.                                        |

## **5. Tymczasowe zapisywanie zmian – stash**

| Komenda           | Opis                                                                    | Praktyczna sytuacja                                                                     |
| ----------------- | ----------------------------------------------------------------------- | --------------------------------------------------------------------------------------- |
| `git stash`       | Tymczasowo zapisuje lokalne zmiany w śledzonych plikach.                | Pracujesz nad dashboardem, ale musisz pilnie przełączyć się na branch z poprawką błędu. |
| `git stash list`  | Wyświetla zapisane zestawy zmian.                                       | Chcesz sprawdzić, jakie niedokończone prace wcześniej odłożyłeś.                        |
| `git stash pop`   | Przywraca zapisane zmiany i usuwa wpis ze stash po udanym zastosowaniu. | Wracasz do pracy nad dashboardem i przywracasz poprzednie zmiany.                       |
| `git stash apply` | Przywraca zmiany, zachowując wpis w stash.                              | Chcesz zastosować zapisane zmiany bez usuwania ich kopii ze stash.                      |
| `git stash -u`    | Zapisuje zmiany również w nowych, nieśledzonych plikach.                | Utworzyłeś nowy komponent React, ale musisz tymczasowo odłożyć całą pracę.              |

## **6. Tagi i wersjonowanie aplikacji**

| Komenda                  | Opis                                                                   | Praktyczna sytuacja                                                  |
| ------------------------ | ---------------------------------------------------------------------- | -------------------------------------------------------------------- |
| `git tag`                | Wyświetla listę lokalnych tagów.                                       | Sprawdzasz wcześniejsze wersje aplikacji.                            |
| `git tag <wersja>`       | Tworzy tag wskazujący na aktualny commit.                              | Oznaczasz pierwsze stabilne wydanie aplikacji jako `v1.0.0`.         |
| `git push origin <tag>`  | Wysyła wskazany tag na GitHub.                                         | Publikujesz oznaczenie wersji aplikacji, która trafiła na produkcję. |
| `git tag -d <wersja>`    | Usuwa lokalny tag.                                                     | Przez pomyłkę utworzyłeś tag z niepoprawnym numerem wersji.          |
| `git push origin --tags` | Wysyła wszystkie lokalne tagi, których nie ma na zdalnym repozytorium. | Chcesz opublikować kilka przygotowanych tagów wersji.                |

## **7. Zarządzanie plikami i analiza historii**

| Komenda                           | Opis                                                              | Praktyczna sytuacja                                                      |
| --------------------------------- | ----------------------------------------------------------------- | ------------------------------------------------------------------------ |
| `git rm <plik>`                   | Usuwa plik i przygotowuje jego usunięcie do commita.              | Usuwasz nieużywany kontroler Laravel.                                    |
| `git rm --cached <plik>`          | Przestaje śledzić plik bez usuwania go z dysku.                   | Przypadkowo dodałeś `.env` do repozytorium i chcesz przestać go śledzić. |
| `git mv <stara> <nowa>`           | Przenosi plik lub zmienia jego nazwę.                             | Zmieniasz nazwę komponentu z `User.jsx` na `UserProfile.jsx`.            |
| `git blame <plik>`                | Pokazuje, który commit ostatnio zmienił poszczególne linie pliku. | Szukasz źródła błędu w serwisie Laravel.                                 |
| `git log --graph --oneline --all` | Wyświetla graficzną historię commitów i branchy.                  | Analizujesz, jak poszczególne funkcjonalności zostały scalone.           |

## **8. Konfiguracja Git**

| Komenda                                       | Opis                                                     | Praktyczna sytuacja                                                 |
| --------------------------------------------- | -------------------------------------------------------- | ------------------------------------------------------------------- |
| `git config --global user.name "Imię"`        | Ustawia globalną nazwę autora commitów.                  | Konfigurujesz Git po instalacji na nowym komputerze.                |
| `git config --global user.email "email"`      | Ustawia globalny adres e-mail autora commitów.           | Chcesz, aby Twoje commity były powiązane z kontem GitHub.           |
| `git config --list`                           | Wyświetla aktywną konfigurację Git.                      | Sprawdzasz, jakie ustawienia obowiązują w repozytorium.             |
| `git config --global init.defaultBranch main` | Ustawia `main` jako domyślny branch nowych repozytoriów. | Chcesz używać spójnego nazewnictwa we wszystkich nowych projektach. |

## **9. GitHub CLI – zarządzanie GitHubem z terminala**

| Komenda                  | Opis                                                                | Praktyczna sytuacja                                                                         |
| ------------------------ | ------------------------------------------------------------------- | ------------------------------------------------------------------------------------------- |
| `gh auth login`          | Loguje do GitHuba za pomocą GitHub CLI.                             | Konfigurujesz dostęp do GitHuba z terminala.                                                |
| `gh repo create`         | Tworzy nowe repozytorium na GitHubie.                               | Rozpoczynasz projekt Laravel i chcesz utworzyć repozytorium bez korzystania z przeglądarki. |
| `gh repo view --web`     | Otwiera aktualne repozytorium w przeglądarce.                       | Chcesz szybko przejść do projektu na GitHubie.                                              |
| `gh pr create`           | Tworzy Pull Request.                                                | Ukończyłeś funkcjonalność logowania i przekazujesz ją do code review.                       |
| `gh pr list`             | Wyświetla listę Pull Requestów.                                     | Sprawdzasz, jakie zmiany oczekują na review.                                                |
| `gh pr view <numer>`     | Wyświetla szczegóły wskazanego Pull Requesta.                       | Sprawdzasz opis i stan Pull Requesta.                                                       |
| `gh pr checkout <numer>` | Pobiera branch powiązany z Pull Requestem i przełącza się na niego. | Chcesz lokalnie przetestować kod innego programisty.                                        |
| `gh pr merge <numer>`    | Scala wskazany Pull Request.                                        | Code review zostało zaakceptowane i łączysz zmiany z branchem docelowym.                    |
| `gh issue list`          | Wyświetla listę zgłoszeń Issue.                                     | Szukasz zadań lub błędów do naprawienia.                                                    |
| `gh issue create`        | Tworzy nowe Issue.                                                  | Zgłaszasz błąd w panelu administratora.                                                     |

## **10. Praktyczny workflow w projekcie Laravel + React**

```
# 1. Przejdź na główny branch
git switch main

# 2. Pobierz najnowsze zmiany
git pull origin main

# 3. Utwórz branch dla nowej funkcjonalności
git switch -c feature/user-authentication

# 4. Wprowadź zmiany w kodzie Laravel i React

# 5. Sprawdź zmodyfikowane pliki
git status

# 6. Sprawdź dokładne zmiany
git diff

# 7. Dodaj pliki do staging
git add .

# 8. Utwórz commit
git commit -m "feat: add user authentication"

# 9. Opublikuj branch na GitHubie
git push -u origin feature/user-authentication

# 10. Utwórz Pull Request
gh pr create

# 11. Po scaleniu PR wróć na main
git switch main

# 12. Pobierz scalone zmiany
git pull origin main

# 13. Usuń niepotrzebny lokalny branch
git branch -d feature/user-authentication
```

**Dobre praktyki w projektach zespołowych:** pracuj na osobnych branchach, twórz małe i czytelne commity, używaj Pull Requestów do code review oraz unikaj bezpośredniego wprowadzania zmian na `main`.

**Ważne:** `git reset --hard` może bezpowrotnie usunąć lokalne zmiany. Z kolei usunięcie `.env` z aktualnego indeksu Git nie usuwa sekretów z wcześniejszych commitów — ujawnione dane dostępowe należy zmienić.