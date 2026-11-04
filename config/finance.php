<?php

return [
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
