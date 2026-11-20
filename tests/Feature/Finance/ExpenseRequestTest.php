<?php

namespace Tests\Feature\Finance;

use App\Actions\Finance\CreateExpenseAction;
use App\Actions\Finance\InitializeAccountingSetupAction;
use App\Http\Requests\Finance\StoreExpenseRequest;
use App\Http\Requests\Finance\UpdateExpenseRequest;
use App\Models\Business;
use App\Models\BusinessUser;
use App\Models\Expense;
use App\Models\Supplier;
use App\Models\TaxCode;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class ExpenseRequestTest extends TestCase
{
    use RefreshDatabase;

    private Business $business;

    private User $user;

    private Supplier $supplier;

    private TaxCode $taxCode;

    protected function setUp(): void
    {
        parent::setUp();

        $this->business = Business::create(['name' => 'Validation Ltd', 'slug' => 'expense-validation', 'is_active' => true]);
        $this->user = User::factory()->create(['is_active' => true]);
        BusinessUser::create([
            'business_id' => $this->business->id,
            'user_id' => $this->user->id,
            'role' => 'owner',
            'is_active' => true,
            'joined_at' => now(),
        ]);
        Permission::findOrCreate('manage_finance', 'web');
        $this->user->givePermissionTo('manage_finance');
        $this->supplier = Supplier::factory()->for($this->business)->create();
        app(InitializeAccountingSetupAction::class)->execute($this->business, $this->user);
        $this->taxCode = $this->taxCodeFor($this->business);
        $this->actingAs($this->user);
    }

    public function test_valid_expense_payload_is_authorized_and_passes_validation(): void
    {
        $request = $this->request(StoreExpenseRequest::class, $this->validPayload());
        $validator = Validator::make($request->all(), $request->rules());
        $request->withValidator($validator);

        $this->assertTrue($request->authorize());
        $this->assertTrue($validator->passes(), json_encode($validator->errors()->toArray()));
    }

    public function test_unit_price_accepts_ten_decimal_places_but_rejects_more(): void
    {
        $payload = $this->validPayload();
        $payload['items'][0]['unit_price'] = '0.0333333333';
        $request = $this->request(StoreExpenseRequest::class, $payload);
        $validator = Validator::make($request->all(), $request->rules());

        $this->assertTrue($validator->passes(), json_encode($validator->errors()->toArray()));

        $payload['items'][0]['unit_price'] = '0.03333333333';
        $request = $this->request(StoreExpenseRequest::class, $payload);
        $validator = Validator::make($request->all(), $request->rules());

        $this->assertFalse($validator->passes());
        $this->assertArrayHasKey('items.0.unit_price', $validator->errors()->toArray());
    }

    public function test_duplicate_supplier_invoice_number_is_rejected_for_the_same_supplier(): void
    {
        $expense = $this->createExpense();
        $request = $this->request(StoreExpenseRequest::class, $this->validPayload());
        $validator = Validator::make($request->all(), $request->rules());

        $this->assertFalse($validator->passes());
        $this->assertArrayHasKey('supplier_invoice_number', $validator->errors()->toArray());
        $this->assertDatabaseHas('expenses', ['id' => $expense->id]);
    }

    public function test_update_request_ignores_the_current_expense_invoice_number(): void
    {
        $expense = $this->createExpense();
        $request = $this->request(UpdateExpenseRequest::class, $this->validPayload(), $expense);
        $validator = Validator::make($request->all(), $request->rules());

        $this->assertTrue($request->authorize());
        $this->assertTrue($validator->passes(), json_encode($validator->errors()->toArray()));
    }

    public function test_foreign_currency_requires_a_complete_positive_exchange_rate_snapshot(): void
    {
        $request = $this->request(StoreExpenseRequest::class, [
            ...$this->validPayload(),
            'currency' => 'EUR',
            'gbp_exchange_rate' => null,
            'exchange_rate_source' => null,
            'exchange_rate_date' => null,
        ]);
        $validator = Validator::make($request->all(), $request->rules());

        $this->assertFalse($validator->passes());
        $this->assertArrayHasKey('gbp_exchange_rate', $validator->errors()->toArray());
        $this->assertArrayHasKey('exchange_rate_source', $validator->errors()->toArray());
        $this->assertArrayHasKey('exchange_rate_date', $validator->errors()->toArray());
    }

    public function test_cross_tenant_supplier_and_tax_code_are_rejected(): void
    {
        $otherBusiness = Business::create(['name' => 'Other Ltd', 'slug' => 'other-validation', 'is_active' => true]);
        $otherSupplier = Supplier::factory()->for($otherBusiness)->create();
        app(InitializeAccountingSetupAction::class)->execute($otherBusiness);
        $otherTaxCode = $this->taxCodeFor($otherBusiness);
        $payload = $this->validPayload();
        $payload['supplier_id'] = $otherSupplier->id;
        $payload['items'][0]['tax_code_id'] = $otherTaxCode->id;
        $request = $this->request(StoreExpenseRequest::class, $payload);
        $validator = Validator::make($request->all(), $request->rules());

        $this->assertFalse($validator->passes());
        $this->assertArrayHasKey('supplier_id', $validator->errors()->toArray());
        $this->assertArrayHasKey('items.0.tax_code_id', $validator->errors()->toArray());
    }

    public function test_billable_item_requires_a_tenant_client(): void
    {
        $payload = $this->validPayload();
        $payload['items'][0]['billable'] = true;
        $request = $this->request(StoreExpenseRequest::class, $payload);
        $validator = Validator::make($request->all(), $request->rules());
        $request->withValidator($validator);

        $this->assertFalse($validator->passes());
        $this->assertArrayHasKey('items.0.client_id', $validator->errors()->toArray());
    }

    /**
     * @param  class-string<FormRequest>  $class
     * @param  array<string, mixed>  $data
     */
    private function request(string $class, array $data, ?Expense $expense = null): FormRequest
    {
        /** @var FormRequest $request */
        $request = $class::create('/expenses', $expense ? 'PATCH' : 'POST', $data);
        $request->setUserResolver(fn (): User => $this->user);

        if ($expense) {
            $request->setRouteResolver(fn () => new class($expense)
            {
                public function __construct(private readonly Expense $expense) {}

                public function parameter(string $key, mixed $default = null): mixed
                {
                    return $key === 'expense' ? $this->expense : $default;
                }
            });
        }

        return $request;
    }

    /** @return array<string, mixed> */
    private function validPayload(): array
    {
        return [
            'supplier_id' => $this->supplier->id,
            'supplier_invoice_number' => 'SUP-INV-001',
            'currency' => 'GBP',
            'issue_date' => '2026-11-01',
            'tax_point' => '2026-11-01',
            'due_date' => '2026-11-30',
            'business_purpose' => 'Hosting infrastructure',
            'items' => [[
                'description' => 'Cloud hosting',
                'quantity' => 1,
                'unit_price' => 100,
                'tax_code_id' => $this->taxCode->id,
                'billable' => false,
            ]],
        ];
    }

    private function createExpense(): Expense
    {
        return app(CreateExpenseAction::class)->execute($this->business, $this->user, $this->validPayload());
    }

    private function taxCodeFor(Business $business): TaxCode
    {
        return TaxCode::withoutGlobalScopes()
            ->where('business_id', $business->id)
            ->where('code', 'S20')
            ->firstOrFail();
    }
}
