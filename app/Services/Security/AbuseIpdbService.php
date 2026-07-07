<?php

namespace App\Services\Security;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AbuseIpdbService
{
    private const BASE_URL = 'https://api.abuseipdb.com/api/v2';

    private const JAIL_CATEGORIES = [
        'sshd'              => '18,22',
        'apache-auth'       => '18,21',
        'apache-badbots'    => '19,21',
        'apache-envfile'    => '21',
        'apache-nohome'     => '21',
        'apache-noscript'   => '21',
        'apache-overflows'  => '21',
    ];

    public function report(string $ip, string $jail, string $comment = ''): bool
    {
        $apiKey = config('services.abuseipdb.key');

        if (! $apiKey) {
            Log::warning('AbuseIPDB: brak klucza API (ABUSEIPDB_API_KEY)');
            return false;
        }

        if ($this->isPrivateIp($ip)) {
            return false;
        }

        $categories = self::JAIL_CATEGORIES[$jail] ?? '21';

        try {
            $response = Http::withHeaders([
                'Key'    => $apiKey,
                'Accept' => 'application/json',
            ])->asForm()->post(self::BASE_URL . '/report', [
                'ip'         => $ip,
                'categories' => $categories,
                'comment'    => $comment ?: "Automated report from fail2ban jail: {$jail}",
            ]);

            if ($response->successful()) {
                Log::info("AbuseIPDB: zgłoszono IP {$ip} (jail: {$jail})");
                return true;
            }

            Log::warning("AbuseIPDB: błąd zgłoszenia {$ip}", ['status' => $response->status(), 'body' => $response->body()]);
            return false;
        } catch (\Throwable $e) {
            Log::error("AbuseIPDB: wyjątek dla {$ip}", ['error' => $e->getMessage()]);
            return false;
        }
    }

    private function isPrivateIp(string $ip): bool
    {
        return filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === false;
    }
}
