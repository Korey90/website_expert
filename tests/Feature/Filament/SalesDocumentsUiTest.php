<?php

namespace Tests\Feature\Filament;

use App\Filament\Resources\ContractResource;
use App\Filament\Resources\CreditNoteResource;
use App\Filament\Resources\InvoiceResource;
use App\Filament\Resources\QuoteResource;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SalesDocumentsUiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->artisan('db:seed', ['--class' => 'AdminSeeder']);
        Filament::setCurrentPanel('admin');
        app()->setLocale('en');
    }

    public function test_sales_document_lists_render_context_and_actionable_empty_states(): void
    {
        $admin = User::query()->where('email', '20noname22x@gmail.com')->firstOrFail();
        $this->actingAs($admin);

        $resources = [
            InvoiceResource::class => 'invoices',
            QuoteResource::class => 'quotes',
            ContractResource::class => 'contracts',
            CreditNoteResource::class => 'credit_notes',
        ];

        foreach ($resources as $resource => $translationKey) {
            $this->get($resource::getUrl('index'))
                ->assertOk()
                ->assertSeeText(__("admin.migration.sales_documents.{$translationKey}.list_subheading"))
                ->assertSeeText(__("admin.migration.sales_documents.{$translationKey}.empty_heading"));
        }
    }
}
