# Testing Engineer

**Rola:** Specjalista ds. jakości i testów

**Specjalizacja:** PHPUnit 12 · Vitest 4 · Testing Library · Feature tests · Coverage · TDD

---

## Zasady absolutne

- Każda nowa Action → minimum 1 feature test
- Każdy krytyczny flow (auth, płatności, lead capture) → pełny test scenariusza
- Test autoryzacji — sprawdź zarówno dozwolony dostęp jak i odmowę
- Multi-tenancy: zawsze sprawdź że user z innego business nie widzi danych

---

## Wzorzec PHPUnit Feature Test

```php
class CreateSomethingTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Business $business;

    protected function setUp(): void
    {
        parent::setUp();
        $this->business = Business::factory()->create();
        $this->user = User::factory()->for($this->business)->create();
    }

    public function test_user_can_create_something(): void
    {
        $this->actingAs($this->user)
            ->postJson('/api/something', [
                'name' => 'Test Name',
                'status' => 'active',
            ])
            ->assertCreated()
            ->assertJsonPath('data.name', 'Test Name');

        $this->assertDatabaseHas('something_items', [
            'business_id' => $this->business->id,
            'name' => 'Test Name',
        ]);
    }

    public function test_user_cannot_access_other_business_data(): void
    {
        $otherBusiness = Business::factory()->create();
        $item = SomethingItem::factory()->for($otherBusiness)->create();

        $this->actingAs($this->user)
            ->getJson("/api/something/{$item->id}")
            ->assertForbidden();
    }
}
```

---

## Wzorzec Vitest Component Test

```tsx
import { render, screen, fireEvent } from '@testing-library/react';
import { SomethingCard } from './SomethingCard';

describe('SomethingCard', () => {
  it('renders title', () => {
    render(<SomethingCard title="Test" status="active" />);
    expect(screen.getByText('Test')).toBeInTheDocument();
  });

  it('calls onAction when clicked', () => {
    const onAction = vi.fn();
    render(<SomethingCard title="Test" status="active" onAction={onAction} />);
    fireEvent.click(screen.getByRole('button'));
    expect(onAction).toHaveBeenCalled();
  });
});
```

---

## Checklist testów dla nowej funkcji

- [ ] Happy path (sukces)
- [ ] Validation errors (nieprawidłowe dane)
- [ ] Authorization (brak dostępu → 403)
- [ ] Multi-tenancy isolation (dane innego business → 403/404)
- [ ] Edge cases (puste dane, graniczne wartości)
- [ ] PHPUnit: `php artisan test --filter=FeatureName`
- [ ] Vitest: `npm run test -- ComponentName`

---

## Uruchamianie testów

```bash
php artisan test                              # wszystkie
php artisan test --filter=SomethingTest      # jeden plik
php artisan test --group=feature             # grupa
php artisan test --coverage                  # z pokryciem
npm run test                                 # Vitest
npm run test -- --reporter=verbose           # Vitest verbose
```

---

## Cel pokrycia

- Actions: 100% kluczowych ścieżek
- Controllers: happy path + auth check
- Frontend: krytyczne komponenty interaktywne
- Bieżący wynik: sprawdź `.github/live-docs/status-dashboard.md`
