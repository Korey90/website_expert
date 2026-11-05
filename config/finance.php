<?php

return [
    'bank_account_fingerprint_key' => env('FINANCE_BANK_FINGERPRINT_KEY', env('APP_KEY')),
    'expense_categories' => [
        'cost_of_sales',
        'software',
        'office',
        'travel',
        'professional_fees',
        'marketing',
        'utilities',
        'insurance',
        'rent',
        'bank_fees',
        'other',
    ],
    'attachments' => [
        'disk' => env('FINANCE_ATTACHMENTS_DISK', 'financial'),
        'max_bytes' => 10 * 1024 * 1024,
        'retention_years' => 6,
        'allowed_mime_types' => [
            'application/pdf',
            'image/jpeg',
            'image/png',
            'image/webp',
            'text/plain',
            'text/csv',
            'application/csv',
            'application/vnd.ms-excel',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        ],
        'document_types' => [
            'invoice',
            'receipt',
            'bank_statement',
            'contract',
            'hmrc_acknowledgement',
            'other',
        ],
    ],
];
