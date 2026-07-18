<?php

namespace App\Actions\Domain;

use App\Models\DomainPriceList;
use App\Services\Domain\OpenProviderClient;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Pool;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

class ImportTldsFromOpenproviderAction
{
    public function __construct(private readonly OpenProviderClient $client) {}

    /**
     * Fetch prices for the given extensions and create new DomainPriceList records.
     *
     * @param  string[] $extensions  Extension strings without dot, e.g. ['ac', 'co.uk']
     * @param  string   $currency    ISO currency code, e.g. 'GBP'
     * @param  float    $margin      Default retail margin as a percentage, e.g. 50.0
     * @param  bool     $active      Whether to mark new records as active immediately
     * @return int      Number of records actually created
     */
    public function execute(array $extensions, string $currency, float $margin, bool $active = true): int
    {
        if (empty($extensions)) {
            return 0;
        }

        $token      = $this->client->bearerToken();
        $baseUrl    = $this->client->getBaseUrl();
        $testLabel  = 'zxq9k2w7m4ptest';
        $operations = ['create', 'renew', 'transfer'];
        $multiplier = 1 + ($margin / 100);

        // Fetch prices for all extensions × operations in parallel
        $responses = Http::pool(function (Pool $pool) use ($token, $baseUrl, $extensions, $operations, $testLabel): void {
            foreach ($extensions as $ext) {
                foreach ($operations as $op) {
                    $pool->as("{$ext}__{$op}")
                        ->withToken($token)
                        ->timeout(15)
                        ->get("{$baseUrl}/domains/prices", [
                            'domain.name'      => $testLabel,
                            'domain.extension' => $ext,
                            'operation'        => $op,
                            'period'           => 1,
                        ]);
                }
            }
        });

        $created = 0;

        foreach ($extensions as $ext) {
            $opRegister = $this->parsePrice($responses["{$ext}__create"] ?? null);
            $opRenew    = $this->parsePrice($responses["{$ext}__renew"] ?? null);
            $opTransfer = $this->parsePrice($responses["{$ext}__transfer"] ?? null);

            // Skip TLDs for which we could not retrieve any price
            if ($opRegister === null && $opRenew === null) {
                continue;
            }

            DomainPriceList::create([
                'tld'                => '.' . ltrim($ext, '.'),
                'currency'           => strtoupper($currency),
                'wholesale_register' => $opRegister,
                'wholesale_renew'    => $opRenew,
                'wholesale_transfer' => $opTransfer,
                'register_price'     => $opRegister !== null ? round($opRegister * $multiplier, 2) : 0,
                'renew_price'        => $opRenew !== null ? round($opRenew * $multiplier, 2) : 0,
                'transfer_price'     => $opTransfer !== null ? round($opTransfer * $multiplier, 2) : null,
                'margin_percent'     => $margin,
                'is_active'          => $active,
            ]);

            $created++;
        }

        return $created;
    }

    private function parsePrice(Response|ConnectionException|null $response): ?float
    {
        if (! $response instanceof Response || ! $response->successful()) {
            return null;
        }

        $json  = $response->json();
        $price = ($json['code'] ?? -1) === 0
            ? ($json['data']['price']['reseller']['price'] ?? null)
            : null;

        return $price !== null ? round((float) $price, 2) : null;
    }
}
