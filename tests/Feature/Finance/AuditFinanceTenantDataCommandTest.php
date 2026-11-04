<?php

namespace Tests\Feature\Finance;

use App\Models\Business;
use App\Models\Client;
use App\Models\Contact;
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
}
