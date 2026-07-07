<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SecurityEvent extends Model
{
    protected $fillable = [
        'ip',
        'jail',
        'attack_type',
        'failures',
        'country',
        'city',
        'isp',
        'action',
        'banned_at',
        'unbanned_at',
        'reported_to_abuseipdb_at',
    ];

    protected $casts = [
        'failures'                   => 'integer',
        'banned_at'                  => 'datetime',
        'unbanned_at'                => 'datetime',
        'reported_to_abuseipdb_at'   => 'datetime',
    ];

    public function isReported(): bool
    {
        return $this->reported_to_abuseipdb_at !== null;
    }

    public function isUnbanned(): bool
    {
        return $this->unbanned_at !== null;
    }

    public static function attackTypeLabel(string $jail): string
    {
        return match ($jail) {
            'sshd'              => 'Brute force SSH',
            'apache-auth'       => 'Brute force HTTP auth',
            'apache-badbots'    => 'Złośliwy bot/skaner',
            'apache-envfile'    => 'Skan .env / path traversal',
            'apache-nohome'     => 'Dostęp do niedozwolonych katalogów',
            'apache-noscript'   => 'Próba wykonania skryptu',
            'apache-overflows'  => 'Buffer overflow / anomalne żądania HTTP',
            default             => "Nieznany typ ({$jail})",
        };
    }
}
