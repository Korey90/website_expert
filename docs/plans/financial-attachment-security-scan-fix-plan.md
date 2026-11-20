# UKFIN-202R2 - automatyczne skanowanie załączników finansowych

**Status:** Zakończone
**Data rozpoczęcia:** 2026-11-20
**Zakres:** prywatne załączniki finansowe w kosztach i korektach VAT

## Problem

Załącznik po uploadzie otrzymuje status `pending`, ale aplikacja nie uruchamia skanowania i nigdy nie zmienia statusu. Na serwerze nie ma ClamAV, a worker kolejki działa jako `korey`, podczas gdy katalog i pliki tworzone przez PHP-FPM są prywatne dla `www-data`.

Potwierdzony stan serwera:

- wszystkie cztery załączniki finansowe pozostają `pending`;
- `updated_at` nie zmieniło się od uploadu;
- worker kolejki działa, a `failed_jobs` jest puste;
- nie istnieje job ani serwis skanowania;
- `clamscan` i `clamdscan` nie są zainstalowane, a `clamav-daemon` jest nieaktywny;
- prywatny katalog ma tryb uniemożliwiający odczyt plików przez worker.

## Cel

Każdy nowy załącznik ma zostać automatycznie i asynchronicznie przeskanowany. Plik jest dostępny do podglądu i użycia księgowego wyłącznie po wyniku `clean`. Malware daje `rejected`, a awaria techniczna po kontrolowanych ponowieniach daje jawny status `failed`.

## Plan implementacji

1. [x] Rozszerzyć model i tabelę załączników o status `failed`, czas skanu, liczbę prób, silnik i bezpieczny kod błędu.
2. [x] Dodać adapter ClamAV z limitem czasu i ścisłą interpretacją exit code: 0 clean, 1 malware, 2 błąd techniczny.
3. [x] Przed skanem ponownie sprawdzić istnienie pliku, rozmiar i SHA-256.
4. [x] Dodać unikalny job kolejki z retry/backoff, bez utrwalania ścieżki, treści dokumentu ani surowego outputu ClamAV w logach.
5. [x] Dispatchować job po zapisie załącznika i po zakończeniu nadrzędnej transakcji DB.
6. [x] Dodać komendę do ponownego wysłania `pending` i `failed`, z filtrem ID i limitem.
7. [x] Rozszerzyć panel o status `failed`, czas skanu, bezpieczny komunikat i ręczne ponowienie skanu.
8. [x] Ustawić bezpieczne uprawnienia nowych katalogów i plików dla wspólnej grupy `www-data`.
9. [x] Dodać tłumaczenia EN, PL i PT.
10. [x] Dodać testy clean, malware, niedostępnego skanera, naruszenia integralności, retry, tenant isolation i dispatchu po uploadzie.
11. [x] Uruchomić Pint, Blade, targeted tests, pełny Finance i kontrolę tłumaczeń.
12. [x] Wdrożyć kod i migrację, następnie skonfigurować ClamAV, uprawnienia oraz przeskanować istniejącą kolejkę.

Stan punktu 12:

- [x] kod i migracja `000035` wdrożone na `website-expert.uk`;
- [x] wykonane i zweryfikowane kopie kodu i bazy;
- [x] cache odbudowany, worker zrestartowany, HTTP smoke test poprawny;
- [x] ClamAV 1.5.4 i aktualne sygnatury zostały zainstalowane;
- [x] katalog istniejących plików ma wspólną grupę `www-data` i bezpieczne tryby `2770/0660`;
- [x] test zwykłego dokumentu zwrócił `exit 0`, a test EICAR zwrócił `exit 1`;
- [x] wszystkie 8 istniejących rekordów zostało przeskanowanych do statusu `clean`;
- [x] kolejka i `failed_jobs` są puste, a audit tenantów nie wykrył błędów.

## Wynik końcowy

- Nowy upload automatycznie dispatchuje unikalny job po zakończeniu transakcji.
- Blokada unikalności joba korzysta z cache bazy danych i nie zależy od katalogów cache plikowego.
- Limity produkcyjne zachowują relację: skaner 120 sekund, job 135 sekund, `retry_after` 180 sekund.
- Panel pokazuje stan, czas i bezpieczny kod problemu oraz pozwala ponowić `pending` lub `failed`.
- Tylko `clean` może zostać pobrany, podejrzany albo użyty w workflow VAT.
- Finalna regresja: 373 testy Finance przeszły, 1 test SQLite pominięty, 2601 asercji.
- EN, PL i PT mają po 1300 zgodnych kluczy tłumaczeń.

## Zasady bezpieczeństwa

- Fail closed: tylko `clean` pozwala pobrać, podejrzeć lub użyć dowód w workflow VAT.
- Statusu `clean` nie wolno ustawiać ręcznie bez rzeczywistego wyniku skanera.
- Błąd skanera nie może zostać pomylony z czystym plikiem.
- Surowy output skanera, treść dokumentu i prywatna ścieżka nie trafiają do bazy ani interfejsu.
- Każda próba ponownie weryfikuje zapisany rozmiar i SHA-256.
- Job pobiera rekord bez globalnego scope, ale zawsze działa tylko na konkretnym ID i nie udostępnia danych użytkownikowi.

## Kryteria akceptacji

- Nowy bezpieczny PDF przechodzi `pending -> clean`.
- Plik testowy EICAR przechodzi `pending -> rejected` i nie może zostać pobrany.
- Brak ClamAV lub timeout kończy się retry, a po wyczerpaniu prób statusem `failed`.
- Zmieniony lub brakujący plik nie jest skanowany jako czysty.
- Istniejące rekordy `pending` można bezpiecznie ponownie skierować do skanowania.
- Worker ma odczyt plików, ale pliki pozostają niedostępne publicznie.

## Czynności serwerowe wymagające uprawnień administratora

Po wdrożeniu kodu właściciel serwera wykonuje instalację ClamAV i jednorazową korektę praw dostępu. Hasła `sudo` nie są przekazywane do aplikacji ani przez czat. Dokładne komendy zostaną podane po walidacji kodu i dopasowaniu do systemu serwera.
