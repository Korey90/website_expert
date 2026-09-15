<?php

namespace App\Support\Seo;

/**
 * Normalizes user-supplied domain/URL input and matches SERP result hosts against it.
 * Deliberately simple (no public-suffix-list dependency) — good enough for the UK/NI
 * market this tool targets. See docs/plans/seo-module-tool-plan.md §9-10.
 */
class DomainNormalizer
{
    /** Second-level UK suffixes where "example.co.uk" must be treated as an apex domain. */
    private const UK_SECOND_LEVEL_SUFFIXES = [
        'co.uk', 'org.uk', 'gov.uk', 'ac.uk', 'me.uk',
        'net.uk', 'ltd.uk', 'plc.uk', 'sch.uk', 'nhs.uk', 'police.uk',
    ];

    /**
     * Normalize a raw URL/domain input down to a bare lowercase hostname.
     * Strips scheme, "www.", path/query/fragment and any trailing dot.
     */
    public function normalize(string $input): string
    {
        $value = trim($input);
        $value = preg_replace('#^[a-z][a-z0-9+.-]*://#i', '', $value) ?? $value;
        $value = preg_replace('~[/?#].*$~', '', $value) ?? $value;
        $value = strtolower(trim($value, ". \t\n\r\0\x0B"));

        if (str_starts_with($value, 'www.')) {
            $value = substr($value, 4);
        }

        // Strip a trailing :port if present
        $value = preg_replace('/:\d+$/', '', $value) ?? $value;

        return $value;
    }

    /**
     * Whether the normalized value is a plausible public hostname — rejects
     * empty input, raw IPs, localhost and anything without a dot.
     */
    public function isValidDomain(string $normalizedDomain): bool
    {
        if ($normalizedDomain === '' || $normalizedDomain === 'localhost') {
            return false;
        }

        if (filter_var($normalizedDomain, FILTER_VALIDATE_IP)) {
            return false;
        }

        return (bool) preg_match(
            '/^(?!-)[a-z0-9-]{1,63}(?<!-)(\.(?!-)[a-z0-9-]{1,63}(?<!-))+$/',
            $normalizedDomain
        );
    }

    /**
     * True when the domain has no explicit subdomain of its own (e.g. "example.com",
     * "example.co.uk") — such domains also match their subdomains in hostMatchesDomain().
     * A domain with an extra label (e.g. "shop.example.com") is treated as a specific
     * subdomain the user explicitly wants to check, and only matches exactly.
     */
    public function isApexDomain(string $normalizedDomain): bool
    {
        $labels = explode('.', $normalizedDomain);
        $count = count($labels);

        if ($count <= 2) {
            return true;
        }

        if ($count === 3 && in_array(implode('.', array_slice($labels, -2)), self::UK_SECOND_LEVEL_SUFFIXES, true)) {
            return true;
        }

        return false;
    }

    /**
     * Whether a SERP result host matches the user's target domain, per §9-10 of the plan:
     * exact match always counts; a subdomain only counts when the target is an apex domain.
     */
    public function hostMatchesDomain(string $resultUrlOrHost, string $targetDomain): bool
    {
        $host = $this->normalize($resultUrlOrHost);
        $target = $this->normalize($targetDomain);

        if ($host === '' || $target === '') {
            return false;
        }

        if ($host === $target) {
            return true;
        }

        return $this->isApexDomain($target) && str_ends_with($host, '.'.$target);
    }
}
