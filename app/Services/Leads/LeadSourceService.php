<?php

namespace App\Services\Leads;

use App\Models\Business;
use App\Models\Lead;
use App\Models\LeadSource;
use Illuminate\Support\Collection;

class LeadSourceService
{
    /**
     * Record attribution data for a newly created lead.
     *
     * @param  Lead  $lead
     * @param  Business|null  $business
     * @param  array{
     *     type: string,
     *     landing_page_id?: int|null,
     *     utm_source?: string|null,
     *     utm_medium?: string|null,
     *     utm_campaign?: string|null,
     *     utm_content?: string|null,
     *     utm_term?: string|null,
     *     referrer_url?: string|null,
     *     page_url?: string|null,
     *     ip_address?: string|null,
     *     user_agent?: string|null,
     * }  $data
     */
    public function record(Lead $lead, ?Business $business, array $data): LeadSource
    {
        $ip       = $data['ip_address'] ?? null;
        $ipHash   = hash('sha256', $ip ?? '');
        $ua       = $data['user_agent'] ?? '';
        $device   = $this->parseDeviceType($ua);

        return LeadSource::create([
            'lead_id'         => $lead->id,
            'business_id'     => $business?->id ?? $lead->business_id,
            'type'            => $data['type'],
            'landing_page_id' => $data['landing_page_id'] ?? null,
            'utm_source'      => $data['utm_source'] ?? null,
            'utm_medium'      => $data['utm_medium'] ?? null,
            'utm_campaign'    => $data['utm_campaign'] ?? null,
            'utm_content'     => $data['utm_content'] ?? null,
            'utm_term'        => $data['utm_term'] ?? null,
            'referrer_url'    => isset($data['referrer_url']) ? substr($data['referrer_url'], 0, 2000) : null,
            'page_url'        => isset($data['page_url']) ? substr($data['page_url'], 0, 2000) : null,
            'ip_address'      => $ip,
            'ip_hash'         => $ipHash,
            'user_agent'      => $ua ?: null,
            'device_type'     => $device,
            'country_code'    => null, // GeoIP lookup — v1.1
            'created_at'      => now(),
        ]);
    }

    /**
     * Simple UA heuristic — no external library required.
     */
    public function parseDeviceType(string $userAgent): string
    {
        $ua = strtolower($userAgent);

        if (str_contains($ua, 'ipad') || str_contains($ua, 'tablet')) {
            return 'tablet';
        }

        if (str_contains($ua, 'mobile') || str_contains($ua, 'android') || str_contains($ua, 'iphone')) {
            return 'mobile';
        }

        return 'desktop';
    }

    /**
     * Count leads per source type for a business over X days.
     * Used by LeadSourcesWidget.
     *
     * @return array<string, int>  ['landing_page' => 15, 'contact_form' => 8, ...]
     */
    public function getChannelBreakdown(Business $business, int $days = 30): array
    {
        return LeadSource::where('business_id', $business->id)
            ->where('created_at', '>=', now()->subDays($days))
            ->selectRaw('type, COUNT(*) as count')
            ->groupBy('type')
            ->pluck('count', 'type')
            ->toArray();
    }

    /**
     * Top landing pages by lead count for a business over X days.
     *
     * @return Collection<int, object{landing_page_id: int, title: string, count: int}>
     */
    public function getTopLandingPages(Business $business, int $days = 30, int $limit = 5): Collection
    {
        return LeadSource::query()
            ->join('landing_pages', 'lead_sources.landing_page_id', '=', 'landing_pages.id')
            ->where('lead_sources.business_id', $business->id)
            ->where('lead_sources.created_at', '>=', now()->subDays($days))
            ->whereNotNull('lead_sources.landing_page_id')
            ->selectRaw('lead_sources.landing_page_id, landing_pages.title, COUNT(*) as count')
            ->groupBy('lead_sources.landing_page_id', 'landing_pages.title')
            ->orderByDesc('count')
            ->limit($limit)
            ->get();
    }
}
