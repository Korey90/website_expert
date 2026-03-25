<?php

namespace App\Services;

use App\Models\Client;

/**
 * Gate that checks a client's communication preferences before sending
 * automated emails or SMS messages.
 *
 * Usage:
 *   if (ClientNotificationGate::canSendEmail($client, 'transactional')) { ... }
 *   if (ClientNotificationGate::canSendSms($client)) { ... }
 *
 * Types:
 *   'transactional' → notify_email_transactional (invoices, payment receipts, contract/quote deliveries)
 *   'projects'      → notify_email_projects      (project updates, messages, status changes)
 *   'marketing'     → notify_email_marketing     (automation send_email, newsletters)
 */
class ClientNotificationGate
{
    /**
     * Check if an email of a given type may be sent to the client.
     *
     * @param  Client  $client
     * @param  string  $type  'transactional' | 'projects' | 'marketing'
     */
    public static function canSendEmail(Client $client, string $type): bool
    {
        return match ($type) {
            'transactional' => (bool) $client->notify_email_transactional,
            'projects'      => (bool) $client->notify_email_projects,
            'marketing'     => (bool) $client->notify_email_marketing,
            default         => true, // unknown types pass through
        };
    }

    /**
     * Check if an SMS may be sent to the client.
     */
    public static function canSendSms(Client $client): bool
    {
        return (bool) $client->notify_sms;
    }
}
