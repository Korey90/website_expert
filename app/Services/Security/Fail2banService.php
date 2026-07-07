<?php

namespace App\Services\Security;

use Illuminate\Support\Facades\Log;

class Fail2banService
{
    public function unban(string $ip, string $jail): bool
    {
        $escaped_ip   = escapeshellarg($ip);
        $escaped_jail = escapeshellarg($jail);

        $output = [];
        $code   = 0;

        exec("sudo fail2ban-client set {$escaped_jail} unbanip {$escaped_ip} 2>&1", $output, $code);

        if ($code === 0) {
            Log::info("Fail2ban: odbanowano {$ip} z jail {$jail}");
            return true;
        }

        Log::warning("Fail2ban: błąd odbanowania {$ip}", ['output' => implode("\n", $output), 'code' => $code]);
        return false;
    }

    public function ban(string $ip, string $jail): bool
    {
        $escaped_ip   = escapeshellarg($ip);
        $escaped_jail = escapeshellarg($jail);

        $output = [];
        $code   = 0;

        exec("sudo fail2ban-client set {$escaped_jail} banip {$escaped_ip} 2>&1", $output, $code);

        if ($code === 0) {
            Log::info("Fail2ban: zbanowano {$ip} w jail {$jail}");
            return true;
        }

        Log::warning("Fail2ban: błąd banowania {$ip}", ['output' => implode("\n", $output), 'code' => $code]);
        return false;
    }

    public function getBannedIps(string $jail): array
    {
        $escaped_jail = escapeshellarg($jail);
        $output       = [];

        exec("sudo fail2ban-client get {$escaped_jail} banned 2>&1", $output);

        $line = implode(' ', $output);

        preg_match_all('/\b\d{1,3}(?:\.\d{1,3}){3}\b/', $line, $matches);

        return $matches[0] ?? [];
    }
}
