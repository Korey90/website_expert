<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

class LegalSettingsSeeder extends Seeder
{
    public function run(): void
    {
        $defaults = [
            // Company Details
            'legal.company_name'    => 'WebsiteExpert Ltd',
            'legal.company_number'  => '[Companies House No. — update in Legal & Company settings]',
            'legal.company_address' => "[Registered Address Line 1]\n[City]\n[Postcode]",
            'legal.vat_number'      => 'GB [VAT Number]',
            'legal.company_email'   => 'hello@websiteexpert.co.uk',
            'legal.company_phone'   => '+44 [Phone Number]',

            // Data Protection
            'legal.ico_number'           => 'ZB[ICO Registration No.]',
            'legal.ico_registration_url' => 'https://ico.org.uk/ESDWebPages/Entry/ZB[ICO No.]',
            'legal.privacy_email'        => 'privacy@websiteexpert.co.uk',
            'legal.dpo_name'             => '',
            'legal.data_retention_years' => '7',

            // Customer Service
            'legal.complaints_email'   => 'support@websiteexpert.co.uk',
            'legal.complaints_phone'   => '+44 [Phone Number]',
            'legal.response_days'      => '14',
            'legal.deposit_percent'    => '50',
            'legal.payment_terms_days' => '30',

            // Cookie Policy
            'legal.cookie_policy_email' => 'privacy@websiteexpert.co.uk',

            // Document Dates & Versions
            'legal.privacy_effective_date'       => '',
            'legal.privacy_version'              => '1.0',
            'legal.terms_effective_date'         => '',
            'legal.terms_version'                => '1.0',
            'legal.cookies_effective_date'       => '',
            'legal.cookies_version'              => '1.0',
            'legal.accessibility_effective_date' => '',
            'legal.accessibility_version'        => '1.0',
        ];

        foreach ($defaults as $key => $value) {
            Setting::updateOrCreate(['key' => $key], ['value' => $value, 'group' => 'legal']);
        }
    }
}
