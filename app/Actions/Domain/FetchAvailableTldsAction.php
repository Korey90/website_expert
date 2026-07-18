<?php

namespace App\Actions\Domain;

use App\Models\DomainPriceList;
use App\Services\Domain\OpenProviderClient;
use Illuminate\Support\Facades\Http;

class FetchAvailableTldsAction
{
    /**
     * Common second-level domains (SLDs) not present in the IANA root-zone list
     * but supported by most registrars including Openprovider.
     */
    private const EXTRA_SLDS = [
        'co.uk', 'org.uk', 'me.uk', 'net.uk', 'ltd.uk', 'plc.uk',
        'com.au', 'net.au', 'org.au', 'id.au',
        'co.nz', 'net.nz', 'org.nz',
        'co.za', 'org.za',
        'com.br', 'net.br', 'org.br',
    ];

    public function __construct(private readonly OpenProviderClient $client) {}

    /**
     * Return all TLD/SLD extensions not yet in the local DomainPriceList.
     *
     * Primary source: Openprovider /extensions REST endpoint (if available).
     * Fallback: IANA root-zone TLD list + common SLDs.
     *
     * @return string[] Sorted extension strings without leading dot, e.g. ['ac', 'co.uk']
     */
    public function execute(): array
    {
        try {
            $allExtensions = $this->fetchFromOpenprovider();
        } catch (\Throwable) {
            // Openprovider REST v1beta does not implement GET /extensions (returns 501).
            // Fall back to the authoritative IANA root-zone list + common SLDs.
            $allExtensions = $this->fetchFromIana();
        }

        // Normalise existing DB TLDs to no-dot format for comparison
        $existingTlds = DomainPriceList::pluck('tld')
            ->map(fn (string $tld) => ltrim($tld, '.'))
            ->unique()
            ->values()
            ->all();

        $newExtensions = array_filter(
            $allExtensions,
            fn (string $ext) => ! in_array($ext, $existingTlds, true),
        );

        $sorted = array_values($newExtensions);
        sort($sorted);

        return $sorted;
    }

    // ── Sources ───────────────────────────────────────────────────────────────

    /**
     * Paginate through the Openprovider /extensions endpoint and collect names.
     * Throws RuntimeException if the endpoint is unavailable (e.g. 501).
     *
     * @return string[]
     */
    private function fetchFromOpenprovider(): array
    {
        $limit  = 100;
        $offset = 0;
        $all    = [];

        do {
            $data = $this->client->get('/extensions', [
                'limit'  => $limit,
                'offset' => $offset,
            ]);

            $results = $data['results'] ?? [];
            $total   = (int) ($data['total'] ?? 0);

            foreach ($results as $row) {
                $name = $row['name'] ?? null;
                if ($name !== null && $name !== '') {
                    $all[] = strtolower(trim((string) $name));
                }
            }

            $offset += $limit;
        } while ($offset < $total && count($results) === $limit);

        return array_unique($all);
    }

    /**
     * Fetch the IANA root-zone TLD list and merge in common SLDs.
     * IDN/punycode TLDs (xn--) are excluded.
     *
     * @return string[]
     */
    private function fetchFromIana(): array
    {
        $response = Http::timeout(10)->get('https://data.iana.org/TLD/tlds-alpha-by-domain.txt');

        $ianaList = [];

        if ($response->successful()) {
            $ianaList = collect(explode("\n", $response->body()))
                ->map(fn (string $line) => strtolower(trim($line)))
                ->filter(fn (string $tld) => $tld !== '' && ! str_starts_with($tld, '#') && ! str_starts_with($tld, 'xn--'))
                ->values()
                ->all();
        }

        return array_unique(array_merge($ianaList, self::EXTRA_SLDS));
    }
}
