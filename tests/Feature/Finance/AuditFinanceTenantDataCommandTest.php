<?php

namespace Tests\Feature\Finance;

use App\Models\Business;
use App\Models\Client;
use App\Models\Contact;
use App\Models\Expense;
use App\Models\Supplier;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuditFinanceTenantDataCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_audit_passes_when_finance_tenant_data_is_consistent(): void
    {
        $business = Business::create(['name' => 'Agency', 'slug' => 'agency', 'is_active' => true]);
        $client = Client::create([
            'business_id' => $business->id,
            'company_name' => 'Client Ltd',
            'status' => 'active',
        ]);

        Contact::create([
            'business_id' => $business->id,
            'client_id' => $client->id,
            'first_name' => 'Jamie',
            'last_name' => 'Jones',
        ]);

        $this->artisan('finance:audit-tenants')
            ->expectsOutputToContain('Finance tenant audit passed.')
            ->assertExitCode(0);
    }

    public function test_audit_fails_and_reports_an_unassigned_record(): void
    {
        $business = Business::create(['name' => 'Agency', 'slug' => 'agency', 'is_active' => true]);
        $client = Client::create([
            'business_id' => $business->id,
            'company_name' => 'Client Ltd',
            'status' => 'active',
        ]);

        Contact::create([
            'business_id' => null,
            'client_id' => $client->id,
            'first_name' => 'Jamie',
            'last_name' => 'Jones',
        ]);

        $this->artisan('finance:audit-tenants')
            ->expectsOutputToContain('Finance tenant audit failed: 1 invalid record(s).')
            ->assertExitCode(1);
    }

    public function test_supplierless_expense_is_valid_but_cross_tenant_supplier_is_reported(): void
    {
        $business = Business::create(['name' => 'Agency', 'slug' => 'expense-audit', 'is_active' => true]);
        $otherBusiness = Business::create(['name' => 'Other', 'slug' => 'expense-audit-other', 'is_active' => true]);
        $otherSupplier = Supplier::factory()->for($otherBusiness)->create();

        Expense::withoutGlobalScopes()->create([
            'business_id' => $business->id,
            'supplier_id' => null,
            'status' => Expense::STATUS_DRAFT,
            'currency' => 'GBP',
            'issue_date' => '2026-11-01',
            'tax_point' => '2026-11-01',
        ]);

        $this->artisan('finance:audit-tenants')
            ->expectsOutputToContain('Finance tenant audit passed.')
            ->assertExitCode(0);

        Expense::withoutGlobalScopes()->create([
            'business_id' => $business->id,
            'supplier_id' => $otherSupplier->id,
            'status' => Expense::STATUS_DRAFT,
            'currency' => 'GBP',
            'issue_date' => '2026-11-01',
            'tax_point' => '2026-11-01',
        ]);

        $this->artisan('finance:audit-tenants')
            ->expectsOutputToContain('Finance tenant audit failed: 1 invalid record(s).')
            ->assertExitCode(1);
    }
}
