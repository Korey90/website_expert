<?php

namespace App\Services;

use App\Models\Client;
use App\Models\Invoice;
use App\Models\Setting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PayuService
{
    private string $baseUrl;
    private string $posId;
    private string $clientId;
    private string $clientSecret;
    private string $md5Key;

    public function __construct()
    {
        $sandbox = (bool) Setting::get('payu_sandbox', true);

        $this->baseUrl      = $sandbox
            ? 'https://secure.snd.payu.com'
            : 'https://secure.payu.com';
        $this->posId        = Setting::get('payu_pos_id', '');
        $this->clientId     = Setting::get('payu_client_id', '');
        $this->clientSecret = Setting::get('payu_client_secret', '');
        $this->md5Key       = Setting::get('payu_md5_key', '');
    }

    /**
     * Obtain OAuth2 access token from PayU.
     */
    public function getToken(): string
    {
        $response = Http::asForm()->post("{$this->baseUrl}/pl/standard/user/oauth/authorize", [
            'grant_type'    => 'client_credentials',
            'client_id'     => $this->clientId,
            'client_secret' => $this->clientSecret,
        ]);

        $response->throw();

        return $response->json('access_token');
    }

    /**
     * Create a PayU order and return ['redirectUri' => ..., 'orderId' => ...].
     */
    public function createOrder(Invoice $invoice, Client $client, string $returnUrl, string $notifyUrl): array
    {
        $token    = $this->getToken();
        $currency = strtoupper($invoice->currency ?? Setting::get('payment_currency', 'GBP'));
        $amount   = (int) round(($invoice->amount_due ?? 0) * 100); // in grosz/pence

        $payload = [
            'notifyUrl'    => $notifyUrl,
            'customerIp'   => request()->ip(),
            'merchantPosId'=> $this->posId,
            'description'  => 'Invoice ' . $invoice->number,
            'currencyCode' => $currency,
            'totalAmount'  => $amount,
            'continueUrl'  => $returnUrl,
            'extOrderId'   => 'inv-' . $invoice->id . '-' . time(),
            'buyer'        => [
                'email'     => $client->primary_contact_email,
                'firstName' => explode(' ', $client->primary_contact_name ?? '')[0] ?? '',
                'lastName'  => implode(' ', array_slice(explode(' ', $client->primary_contact_name ?? ''), 1)) ?: '',
                'language'  => 'en',
            ],
            'products' => [[
                'name'      => 'Invoice ' . $invoice->number,
                'unitPrice' => $amount,
                'quantity'  => 1,
            ]],
        ];

        // PayU follows the redirect itself; we must allow it
        $response = Http::withToken($token)
            ->withoutRedirecting()
            ->post("{$this->baseUrl}/api/v2_1/orders", $payload);

        if ($response->status() === 302) {
            // PayU returns 302 with Location header containing the payment URL
            $location = $response->header('Location');
            $body     = $response->json();
            return [
                'redirectUri' => $location ?: ($body['redirectUri'] ?? ''),
                'orderId'     => $body['orderId'] ?? '',
            ];
        }

        $response->throw();

        $body = $response->json();

        return [
            'redirectUri' => $body['redirectUri'] ?? '',
            'orderId'     => $body['orderId'] ?? '',
        ];
    }

    /**
     * Verify the OpenPayU-Signature header from an IPN notification.
     * Expected format: sender=checkout;signature=HASH;algorithm=MD5;version=2.1
     */
    public function verifySignature(string $signatureHeader, string $body): bool
    {
        // Parse signature header
        $parts = [];
        foreach (explode(';', $signatureHeader) as $part) {
            [$key, $value] = array_pad(explode('=', $part, 2), 2, '');
            $parts[trim($key)] = trim($value);
        }

        $algorithm = strtoupper($parts['algorithm'] ?? 'MD5');
        $received  = $parts['signature'] ?? '';

        if ($algorithm === 'MD5') {
            $expected = md5($body . $this->md5Key);
        } else {
            // SHA-256 or SHA-384 — use hash() accordingly
            $algo     = strtolower(str_replace('-', '', $algorithm));
            $expected = hash($algo, $body . $this->md5Key);
        }

        if (! hash_equals($expected, $received)) {
            Log::warning('PayU IPN signature verification failed', [
                'expected'  => $expected,
                'received'  => $received,
                'algorithm' => $algorithm,
            ]);
            return false;
        }

        return true;
    }
}
