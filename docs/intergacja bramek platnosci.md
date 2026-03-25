# Plan wdrazania bramki platnosci.

## Stan wyjściowy

| Co | Status |
|---|---|
| `Payment` model + migracja | ✅ gotowe (pola: method, status, stripe_payment_intent_id) |
| `stripe/stripe-php` package | ✅ zainstalowany |
| `StripeWebhookController` | ✅ istnieje (obsługuje webhooki) |
| `settings` tabela (key-value) | ✅ gotowa |
| `SmsService::send()` | ✅ gotowy (Twilio) |
| PayU package | ❌ brak |
| `PaymentController` | ❌ brak |
| UI płatności w portalu | ❌ jest tylko statyczny link Stripe |
| Historia płatności (Filament) | ❌ brak |
| Powiadomienia po płatności | ❌ brak |

---

## Plan wdrożenia — 6 faz

### Faza 1 — Settings UI (Stripe + PayU) `~2h`

**Nowe klucze w tabeli `settings`** (group: `payments`):

| klucz | opis |
|---|---|
| `stripe_enabled` | `0/1` |
| `stripe_pk` | Publishable Key |
| `stripe_sk` | Secret Key |
| `stripe_webhook_secret` | Webhook signing secret |
| `payu_enabled` | `0/1` |
| `payu_sandbox` | `0/1` |
| `payu_pos_id` | POS ID |
| `payu_md5_key` | MD5 klucz do weryfikacji IPN |
| `payu_client_id` | OAuth2 Client ID |
| `payu_client_secret` | OAuth2 Client Secret |

**Filament:** nowa zakładka "Payments" w istniejącym Settings lub dedykowana strona settings — toggles, masked input dla kluczy, przycisk "Test Connection".

---

### Faza 2 — Stripe Checkout `~3h`

**Przepływ:**

```
Portal: kliknie "Pay" → POST /portal/invoices/{id}/pay/stripe
  → PHP: Stripe\Checkout\Session::create(...)
  → redirect → Stripe hosted page
  → po płatności → redirect /portal/invoices/{id}?paid=1
                 → webhook POST /stripe/webhook
  → StripeWebhookController: checkout.session.completed
  → utwórz Payment, Invoice::recalculate()
```

**Do zrobienia:**
- Rozszerzenie `StripeWebhookController::handle()` o event `checkout.session.completed`
- `PaymentController::stripeCheckout(Invoice $invoice)` — tworzy Session, redirect
- Route: `POST /portal/invoices/{invoice}/pay/stripe`
- Metadata w Stripe Session: `invoice_id`, `client_id` → do weryfikacji w webhooku

---

### Faza 3 — PayU Integration `~5h`

Brak oficjalnego Laravel package — własny `PayuService` via `Http::` facade.

**PayU REST API v2.1 (sandbox + prod):**

```
OAuth2: POST https://secure.payu.com/pl/standard/user/oauth/authorize
Create Order: POST /api/v2_1/orders
IPN: POST /payu/notify (od PayU)
Return URL: GET /portal/invoices/{id}?payu_status=...
```

**Do zrobienia:**
- `app/Services/PayuService.php` — `getToken()`, `createOrder()`, `verifySignature()`
- `PayuWebhookController` — weryfikacja MD5, obsługa `COMPLETED`
- `PaymentController::payuOrder(Invoice $invoice)` — tworzy order, redirect na PayU
- Route: `POST /portal/invoices/{invoice}/pay/payu`
- Route: `POST /payu/notify` (CSRF-exempt)
- Nowa wartość w `payments.method` enum: dodać `payu` do migracji/modelu

---

### Faza 4 — Portal: strona wyboru metody płatności `~2h`

```
/portal/invoices/{id} → klik "Pay Online"
  → nowa strona /portal/invoices/{id}/pay
  → dwie opcje:
     [💳 Card / Stripe]  [🏦 PayU (przelew, BLIK, karty PL)]
  → po wyborze → POST do odpowiedniego kontrolera
```

**Warunkowe wyświetlanie** komponentów na podstawie `settings.stripe_enabled` / `settings.payu_enabled` — kontroler przekazuje `$methods` do Reacta.

---

### Faza 5 — Filament: Historia płatności `~2h`

**`PaymentResource`** (Filament) w grupie Finance:

- Lista: numer faktury, klient, kwota, method (badge), status (badge), data
- Filtry: status, method, zakres dat
- View: szczegóły + możliwość ręcznego dodania płatności (bank transfer / gotówka)
- **Relacja z fakturą**: w `ViewInvoice` — sekcja "Payments" z listą wpłat

---

### Faza 6 — Powiadomienia `~2h`

| Zdarzenie | Akcja |
|---|---|
| Płatność potwierdzona (webhook) | `PaymentReceivedMail` → klient |
| Płatność potwierdzona | SMS via `SmsService` → klient (jeśli `twilio_enabled`) |
| Faktura w pełni opłacona | `notify_admin` + dispatch `invoice.paid` automation trigger |
| Płatność nieudana (Stripe) | Email + SMS z linkiem do ponownej płatności |

**Nowa klasa:** `app/Mail/PaymentReceivedMail.php` z widokiem `emails.payment-received`

---

## Kolejność wdrożenia (priorytet)

```
[1] Faza 1 — Settings UI          │ blokuje resztę
[2] Faza 2 — Stripe               │ package już jest, najszybciej
[3] Faza 5 — Historia płatności   │ widoczność dla admina
[4] Faza 6 — Powiadomienia        │ uzupełnia Stripe
[5] Faza 3 — PayU                 │ większy nakład pracy, sandbox do testów
[6] Faza 4 — Strona wyboru        │ łączy obie metody
```

---

## Wymagania zewnętrzne przed startem

| Czego potrzebujesz | Gdzie |
|---|---|
| Stripe API keys (test) | dashboard.stripe.com |
| Stripe Webhook Secret | Stripe → Webhooks → signing secret |
| PayU Sandbox credentials | secure.snd.payu.com (rejestracja) |
| PayU POS ID + MD5 key | Panel PayU → POS-y |

---

Od której fazy chcesz zacząć?