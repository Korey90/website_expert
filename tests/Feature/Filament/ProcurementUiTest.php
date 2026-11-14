<?php

namespace Tests\Feature\Filament;

use App\Filament\Resources\ExpenseResource;
use App\Filament\Resources\PurchaseCreditNoteResource;
use App\Filament\Resources\RecurringExpenseTemplateResource;
use App\Filament\Resources\SupplierResource;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProcurementUiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->artisan('db:seed', ['--class' => 'AdminSeeder']);
        Filament::setCurrentPanel('admin');
        app()->setLocale('en');
    }

    public function test_procurement_lists_render_context_and_actionable_empty_states(): void
    {
        $admin = User::query()->where('email', '20noname22x@gmail.com')->firstOrFail();
        $this->actingAs($admin);

        $resources = [
            ExpenseResource::class => 'expenses',
            SupplierResource::class => 'suppliers',
            RecurringExpenseTemplateResource::class => 'recurring_expenses',
            PurchaseCreditNoteResource::class => 'purchase_credit_notes',
        ];

        foreach ($resources as $resource => $translationKey) {
            $this->get($resource::getUrl('index'))
                ->assertOk()
                ->assertSeeText(__("admin.migration.procurement.{$translationKey}.list_subheading"))
                ->assertSeeText(__("admin.migration.procurement.{$translationKey}.empty_heading"));
        }
    }
}
