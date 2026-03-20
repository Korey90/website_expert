<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

class SettingSeeder extends Seeder
{
    public function run(): void
    {
        $defaults = [
            // GTM
            ['key' => 'gtm_enabled',   'value' => '0',                          'group' => 'tracking'],
            ['key' => 'gtm_id',        'value' => env('GTM_ID', ''),            'group' => 'tracking'],
            // GA4
            ['key' => 'ga4_enabled',   'value' => '0',                          'group' => 'tracking'],
            ['key' => 'ga4_id',        'value' => env('GA4_ID', ''),            'group' => 'tracking'],
            // Meta Pixel
            ['key' => 'pixel_enabled', 'value' => '0',                          'group' => 'tracking'],
            ['key' => 'pixel_id',      'value' => env('META_PIXEL_ID', ''),     'group' => 'tracking'],
            // Google Ads
            ['key' => 'gads_enabled',  'value' => '0',                          'group' => 'tracking'],
            ['key' => 'gads_id',       'value' => env('GOOGLE_ADS_ID', ''),     'group' => 'tracking'],
            // Cookie Consent
            ['key' => 'cookie_consent_enabled', 'value' => '1',                 'group' => 'tracking'],
            ['key' => 'cookiebot_id',           'value' => '',                  'group' => 'tracking'],
        ];

        foreach ($defaults as $row) {
            Setting::firstOrCreate(['key' => $row['key']], $row);
        }
    }
}
