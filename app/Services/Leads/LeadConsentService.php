<?php

namespace App\Services\Leads;

use App\Models\Lead;
use App\Models\LeadConsent;
use App\Models\LeadSource;

class LeadConsentService
{
    /**
     * Record a GDPR consent entry for a lead.
     *
     * @param  Lead  $lead
     * @param  array{
     *     given: bool,
     *     consent_text: string,
     *     consent_version?: string,
     *     source_url?: string|null,
     *     ip_address?: string|null,
     *     locale?: string,
     * }  $data
     */
    public function record(Lead $lead, array $data): LeadConsent
    {
        $ipHash = hash('sha256', $data['ip_address'] ?? '');

        return LeadConsent::create([
            'lead_id'          => $lead->id,
            'given'            => $data['given'],
            'consent_text'     => $data['consent_text'],
            'consent_version'  => $data['consent_version'] ?? config('leads.consent_version', '1.0'),
            'collected_at'     => now(),
            'source_url'       => $data['source_url'] ?? null,
            'ip_hash'          => $ipHash,
            'locale'           => $data['locale'] ?? 'en',
        ]);
    }

    /**
     * Return the GDPR consent checkbox text for a given locale.
     * Reads from lang/{locale}/gdpr.php — falls back to 'en'.
     */
    public function getConsentTextForLocale(string $locale): string
    {
        $text = __('gdpr.consent_text', [], $locale);

        // If the translation key was not found, fall back to English
        if ($text === 'gdpr.consent_text') {
            $text = __('gdpr.consent_text', [], 'en');
        }

        // Final fallback
        if ($text === 'gdpr.consent_text') {
            $text = 'I agree to be contacted about this enquiry and accept the Privacy Policy.';
        }

        return $text;
    }

    /**
     * GDPR "Right to Erasure" — anonymise consent record while preserving the audit trail.
     */
    public function anonymizeForDeletion(Lead $lead): void
    {
        LeadConsent::where('lead_id', $lead->id)->update([
            'consent_text' => '[DELETED]',
            'source_url'   => null,
            'ip_hash'      => null,
        ]);
    }
}
