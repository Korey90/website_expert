# Threat model — UK Finance & Tax

**Wersja:** 2026-11-04  
**Zakres:** rekordy finansowe tenantów, dane podatkowe, przyszłe integracje bankowe i HMRC.

## 1. Chronione zasoby

| Klasa danych | Przykłady | Klasyfikacja |
|---|---|---|
| Dane księgowe | faktury, płatności, koszty, journal entries | poufne |
| Identyfikatory podatkowe | UTR, PAYE reference, Accounts Office reference | wysoce poufne |
| Dane HMRC | access/refresh token, VRN, correlation ID | sekrety / poufne |
| Dane bankowe | rachunek, sort code, transakcje, raw provider payload | wysoce poufne |
| Dowody źródłowe | receipts, invoices, bank statements | wysoce poufne |
| Audit trail | actor, timestamp, payload hash, decyzje approval/posting | integralność krytyczna |

## 2. Granice zaufania

- przeglądarka ↔ Laravel/Filament;
- publiczny webhook Stripe/PayU ↔ aplikacja;
- queue worker/scheduler ↔ baza danych;
- aplikacja ↔ HMRC API/OAuth;
- aplikacja ↔ przyszły dostawca Open Banking;
- aplikacja ↔ prywatny storage załączników;
- tenant A ↔ tenant B w tej samej bazie.

## 3. Najważniejsze zagrożenia i kontrolki

| Zagrożenie | Wpływ | Wymagane kontrolki | Stan |
|---|---|---|---|
| IDOR / cross-tenant query | ujawnienie pełnej księgowości innej firmy | `business_id`, global scope, policies, scoping selectów, testy dwóch tenantów | wdrożone dla contacts/projects/quotes/invoices/payments; rozszerzać na każdy nowy model |
| Rekord bez tenanta w webhooku/jobie | rekord widoczny poza poprawnym kontekstem | tenant dziedziczony z invoice/order, deploy audit, docelowo NOT NULL | wdrożone; NOT NULL po audycie produkcji |
| Kradzież UTR/PAYE z dumpa DB | fraud/tożsamość podatkowa | encrypted casts, ograniczone logi i eksporty, rotacja APP_KEY z procedurą re-encryption | wdrożone w `AccountingProfile` |
| Kradzież tokenów HMRC | nieautoryzowane odczyty/submission | szyfrowanie, najmniejsze scopes, token refresh lock, revoke, redaction, brak tokenów w logs | release gate przed Fazą 6 |
| Powtórzenie HMRC POST | podwójna/niejednoznaczna deklaracja | idempotency key, immutable snapshot, correlation ID, brak ślepego retry POST | release gate przed Fazą 6 |
| Fałszywy webhook płatniczy | fałszywe oznaczenie faktury jako paid | weryfikacja podpisu, idempotency po provider ID, kwota/waluta zgodna z invoice | Stripe istnieje; PayU wymaga ponownego audytu przed release finance |
| Modyfikacja posted entry | utrata integralności księgi | immutable posted journals, reversal zamiast update/delete, audit log | release gate Fazy 3 |
| Publiczny dostęp do receipt | wyciek dokumentów i danych bankowych | private disk, autoryzowany streaming, losowe ścieżki, MIME/size check, SHA-256 | release gate Fazy 2 |
| CSV formula injection w eksporcie | wykonanie formuły u księgowego | neutralizacja `=`, `+`, `-`, `@`, testy eksportu | release gate Fazy 5 |
| SSRF przez import URL/provider | dostęp do sieci wewnętrznej | brak dowolnych URL, allowlista endpointów, timeout, blokada redirectów | release gate integracji |
| Nadmierne dane w logach | trwały wyciek PII/sekretów | structured redaction, payload hash zamiast payloadu, sanitized errors | wymagane w każdej integracji |

## 4. Role i separacja obowiązków

- `view_finance`: odczyt danych finansowych własnej firmy;
- `manage_finance`: tworzenie i edycja draftów;
- `approve_finance`: zatwierdzanie dokumentów;
- `post_finance`: księgowanie do ledger;
- `close_finance_periods`: zamknięcie/lock okresu;
- `submit_tax_returns`: osobne, najwyższe uprawnienie do świadomego submission.

Manager nie otrzymuje domyślnie `close_finance_periods` ani `submit_tax_returns`. Submission wymaga ekranu review i ponownego potwierdzenia użytkownika; samo posiadanie sesji nie może uruchamiać wysyłki automatycznie.

## 5. Kontrole operacyjne

Przed deployem modułu finansowego:

1. Wykonać backup i sprawdzić procedurę odtworzenia.
2. Uruchomić migracje w trybie maintenance zgodnie z runbookiem.
3. Uruchomić `php artisan finance:audit-tenants --json`; wynik musi mieć `valid: true`.
4. Zweryfikować role/uprawnienia na koncie testowym managera i admina.
5. Sprawdzić, że logi nie zawierają UTR/PAYE/tokenów ani pełnych payloadów.
6. Przeprowadzić test cross-tenant na środowisku staging.

## 6. Ryzyka resztkowe / decyzje otwarte

- Kolumny `business_id` pozostają nullable do czasu czystego audytu produkcji.
- Domyślne mapowania pól VAT Return są szkieletem technicznym i wymagają zatwierdzenia księgowego UK.
- Polityka rotacji `APP_KEY` musi zawierać re-encryption danych zaszyfrowanych.
- PayU wymaga potwierdzenia mechanizmu autentyczności callbacku oraz idempotencji.
- Retencja dokumentów i audit trail musi zostać potwierdzona per entity type i obowiązki podatkowe.
