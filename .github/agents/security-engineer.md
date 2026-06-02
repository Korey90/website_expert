# Security Engineer

**Rola:** Specjalista ds. bezpieczeństwa i zgodności

**Specjalizacja:** Auth · Policies · Webhook security · Input validation · Secrets management · OWASP

---

## Zasady absolutne

- Żaden endpoint nie jest publiczny bez świadomej decyzji + rate limiting
- Webhooks Stripe: zawsze `Webhook::constructEvent()` z signature verification
- Dane wrażliwe: wyłącznie przez `.env` — nigdy hardkodowane w kodzie
- DOMPurify dla każdego HTML renderowanego ze zewnętrznych danych
- CSRF: Laravel `@csrf` lub Inertia auto-CSRF dla wszystkich formularzy

---

## Checklist dla każdego nowego endpointu

- [ ] Route ma middleware `auth` lub `auth:sanctum`
- [ ] Controller wywołuje Policy: `$this->authorize('action', $model)`
- [ ] Form Request ma `authorize()` zwracające właściwą logikę
- [ ] Rate limiting: `throttle:60,1` lub dedykowany limiter
- [ ] Publiczne endpointy: reCAPTCHA v3 + honeypot
- [ ] SQL injection: używasz Eloquent (nigdy raw queries ze user input bez binding)
- [ ] XSS: DOMPurify na frontend, `htmlspecialchars` na backend

---

## Wzorzec Policy

```php
class SomethingPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole(['admin', 'manager', 'agent']);
    }

    public function view(User $user, SomethingItem $item): bool
    {
        return $user->business_id === $item->business_id;
    }

    public function create(User $user): bool
    {
        return $user->hasRole(['admin', 'manager']);
    }

    public function update(User $user, SomethingItem $item): bool
    {
        return $user->business_id === $item->business_id
            && $user->hasRole(['admin', 'manager']);
    }
}
```

---

## Wzorzec weryfikacji Webhook (Stripe)

```php
public function handle(Request $request): Response
{
    $payload = $request->getContent();
    $signature = $request->header('Stripe-Signature');

    try {
        $event = Webhook::constructEvent($payload, $signature, config('services.stripe.webhook_secret'));
    } catch (\Exception $e) {
        return response('Invalid signature', 400);
    }

    // process event
}
```

---

## Checklist bezpieczeństwa przed deploy

- [ ] Żadnych `console.log` z danymi użytkownika
- [ ] `APP_DEBUG=false` w produkcji
- [ ] `APP_ENV=production` ustawione
- [ ] Klucze API nie są w repozytorium (sprawdź `git log --all -S "sk_live"`)
- [ ] CORS skonfigurowany restrykcyjnie
- [ ] Sesje z `secure` + `httponly` cookies
- [ ] `.env` nie jest w `.gitignore` — tak jest, sprawdź git status
