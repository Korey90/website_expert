# Skill: Test Generation

**Opis:** Generowanie testów PHPUnit i Vitest dla nowych funkcji.

**Kiedy używać:** Po każdej nowej Action, Controller endpoint, lub krytycznym komponencie React.

---

## PHPUnit — Feature Test (szablon)

```php
<?php

namespace Tests\Feature\{Domain};

use App\Models\{Model};
use App\Models\Business;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class {Model}Test extends TestCase
{
    use RefreshDatabase;

    private User $adminUser;
    private User $agentUser;
    private Business $business;

    protected function setUp(): void
    {
        parent::setUp();
        $this->business = Business::factory()->create();
        $this->adminUser = User::factory()
            ->for($this->business)
            ->withRole('admin')
            ->create();
        $this->agentUser = User::factory()
            ->for($this->business)
            ->withRole('agent')
            ->create();
    }

    // --- Happy paths ---

    public function test_admin_can_create_{model}(): void
    {
        $this->actingAs($this->adminUser)
            ->postJson(route('{domain}.store'), [
                'name' => 'Test {Model}',
                'status' => 'active',
            ])
            ->assertCreated()
            ->assertJsonPath('data.name', 'Test {Model}');

        $this->assertDatabaseHas('{models}', [
            'business_id' => $this->business->id,
            'name' => 'Test {Model}',
        ]);
    }

    // --- Authorization ---

    public function test_unauthenticated_user_cannot_access_{model}(): void
    {
        $this->getJson(route('{domain}.index'))
            ->assertUnauthorized();
    }

    public function test_agent_cannot_create_{model}(): void
    {
        $this->actingAs($this->agentUser)
            ->postJson(route('{domain}.store'), ['name' => 'Test'])
            ->assertForbidden();
    }

    // --- Multi-tenancy isolation ---

    public function test_user_cannot_see_other_business_{model}(): void
    {
        $otherBusiness = Business::factory()->create();
        $otherItem = {Model}::factory()->for($otherBusiness)->create();

        $this->actingAs($this->adminUser)
            ->getJson(route('{domain}.show', $otherItem))
            ->assertForbidden();
    }

    // --- Validation ---

    public function test_create_{model}_validates_required_fields(): void
    {
        $this->actingAs($this->adminUser)
            ->postJson(route('{domain}.store'), [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['name']);
    }
}
```

---

## Vitest — Component Test (szablon)

```tsx
// tests/js/{Domain}/{Component}.test.tsx
import { render, screen, fireEvent, waitFor } from '@testing-library/react';
import { describe, it, expect, vi } from 'vitest';
import { {Component} } from '@/Components/{Domain}/{Component}';

describe('{Component}', () => {
  it('renders correctly', () => {
    render(<{Component} title="Test" status="active" />);
    expect(screen.getByText('Test')).toBeInTheDocument();
  });

  it('shows loading state during submit', async () => {
    const onSubmit = vi.fn();
    render(<{Component} onSubmit={onSubmit} />);
    
    fireEvent.click(screen.getByRole('button', { name: /save/i }));
    
    await waitFor(() => {
      expect(screen.getByRole('button')).toBeDisabled();
    });
  });

  it('displays validation errors', () => {
    render(<{Component} errors={{ name: 'Required' }} />);
    expect(screen.getByText('Required')).toBeInTheDocument();
  });
});
```

---

## Uruchamianie testów

```bash
# Wszystkie
php artisan test

# Jeden plik
php artisan test --filter={Model}Test

# Vitest
npm run test
npm run test -- {Component}
npm run test -- --coverage
```

---

## Checklist testów

- [ ] Happy path (sukces)
- [ ] Walidacja (422 z błędami)
- [ ] Autoryzacja (403 bez uprawnień)
- [ ] Multi-tenancy (dane innego business → 403/404)
- [ ] Brak dostępu bez auth (401)
- [ ] Factory dla nowych modeli istnieje
