<?php

return [
    'bank_account_fingerprint_key' => env('FINANCE_BANK_FINGERPRINT_KEY', env('APP_KEY')),
    'bank_statement_import' => [
        'max_bytes' => 5 * 1024 * 1024,
        'max_rows' => 5000,
        'max_columns' => 100,
    ],
    'bank_matching' => [
        'date_window_days' => 30,
        'minimum_score' => 40,
        'maximum_candidates' => 50,
    ],
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
