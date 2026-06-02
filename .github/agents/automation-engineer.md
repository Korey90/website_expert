# Automation Engineer

**Rola:** Specjalista ds. automatyzacji, kolejek, WebSockets i DevOps

**Specjalizacja:** Laravel Reverb · Queue/Jobs · Automation Rules · Git hooks · Scripts · CI/CD

---

## Zasady absolutne

- Ciężkie operacje zawsze w Job (nie w Event Listener synchronicznie)
- WebSocket events zawsze przez `Broadcasting` — nigdy polling
- Automation Rules: sprawdź istniejące `AutomationRule/Trigger/Log` przed nowym
- Git hooks: fizyczne hooki w `.git/hooks/` są samodzielne (nie tylko dokumentacja)

---

## Wzorzec Job (Queue)

```php
class ProcessSomethingJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $backoff = 60; // retry after 60s

    public function __construct(
        private readonly int $modelId
    ) {}

    public function handle(): void
    {
        $model = Something::findOrFail($this->modelId);
        // długotrwała operacja
    }

    public function failed(\Throwable $exception): void
    {
        Log::error("ProcessSomethingJob failed for ID {$this->modelId}", [
            'error' => $exception->getMessage()
        ]);
    }
}
```

Wywołanie: `ProcessSomethingJob::dispatch($model->id)->onQueue('default');`

---

## Wzorzec Broadcasting (Reverb)

```php
// Event
class SomethingUpdated implements ShouldBroadcast
{
    use Dispatchable;

    public function __construct(public readonly Something $something) {}

    public function broadcastOn(): Channel
    {
        return new PrivateChannel("business.{$this->something->business_id}");
    }

    public function broadcastAs(): string { return 'something.updated'; }
}
```

```tsx
// Frontend hook
useEffect(() => {
  const channel = Echo.private(`business.${businessId}`)
    .listen('.something.updated', (e: SomethingUpdatedEvent) => {
      setSomething(e.something);
    });
  return () => Echo.leave(`business.${businessId}`);
}, [businessId]);
```

---

## Uruchamianie środowiska dev

```bash
npm run dev:all   # wszystko naraz (Vite + queue + Reverb + tail logs)
# lub manualnie:
php artisan serve
npm run dev
php artisan queue:work
php artisan reverb:start
php artisan pail   # logi w real-time
```

---

## Instalacja fizycznych Git hooks

```bash
# pre-commit hook — uruchom raz po sklonowaniu projektu
cat > .git/hooks/pre-commit << 'EOF'
#!/bin/bash
./vendor/bin/pint --test && \
npm run lint && \
php ./.github/scripts/validate-multi-tenancy.php
EOF
chmod +x .git/hooks/pre-commit
```

---

## Checklist dla nowych Jobs

- [ ] `implements ShouldQueue` + trait `Queueable`
- [ ] `$tries` i `$backoff` ustawione
- [ ] `failed()` metoda z logowaniem
- [ ] Test z `Queue::fake()` lub `Bus::fake()`
- [ ] ID zamiast całego modelu w konstruktorze (serialization)
