<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        // Core: roles, permissions, admin user, pipeline stages, project templates
        $this->call(AdminSeeder::class);

        // System settings (tracking, integrations)
        $this->call(SettingSeeder::class);

        // Legal & compliance content
        $this->call(LegalSettingsSeeder::class);
        $this->call(PageSeeder::class);

        // Website front-end content
        $this->call(SiteSectionSeeder::class);
        $this->call(CalculatorPricingSeeder::class);
        $this->call(CalculatorStringsSeeder::class);
        $this->call(CalculatorStepsSeeder::class);

        // Templates required by automations, contracts, and notifications
        $this->call(EmailTemplateSeeder::class);
        $this->call(SmsTemplateSeeder::class);
        $this->call(ContractTemplateSeeder::class);

        // ── Demo / sample data (comment out for production) ──────────────────
        // $this->call(UserSeeder::class);
        // $this->call(ClientSeeder::class);
        // $this->call(LeadSeeder::class);
        // $this->call(ProjectSeeder::class);
        // $this->call(QuoteSeeder::class);
        // $this->call(InvoiceSeeder::class);
        // $this->call(AutomationRuleSeeder::class);
    }
}
