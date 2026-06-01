<?php

return [

    // ── Order status labels ───────────────────────────────────────────────────
    'status' => [
        'pending_payment' => 'Awaiting Payment',
        'paid'            => 'Paid',
        'registering'     => 'Registering',
        'completed'       => 'Completed',
        'failed'          => 'Failed',
        'cancelled'       => 'Cancelled',
    ],

    // ── Order / renewal action labels ─────────────────────────────────────────
    'action' => [
        'register' => 'Registration',
        'renew'    => 'Renewal',
        'transfer' => 'Transfer',
    ],

    // ── Renewal reminder ──────────────────────────────────────────────────────
    'reminder' => [
        'subject'   => 'Your domain :domain expires in :days day(s)',
        'days_30'   => 'Your domain :domain will expire in 30 days. Please renew it to avoid any disruption.',
        'days_14'   => 'Your domain :domain will expire in 14 days. Renew now to keep your domain active.',
        'days_7'    => 'Your domain :domain will expire in 7 days. Urgent — please renew immediately.',
        'days_1'    => 'Your domain :domain expires tomorrow! Renew now to prevent it going offline.',
        'expired'   => 'Your domain :domain has expired. Contact us to discuss reinstatement options.',
    ],

    // ── UI labels ─────────────────────────────────────────────────────────────
    'label' => [
        'domain_name'    => 'Domain name',
        'tld'            => 'Extension (TLD)',
        'expires_at'     => 'Expires on',
        'registered_at'  => 'Registered on',
        'years'          => ':count year(s)',
        'auto_renew'     => 'Auto-renew',
        'whois_privacy'  => 'WHOIS privacy',
        'nameservers'    => 'Nameservers',
        'register_price' => 'Registration price',
        'renew_price'    => 'Renewal price',
        'transfer_price' => 'Transfer price',
        'available'      => 'Available',
        'taken'          => 'Not available',
        'checking'       => 'Checking availability…',
    ],

    // ── Error messages ────────────────────────────────────────────────────────
    'error' => [
        'unavailable'           => 'The domain :domain is not available for registration.',
        'registration_failed'   => 'Domain registration failed for :domain. Our team has been notified.',
        'renewal_failed'        => 'Domain renewal failed for :domain. Please contact support.',
        'transfer_failed'       => 'Domain transfer failed for :domain. Please check your auth code and try again.',
        'nameservers_failed'    => 'Failed to update nameservers for :domain.',
        'order_not_cancellable' => 'This order cannot be cancelled at its current status.',
        'stripe_not_configured' => 'Online payments are not configured. Please contact support.',
    ],

    // ── Notifications ─────────────────────────────────────────────────────────
    'notification' => [
        'order_placed_subject'   => 'Domain order confirmed — :domain',
        'order_placed_body'      => 'Thank you for your order. We are processing your domain :domain (:action).',
        'registered_subject'     => 'Your domain :domain has been registered',
        'registered_body'        => 'Great news! Your domain :domain has been successfully registered and is now active.',
        'failed_subject'         => 'Domain registration failed — :domain',
        'failed_body'            => 'Unfortunately we were unable to register :domain. Our team has been notified and will contact you shortly.',
    ],

];
