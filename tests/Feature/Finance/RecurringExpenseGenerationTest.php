<?php

namespace Tests\Feature\Finance;

use App\Actions\Finance\CreateRecurringExpenseTemplateAction;
use App\Actions\Finance\GenerateDueRecurringExpensesAction;
use App\Actions\Finance\InitializeAccountingSetupAction;
use App\Actions\Finance\UpdateRecurringExpenseTemplateAction;
use App\Filament\Resources\RecurringExpenseTemplateResource;
use App\Models\Business;
use App\Models\BusinessUser;
use App\Models\Expense;
use App\Models\RecurringExpenseTemplate;
use App\Models\Supplier;
use App\Models\TaxCode;
use App\Models\User;
use Carbon\CarbonImmutable;
use DomainException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class RecurringExpenseGenerationTest extends TestCase
{
    use RefreshDatabase;

    private Business $business;

    private User $user;

    private Supplier $supplier;

    protected function setUp(): void
    {
        parent::setUp();

        $this->business = Business::create(['name' => 'Recurring Ltd', 'slug' => 'recurring', 'is_active' => true]);
        $this->user = User::factory()->create();
        $this->addMember($this->business, $this->user);
        $this->supplier = Supplier::factory()->for($this->business)->create(['payment_terms_days' => 14]);
        app(InitializeAccountingSetupAction::class)->execute($this->business, $this->user);
    }

    public function test_due_monthly_occurrences_generate_draft_expenses_and_preserve_month_end_anchor(): void
    {
        $template = $this->createTemplate([
            'start_date' => '2026-01-31',
            'next_run_date' => '2026-01-31',
        ]);

        $result = app(GenerateDueRecurringExpensesAction::class)->execute(CarbonImmutable::parse('2026-03-31'));
        $expenses = Expense::withoutGlobalScopes()
            ->where('recurring_expense_template_id', $template->id)
            ->orderBy('recurrence_date')
            ->get();

        $this->assertSame([], $result['failures']);
        $this->assertCount(3, $result['generated']);
        $this->assertSame(['2026-01-31', '2026-02-28', '2026-03-31'], $expenses->pluck('recurrence_date')->map->toDateString()->all());
        $this->assertSame([Expense::STATUS_DRAFT], $expenses->pluck('status')->unique()->values()->all());
        $this->assertSame(['120.00'], $expenses->pluck('total')->unique()->values()->all());
        $this->assertSame(['2026-02-14', '2026-03-14', '2026-04-14'], $expenses->pluck('due_date')->map->toDateString()->all());
        $this->assertSame('2026-04-30', $template->fresh()->next_run_date->toDateString());
        $this->assertNotNull($template->fresh()->last_generated_at);
    }

    public function test_generation_is_idempotent_when_the_same_date_is_processed_again(): void
    {
        $template = $this->createTemplate(['start_date' => '2026-11-01', 'next_run_date' => '2026-11-01']);
        $action = app(GenerateDueRecurringExpensesAction::class);

        $first = $action->execute(CarbonImmutable::parse('2026-11-01'));
        $second = $action->execute(CarbonImmutable::parse('2026-11-01'));

        $this->assertCount(1, $first['generated']);
        $this->assertCount(0, $second['generated']);
        $this->assertSame(1, Expense::withoutGlobalScopes()->where('recurring_expense_template_id', $template->id)->count());
    }

    public function test_paused_template_does_not_generate_an_expense(): void
    {
        $template = $this->createTemplate([
            'status' => RecurringExpenseTemplate::STATUS_PAUSED,
            'start_date' => '2026-11-01',
            'next_run_date' => '2026-11-01',
        ]);

        $result = app(GenerateDueRecurringExpensesAction::class)->execute(CarbonImmutable::parse('2026-11-30'));

        $this->assertCount(0, $result['generated']);
        $this->assertFalse(Expense::withoutGlobalScopes()->where('recurring_expense_template_id', $template->id)->exists());
    }

    public function test_template_completes_after_its_last_occurrence(): void
    {
        $template = $this->createTemplate([
            'frequency' => RecurringExpenseTemplate::FREQUENCY_WEEKLY,
            'start_date' => '2026-11-01',
            'next_run_date' => '2026-11-01',
            'end_date' => '2026-11-15',
        ]);

        $result = app(GenerateDueRecurringExpensesAction::class)->execute(CarbonImmutable::parse('2026-12-01'));

        $this->assertCount(3, $result['generated']);
        $this->assertSame(RecurringExpenseTemplate::STATUS_COMPLETED, $template->fresh()->status);
        $this->assertSame(3, $template->generatedExpenses()->count());
    }

    public function test_an_active_member_is_used_if_the_template_creator_is_no_longer_active(): void
    {
        $template = $this->createTemplate(['start_date' => '2026-11-01', 'next_run_date' => '2026-11-01']);
        $replacement = User::factory()->create();
        $this->addMember($this->business, $replacement);
        BusinessUser::where('business_id', $this->business->id)
            ->where('user_id', $this->user->id)
            ->update(['is_active' => false]);

        app(GenerateDueRecurringExpensesAction::class)->execute(CarbonImmutable::parse('2026-11-01'));

        $this->assertSame($replacement->id, $template->generatedExpenses()->firstOrFail()->created_by);
    }

    public function test_cross_tenant_references_are_rejected_when_creating_a_template(): void
    {
        $otherBusiness = Business::create(['name' => 'Other Recurring Ltd', 'slug' => 'other-recurring', 'is_active' => true]);
        $otherSupplier = Supplier::factory()->for($otherBusiness)->create();

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('does not belong');

        $this->createTemplate(['supplier_id' => $otherSupplier->id]);
    }

    public function test_console_command_generates_due_drafts_and_validates_the_date_option(): void
    {
        $this->createTemplate(['start_date' => '2026-11-01', 'next_run_date' => '2026-11-01']);

        $this->artisan('finance:generate-recurring-expenses', ['--date' => '2026-11-01'])
            ->expectsOutput('Generated 1 recurring expense draft(s).')
            ->assertSuccessful();
        $this->artisan('finance:generate-recurring-expenses', ['--date' => 'not-a-date'])
            ->expectsOutput('The --date option must use YYYY-MM-DD.')
            ->assertExitCode(2);
    }

    public function test_template_can_be_paused_and_edited_without_changing_generated_drafts(): void
    {
        $template = $this->createTemplate();
        app(GenerateDueRecurringExpensesAction::class)->execute(CarbonImmutable::parse('2026-11-01'));

        $updated = app(UpdateRecurringExpenseTemplateAction::class)->execute($template, $this->user, [
            'name' => 'Paused software subscription',
            'status' => RecurringExpenseTemplate::STATUS_PAUSED,
            'next_run_date' => '2026-12-01',
            'items' => [[
                'description' => 'Updated future subscription',
                'quantity' => 1,
                'unit_price' => 125,
                'tax_code_id' => TaxCode::withoutGlobalScopes()
                    ->where('business_id', $this->business->id)
                    ->where('code', 'S20')
                    ->value('id'),
            ]],
        ]);

        $this->assertSame(RecurringExpenseTemplate::STATUS_PAUSED, $updated->status);
        $this->assertSame('Updated future subscription', $updated->items->first()->description);
        $this->assertSame('120.00', $updated->generatedExpenses()->firstOrFail()->total);
    }

    public function test_authorized_user_can_open_recurring_expense_resource_pages(): void
    {
        $this->user->update(['is_active' => true]);
        foreach (['access_admin_panel', 'view_finance', 'manage_finance'] as $permission) {
            Permission::findOrCreate($permission, 'web');
        }
        $this->user->givePermissionTo(['access_admin_panel', 'view_finance', 'manage_finance']);
        $template = $this->createTemplate();

        $this->actingAs($this->user)
            ->get(RecurringExpenseTemplateResource::getUrl('index'))
            ->assertOk();
        $this->get(RecurringExpenseTemplateResource::getUrl('create'))->assertOk();
        $this->get(RecurringExpenseTemplateResource::getUrl('edit', ['record' => $template]))->assertOk();
    }

    /** @param array<string, mixed> $overrides */
    private function createTemplate(array $overrides = []): RecurringExpenseTemplate
    {
        return app(CreateRecurringExpenseTemplateAction::class)->execute($this->business, $this->user, [
            'name' => 'Monthly software subscription',
            'supplier_id' => $this->supplier->id,
            'status' => RecurringExpenseTemplate::STATUS_ACTIVE,
            'frequency' => RecurringExpenseTemplate::FREQUENCY_MONTHLY,
            'interval' => 1,
            'start_date' => '2026-11-01',
            'next_run_date' => '2026-11-01',
            'due_days' => 14,
            'business_purpose' => 'Software required for business operations.',
            'items' => [[
                'description' => 'Software subscription',
                'quantity' => 1,
                'unit_price' => 100,
                'tax_code_id' => TaxCode::withoutGlobalScopes()
                    ->where('business_id', $this->business->id)
                    ->where('code', 'S20')
                    ->value('id'),
            ]],
            ...$overrides,
        ]);
    }

    private function addMember(Business $business, User $user): void
    {
        BusinessUser::create([
            'business_id' => $business->id,
            'user_id' => $user->id,
            'role' => 'owner',
            'is_active' => true,
            'joined_at' => now(),
        ]);
    }
}
