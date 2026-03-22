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

        // Users: manager and developers
        $this->call(UserSeeder::class);

        // CRM data (order matters — clients before leads/projects)
        $this->call(ClientSeeder::class);
        $this->call(LeadSeeder::class);
        $this->call(ProjectSeeder::class);

        // Finance
        $this->call(QuoteSeeder::class);
        $this->call(InvoiceSeeder::class);

        // Content & settings
        $this->call(EmailTemplateSeeder::class);
        $this->call(SmsTemplateSeeder::class);
        $this->call(AutomationRuleSeeder::class);
        $this->call(CalculatorPricingSeeder::class);
        $this->call(PageSeeder::class);
        $this->call(SiteSectionSeeder::class);
    }
}
