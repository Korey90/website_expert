<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Support\Facades\Log;
use Twilio\Rest\Client as TwilioClient;

class SmsService
{
    private bool   $enabled;
    private string $sid;
    private string $token;
    private string $from;

    public function __construct()
    {
        // Prefer DB settings (admin-configurable), fall back to env/config
        $this->enabled = (bool) Setting::get('twilio_enabled', config('services.twilio.sid') ? true : false);
        $this->sid     = Setting::get('twilio_sid',   config('services.twilio.sid',   ''));
        $this->token   = Setting::get('twilio_token', config('services.twilio.token', ''));
        $this->from    = Setting::get('twilio_from',  config('services.twilio.from',  ''));
    }

    /**
     * Send an SMS message. Returns true on success, false on failure.
     */
    public function send(string $to, string $message): bool
    {
        if (! $this->enabled) {
            Log::info('SmsService: SMS disabled, skipping message.', compact('to'));
            return false;
        }

        if (! $this->sid || ! $this->token || ! $this->from) {
            Log::warning('SmsService: Twilio credentials not configured.');
            return false;
        }

        $to = $this->normalizePhone($to);
        if (! $to) {
            Log::warning('SmsService: Invalid phone number, skipping.', compact('to'));
            return false;
        }

        try {
            $client = new TwilioClient($this->sid, $this->token);
            $client->messages->create($to, [
                'from' => $this->from,  // phone number (+44...) or alphanumeric sender ID (e.g. "WebExpert")
                'body' => $message,
            ]);

            Log::info('SmsService: SMS sent.', ['to' => $to, 'chars' => strlen($message)]);
            return true;
        } catch (\Throwable $e) {
            Log::error('SmsService: Failed to send SMS: ' . $e->getMessage(), compact('to'));
            return false;
        }
    }

    /**
     * Ensure the phone number starts with + for E.164 format.
     * Returns null for obviously invalid inputs.
     */
    private function normalizePhone(string $phone): ?string
    {
        $phone = preg_replace('/[\s\-()]/', '', $phone);

        if (empty($phone)) {
            return null;
        }

        // Already in E.164
        if (str_starts_with($phone, '+')) {
            return $phone;
        }

        // UK numbers: 07xxx → +447xxx
        if (str_starts_with($phone, '07') && strlen($phone) === 11) {
            return '+44' . substr($phone, 1);
        }

        // Fallback: prepend + if only digits
        if (ctype_digit($phone)) {
            return '+' . $phone;
        }

        return null;
    }
}
