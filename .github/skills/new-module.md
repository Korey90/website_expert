# Skill: New Module (Scaffold nowego modułu)

**Opis:** Kompletny scaffold nowego modułu biznesowego od zera. Obejmuje backend, frontend i testy.

**Kiedy używać:** Nowa domena biznesowa (np. nowy typ encji: Tickets, Proposals, Contracts, itp.)

---

## Definicja modułu

Moduł składa się z:
- Model Eloquent z migracją
- Action classes (CRUD)
- Form Requests (walidacja)
- Policy (autoryzacja)
- API Controller
- Filament Resource (admin panel)
- React Pages (Inertia)
- React Components
- Tłumaczenia (pl + en + pt)
- Testy PHPUnit

---

## Kolejność scaffold (zawsze ta sama)

```
1. @DatabaseEngineer  → migracja + model + factory
2. @BackendEngineer   → Actions (Create, Update, Delete) + DTO + Events
3. @BackendEngineer   → Form Requests + Policy + Controller + Routes
4. @BackendEngineer   → Filament Resource
5. @FrontendEngineer  → React Pages (Index, Show, Create, Edit)
6. @FrontendEngineer  → React Components (Card, Form, Table)
7. @DocumentationEngineer → tłumaczenia pl + en + pt
8. @TestingEngineer   → PHPUnit Feature Tests
9. @SecurityEngineer  → przegląd Policy + endpoints
```

---

## Checklist — pliki do stworzenia

### Backend
- [ ] `database/migrations/xxxx_create_{models}_table.php`
- [ ] `app/Models/{Model}.php` (z BelongsToTenant, SoftDeletes)
- [ ] `database/factories/{Model}Factory.php`
- [ ] `app/DataTransferObjects/{Domain}/{Model}Data.php`
- [ ] `app/Actions/{Domain}/Create{Model}Action.php`
- [ ] `app/Actions/{Domain}/Update{Model}Action.php`
- [ ] `app/Actions/{Domain}/Delete{Model}Action.php`
- [ ] `app/Events/{Domain}/{Model}Created.php`
- [ ] `app/Http/Requests/Store{Model}Request.php`
- [ ] `app/Http/Requests/Update{Model}Request.php`
- [ ] `app/Policies/{Model}Policy.php`
- [ ] `app/Http/Controllers/{Domain}/{Model}Controller.php`
- [ ] `app/Http/Resources/{Model}Resource.php`
- [ ] `app/Filament/Resources/{Model}Resource.php`

### Frontend
- [ ] `resources/js/Pages/{Domain}/Index.tsx`
- [ ] `resources/js/Pages/{Domain}/Show.tsx`
- [ ] `resources/js/Pages/{Domain}/Create.tsx`
- [ ] `resources/js/Pages/{Domain}/Edit.tsx`
- [ ] `resources/js/Components/{Domain}/{Model}Card.tsx`
- [ ] `resources/js/Components/{Domain}/{Model}Form.tsx`
- [ ] `resources/js/Hooks/use{Model}.ts`

### Tłumaczenia
- [ ] `lang/pl/{domain}.php`
- [ ] `lang/en/{domain}.php`
- [ ] `lang/pt/{domain}.php`

### Testy
- [ ] `tests/Feature/{Domain}/{Model}Test.php`
- [ ] `tests/Feature/{Domain}/{Model}AuthorizationTest.php`

### Routing
- [ ] Dodaj trasy w `routes/api.php` lub `routes/web.php`
- [ ] Zarejestruj Policy w `app/Providers/AppServiceProvider.php`

---

## Wzorzec rejestracji Policy

```php
// app/Providers/AppServiceProvider.php
use App\Models\{Model};
use App\Policies\{Model}Policy;

Gate::policy({Model}::class, {Model}Policy::class);
```

---

## Wzorzec rejestracji tras

```php
// routes/api.php
Route::middleware('auth:sanctum')->prefix('{domain}')->name('{domain}.')->group(function () {
    Route::get('/', [{Model}Controller::class, 'index'])->name('index');
    Route::post('/', [{Model}Controller::class, 'store'])->name('store');
    Route::get('/{model}', [{Model}Controller::class, 'show'])->name('show');
    Route::put('/{model}', [{Model}Controller::class, 'update'])->name('update');
    Route::delete('/{model}', [{Model}Controller::class, 'destroy'])->name('destroy');
});
```
