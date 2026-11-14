<?php

namespace Tests\Feature\Filament;

use App\Filament\Resources\BankAccountResource;
use App\Filament\Resources\BankTransactionResource;
use App\Filament\Resources\PaymentResource;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BankingUiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->artisan('db:seed', ['--class' => 'AdminSeeder']);
        Filament::setCurrentPanel('admin');
        app()->setLocale('en');
    }

    public function test_banking_lists_render_context_and_actionable_empty_states(): void
    {
        $admin = User::query()->where('email', '20noname22x@gmail.com')->firstOrFail();
        $this->actingAs($admin);

        $resources = [
            BankAccountResource::class => 'accounts',
            BankTransactionResource::class => 'transactions',
            PaymentResource::class => 'payments',
        ];

        foreach ($resources as $resource => $translationKey) {
            $this->get($resource::getUrl('index'))
                ->assertOk()
                ->assertSeeText(__("admin.migration.banking.{$translationKey}.list_subheading"))
                ->assertSeeText(__("admin.migration.banking.{$translationKey}.empty_heading"));
        }
    }
}
