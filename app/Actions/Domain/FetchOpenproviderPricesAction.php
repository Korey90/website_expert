<?php

namespace App\Actions\Domain;

use App\Models\DomainPriceList;
use App\Models\Setting;
use App\Services\Domain\OpenProviderClient;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Pool;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

class FetchOpenproviderPricesAction
{
    public function __construct(private readonly OpenProviderClient $client) {}

    /**
     * Fetch current wholesale prices from Openprovider for all active TLDs.
     * Requests are made in parallel (Http::pool). Returns an array of comparison rows.
     *
     * @return array<int, array{
     *   id: int, tld: string,
     *   op_register: float|null, op_renew: float|null, op_transfer: float|null,
     *   cur_register: float, cur_renew: float, cur_transfer: float|null,
     *   changed: bool, margin_percent: float
     * }>
     */
    public function execute(): array
    {
        $baseCurrency = strtoupper((string) config('currencies.default', 'GBP'));
        $records = DomainPriceList::active()->where('currency', $baseCurrency)->get();
        $tlds = $records->map(fn ($r) => ltrim($r->tld, '.'))->unique()->values()->all();
        $operations = ['create', 'renew', 'transfer'];

        // Fire all requests in parallel
        // Use an unlikely-to-be-premium random label to get standard (non-premium) pricing
        $testLabel = 'zxq9k2w7m4ptest';
        $token = $this->client->bearerToken();
        $baseUrl = $this->client->getBaseUrl();

        // Fire all 48 requests (16 TLDs × 3 operations) in parallel
        $responses = Http::pool(function (Pool $pool) use ($token, $baseUrl, $tlds, $operations, $testLabel) {
            foreach ($tlds as $tld) {
                foreach ($operations as $op) {
                    $pool->as("{$tld}__{$op}")
                        ->withToken($token)
                        ->timeout(15)
                        ->get("{$baseUrl}/domains/prices", [
                            'domain.name' => $testLabel,
                            'domain.extension' => $tld,
                            'operation' => $op,
                            'period' => 1,
                        ]);
                }
            }
        });

        $defaultMargin = (float) Setting::get('domain_default_margin', 50);

        $changes = [];
        foreach ($records as $record) {
            $tld = ltrim($record->tld, '.');

            $opRegister = $this->parsePrice($responses["{$tld}__create"] ?? null);
            $opRenew = $this->parsePrice($responses["{$tld}__renew"] ?? null);
            $opTransfer = $this->parsePrice($responses["{$tld}__transfer"] ?? null);

            $curRegister = (float) ($record->wholesale_register ?? 0);
            $curRenew = (float) ($record->wholesale_renew ?? 0);
            $curTransfer = $record->wholesale_transfer !== null
                ? (float) $record->wholesale_transfer
                : null;

            $margin = (float) $record->margin_percent > 0
                ? (float) $record->margin_percent
                : $defaultMargin;

            $newRetailRegister = $opRegister !== null ? round($opRegister * (1 + $margin / 100), 2) : null;
            $newRetailRenew = $opRenew !== null ? round($opRenew * (1 + $margin / 100), 2) : null;
            $newRetailTransfer = $opTransfer !== null ? round($opTransfer * (1 + $margin / 100), 2) : null;

            $changed = $opRegister !== $curRegister
                || $opRenew !== $curRenew
                || $opTransfer !== $curTransfer;

            $changes[] = [
                'id' => $record->id,
                'tld' => $record->tld,
                'op_register' => $opRegister,
                'op_renew' => $opRenew,
                'op_transfer' => $opTransfer,
                'cur_register' => $curRegister,
                'cur_renew' => $curRenew,
                'cur_transfer' => $curTransfer,
                'cur_retail_register' => (float) ($record->register_price ?? 0),
                'cur_retail_renew' => (float) ($record->renew_price ?? 0),
                'cur_retail_transfer' => $record->transfer_price !== null ? (float) $record->transfer_price : null,
                'new_retail_register' => $newRetailRegister,
                'new_retail_renew' => $newRetailRenew,
                'new_retail_transfer' => $newRetailTransfer,
                'changed' => $changed,
                'margin_percent' => $margin,
            ];
        }

        return $changes;
    }

    private function parsePrice(Response|ConnectionException|null $response): ?float
    {
        if (! $response instanceof Response || ! $response->successful()) {
            return null;
        }

        $json = $response->json();

        if (($json['code'] ?? -1) !== 0) {
            return null;
        }

        $price = $json['data']['price']['reseller']['price'] ?? null;

        return $price !== null ? round((float) $price, 2) : null;
    }
}
