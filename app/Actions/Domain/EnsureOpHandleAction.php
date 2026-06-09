<?php

namespace App\Actions\Domain;

use App\Models\Client;
use App\Services\Domain\OpenProviderClient;

class EnsureOpHandleAction
{
    public function __construct(private readonly OpenProviderClient $client) {}

    /**
     * Return the Openprovider customer handle for a client.
     * If cached on the client record, syncs the OP customer with the latest contact
     * data (so required fields like city are always up-to-date) and returns the handle.
     * Otherwise, finds or creates the handle via the OP API and persists it.
     */
    public function execute(Client $client, array $contactData): string
    {
        if ($client->op_handle) {
            $this->syncHandle($client->op_handle, $contactData);
            return $client->op_handle;
        }

        $handle = $this->resolveFromApi($contactData);
        $client->update(['op_handle' => $handle]);

        return $handle;
    }

    private function resolveFromApi(array $contact): string
    {
        try {
            $data = $this->client->get('/customers', [
                'email_pattern' => $contact['email'] ?? '',
                'limit'         => 1,
            ]);

            if (! empty($data['results'])) {
                $handle = (string) $data['results'][0]['handle'];
                $this->syncHandle($handle, $contact);
                return $handle;
            }
        } catch (\Throwable) {
            // Fall through to create
        }

        return $this->createHandle($contact);
    }

    /**
     * Update an existing OP customer handle with the latest contact data.
     * Non-critical — failures are silently swallowed.
     */
    private function syncHandle(string $handle, array $contact): void
    {
        try {
            $phone = $this->parsePhone($contact['phone'] ?? '', $contact['country_code'] ?? 'GB');

            $body = [
                'name'    => [
                    'first_name' => $contact['first_name'] ?? '',
                    'last_name'  => $contact['last_name']  ?? '',
                ],
                'phone'   => $phone,
                'address' => [
                    'street'   => $contact['address_line1'] ?? '',
                    // address_line1 already contains the full street+number (UK format);
                    // address_line2 is supplementary (flat/suite) and does not map to OP's
                    // `number` field which expects the building number only.
                    'number'   => '',
                    'city'     => $contact['city']          ?? '',
                    'province' => $contact['county']        ?? '',
                    'zipcode'  => $contact['postcode']      ?? '',
                    'country'  => strtoupper($contact['country_code'] ?? 'GB'),
                ],
            ];

            if (! empty($contact['organisation'])) {
                $body['company_name'] = $contact['organisation'];
            }

            $this->client->put("/customers/{$handle}", $body);
        } catch (\Throwable) {
            // Best-effort sync — do not block domain registration
        }
    }

    private function createHandle(array $contact): string
    {
        $phone = $this->parsePhone($contact['phone'] ?? '', $contact['country_code'] ?? 'GB');

        $body = [
            'name'    => [
                'first_name' => $contact['first_name'] ?? '',
                'last_name'  => $contact['last_name']  ?? '',
            ],
            'email'   => $contact['email'] ?? '',
            'phone'   => $phone,
            'address' => [
                'street'   => $contact['address_line1'] ?? '',
                // address_line1 already contains the full street+number (UK format);
                // address_line2 is supplementary (flat/suite) and does not map to OP's
                // `number` field which expects the building number only.
                'number'   => '',
                'city'     => $contact['city']          ?? '',
                'province' => $contact['county']        ?? '',
                'zipcode'  => $contact['postcode']      ?? '',
                'country'  => strtoupper($contact['country_code'] ?? 'GB'),
            ],
        ];

        if (! empty($contact['organisation'])) {
            $body['company_name'] = $contact['organisation'];
        }

        $data = $this->client->post('/customers', $body);

        return (string) $data['handle'];
    }

    private function parsePhone(string $phone, string $isoCountry = 'GB'): array
    {
        $phone = trim($phone);

        if (str_starts_with($phone, '+')) {
            if (preg_match('/^\+(\d{1,3})[\s\-.]*([\d\s\-.]+)$/', $phone, $m)) {
                return [
                    'country_code'      => $m[1],
                    'area_code'         => '0',
                    'subscriber_number' => preg_replace('/\D/', '', $m[2]),
                ];
            }
        }

        $cc = match (strtoupper($isoCountry)) {
            'US', 'CA' => '1',
            'GB'       => '44',
            'DE'       => '49',
            'FR'       => '33',
            'PL'       => '48',
            'PT'       => '351',
            'ES'       => '34',
            'IT'       => '39',
            'NL'       => '31',
            'SE'       => '46',
            'NO'       => '47',
            'DK'       => '45',
            'FI'       => '358',
            'IE'       => '353',
            default    => '1',
        };

        return [
            'country_code'      => $cc,
            'area_code'         => '0',
            'subscriber_number' => preg_replace('/\D/', '', $phone),
        ];
    }
}
