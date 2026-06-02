# Skill: Laravel Action Implementation

**Opis:** Tworzenie czystej, testowalnej logiki biznesowej wzorcem Action.

**Kiedy używać:** Każda operacja biznesowa (Create*, Update*, Delete*, Generate*, Send*, Process*).

---

## Wzorzec pełny (z DTO i Events)

```php
<?php

namespace App\Actions\{Domain};

use App\DataTransferObjects\{Domain}\{Model}Data;
use App\Events\{Domain}\{Model}Created;
use App\Models\{Model};
use Illuminate\Support\Facades\DB;

final class Create{Model}Action
{
    public function execute({Model}Data $data): {Model}
    {
        return DB::transaction(function () use ($data) {
            $model = {Model}::create($data->toArray());
            event(new {Model}Created($model));
            return $model;
        });
    }
}
```

---

## Wzorzec DTO

```php
<?php

namespace App\DataTransferObjects\{Domain};

use Illuminate\Http\Request;

final class {Model}Data
{
    public function __construct(
        public readonly int $businessId,
        public readonly string $name,
        public readonly ?string $description = null,
        public readonly string $status = 'active',
    ) {}

    public static function fromRequest(Request $request): self
    {
        return new self(
            businessId: $request->user()->business_id,
            name: $request->validated('name'),
            description: $request->validated('description'),
            status: $request->validated('status', 'active'),
        );
    }

    public function toArray(): array
    {
        return [
            'business_id' => $this->businessId,
            'name'        => $this->name,
            'description' => $this->description,
            'status'      => $this->status,
        ];
    }
}
```

---

## Wzorzec Event

```php
<?php

namespace App\Events\{Domain};

use App\Models\{Model};
use Illuminate\Foundation\Events\Dispatchable;

class {Model}Created
{
    use Dispatchable;

    public function __construct(public readonly {Model} $model) {}
}
```

---

## Wzorzec Form Request

```php
<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class Store{Model}Request extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', {Model}::class);
    }

    public function rules(): array
    {
        return [
            'name'        => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'status'      => ['sometimes', 'in:active,inactive'],
        ];
    }
}
```

---

## Wzorzec Controller (wywołanie Action)

```php
public function store(Store{Model}Request $request, Create{Model}Action $action): JsonResponse
{
    $model = $action->execute({Model}Data::fromRequest($request));
    return response()->json(['data' => new {Model}Resource($model)], 201);
}
```

---

## Checklist po stworzeniu Action

- [ ] Konstruktor z dependency injection (nie `new`)
- [ ] `execute()` z typowanym parametrem
- [ ] Transakcja DB dla operacji wieloetapowych
- [ ] Event dispatchowany po sukcesie
- [ ] Test → skill: test-generation
- [ ] Rejestracja w `AppServiceProvider` (jeśli potrzebna)
