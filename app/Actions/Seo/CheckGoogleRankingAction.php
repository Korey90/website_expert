<?php

namespace App\Actions\Seo;

use App\Data\Seo\RankCheckResult;
use App\Exceptions\Seo\SerpProviderException;
use App\Models\SearchRankingCheck;
use App\Services\Seo\SerpProviderInterface;
use App\Support\Seo\DomainNormalizer;
use Illuminate\Support\Facades\Cache;

/**
 * Single reusable "check ranking" business function (see plan §35/§50) —
 * used today by the public checker, and later by the client dashboard,
 * scheduled keyword tracking and reports.
 */
class CheckGoogleRankingAction
{
    public function __construct(
        private readonly SerpProviderInterface $provider,
        private readonly DomainNormalizer $domains,
    ) {}

    public function execute(
        string $keyword,
        string $rawDomain,
        string $location,
        string $device,
        string $language = 'en',
        ?string $businessId = null,
        ?string $ipHash = null,
    ): RankCheckResult {
        $keyword = trim(preg_replace('/\s+/', ' ', $keyword) ?? $keyword);
        $domain = $this->domains->normalize($rawDomain);

        $cacheKey = $this->cacheKey($keyword, $domain, $location, $device, $language);
        $cached = Cache::get($cacheKey);

        if (is_array($cached)) {
            $result = $this->resultFromArray($cached, fromCache: true);
            $this->persist($result, $businessId, $ipHash);

            return $result;
        }

        $topResults = (int) config('seo.rank_checker.top_results', 100);
        $locationCode = $this->resolveLocationCode($location);

        try {
            $searchResult = $this->provider->searchOrganicResults(
                $keyword,
                $location,
                $locationCode,
                $device,
                $language,
                $topResults,
            );
        } catch (SerpProviderException $e) {
            $result = RankCheckResult::failed(
                $keyword,
                $domain,
                $location,
                $device,
                $language,
                config('seo.rank_checker.provider', 'manual'),
                $e->getMessage(),
            );

            $this->persist($result, $businessId, $ipHash, errorMessage: $e->getMessage());

            return $result;
        }

        $best = null;
        foreach ($searchResult->organicResults as $organic) {
            if (! $this->domains->hostMatchesDomain($organic->url, $domain)) {
                continue;
            }
            if ($best === null || $organic->position < $best->position) {
                $best = $organic;
            }
        }

        $result = $best !== null
            ? RankCheckResult::found($keyword, $domain, $location, $device, $language, $best->position, $best->url, $searchResult->provider)
            : RankCheckResult::notFound($keyword, $domain, $location, $device, $language, $searchResult->provider);

        $ttlHours = (int) config('seo.rank_checker.cache_ttl_hours', 8);
        Cache::put($cacheKey, $result->toArray(), now()->addHours($ttlHours));

        $this->persist($result, $businessId, $ipHash);

        return $result;
    }

    private function resolveLocationCode(string $location): ?string
    {
        foreach (config('seo.rank_checker.locations', []) as $entry) {
            if (strcasecmp($entry['name'] ?? '', $location) === 0) {
                return $entry['location_code'] ?? null;
            }
        }

        return null;
    }

    private function cacheKey(string $keyword, string $domain, string $location, string $device, string $language): string
    {
        return 'rank_check:'.md5(strtolower(implode('|', [$keyword, $domain, $location, $device, $language])));
    }

    private function resultFromArray(array $data, bool $fromCache): RankCheckResult
    {
        return new RankCheckResult(
            status: $data['status'],
            keyword: $data['keyword'],
            domain: $data['domain'],
            location: $data['location'],
            device: $data['device'],
            language: $data['language'],
            position: $data['position'],
            rankingUrl: $data['ranking_url'],
            provider: $data['provider'],
            fromCache: $fromCache,
            message: $data['message'] ?? null,
        );
    }

    private function persist(
        RankCheckResult $result,
        ?string $businessId,
        ?string $ipHash,
        ?string $errorMessage = null,
    ): void {
        SearchRankingCheck::create([
            'business_id' => $businessId,
            'keyword' => $result->keyword,
            'domain' => $result->domain,
            'location' => $result->location,
            'device' => $result->device,
            'language' => $result->language,
            'status' => $result->status,
            'position' => $result->position,
            'ranking_url' => $result->rankingUrl,
            'provider' => $result->provider,
            'from_cache' => $result->fromCache,
            'ip_hash' => $ipHash,
            'error_message' => $errorMessage,
            'created_at' => now(),
        ]);
    }
}
