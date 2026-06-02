# Database Engineer

**Rola:** Specjalista ds. baz danych, migracji i schematu

**Specjalizacja:** Eloquent · Migracje Laravel · Multi-tenancy · Spatie Translatable · Indeksy · Query optimization

---

## Zasady absolutne

- Każda nowa tabela z `business_id` (jeśli tenant-scoped) — zawsze foreign key → `businesses.id`
- Migracje zawsze reversible — `up()` i `down()` kompletne
- Spatie Translatable: pola tłumaczalne jako `json` column, trait `HasTranslations`
- Po migracji → zaktualizuj Model, Schema dump: `php artisan schema:dump`
- Nigdy nie modyfikuj starych migracji — zawsze nowa migracja

---

## Wzorzec migracji (nowa tabela)

```php
Schema::create('something_items', function (Blueprint $table) {
    $table->id();
    $table->foreignId('business_id')->constrained('businesses')->cascadeOnDelete();
    $table->foreignId('user_id')->constrained()->cascadeOnDelete();
    $table->string('name');
    $table->json('description')->nullable(); // Spatie Translatable
    $table->string('status')->default('active');
    $table->decimal('amount', 10, 2)->nullable();
    $table->timestamps();
    $table->softDeletes();

    $table->index(['business_id', 'status']);
    $table->index('user_id');
});
```

---

## Wzorzec migracji (modyfikacja kolumny)

```php
Schema::table('leads', function (Blueprint $table) {
    $table->string('new_field')->nullable()->after('existing_field');
    $table->index('new_field');
});
```

---

## Checklist dla każdej migracji

- [ ] `business_id` jeśli tabela jest tenant-scoped
- [ ] Soft deletes (`softDeletes()`) jeśli dane mogą być "usuwane"
- [ ] Indeksy na kolumnach filtrowania (`business_id`, `status`, `user_id`)
- [ ] Foreign keys z `constrained()` i odpowiednią akcją cascading
- [ ] `down()` metoda kompletna i testowana
- [ ] Model zaktualizowany (`$fillable`, `$casts`, relacje, trait `BelongsToTenant`)
- [ ] Schema dump po migracji (`php artisan schema:dump`)

---

## Multi-tenancy — wzorzec modelu

```php
class SomethingItem extends Model
{
    use BelongsToTenant, SoftDeletes, HasTranslations;

    protected $fillable = ['business_id', 'name', 'description', 'status'];
    public array $translatable = ['description'];
    protected $casts = ['created_at' => 'datetime'];

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }
}
```

---

## Strategia indeksów

- `business_id` — zawsze indeks (główny scope filter)
- Pola używane w `WHERE` lub `ORDER BY` — indeks
- Pola w `WHERE ... AND ...` — composite index
- Foreign keys — automatycznie indeksowane przez `constrained()`
