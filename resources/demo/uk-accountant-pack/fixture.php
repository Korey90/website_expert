<?php

$row = static fn (array $cells, string $type = ''): array => ['cells' => $cells, 'type' => $type];
$metadata = static fn (string $reference, string $period = '1 Jul–30 Sep 2026'): array => [
    ['label' => 'Reference', 'value' => $reference],
    ['label' => 'Period', 'value' => $period],
    ['label' => 'Basis', 'value' => 'Accrual'],
    ['label' => 'Currency', 'value' => 'GBP'],
];

$documents = [
    [
        'filename' => '00-read-me-and-review-guide.pdf',
        'title' => 'Accountant Review Guide',
        'subtitle' => 'UK finance and tax demonstration pack',
        'classification' => 'REVIEW GUIDE',
        'badge_tone' => 'external',
        'metadata' => $metadata('PACK-2026-Q3'),
        'intro' => 'This pack uses company details from the system and one internally consistent quarter of fictional transactions. It separates current system output, externally supplied evidence and target previews of planned reporting functions.',
        'sections' => [
            [
                'heading' => 'Document map',
                'table' => [
                    'headers' => ['Files', 'Document', 'System status', 'Primary review focus'],
                    'right_columns' => [],
                    'rows' => [
                        $row(['01–02', 'UK VAT invoices (GBP and EUR)', 'Current output', 'VAT invoice fields, FX/GBP VAT snapshot']),
                        $row(['03', 'Customer credit note', 'Current output', 'Original invoice reference and VAT reversal']),
                        $row(['04–05', 'Supplier invoice and supplier credit note', 'External evidence stored by system', 'Input VAT evidence and attachment metadata']),
                        $row(['06–17', 'Receipts, reconciliation and accounting reports', 'Target preview', 'Layout, controls, drill-down and period logic']),
                    ],
                ],
            ],
            [
                'heading' => 'Control totals',
                'table' => [
                    'headers' => ['Control', 'Expected result'],
                    'right_columns' => [1],
                    'rows' => [
                        $row(['VAT Box 5 payable', '£712.80']),
                        $row(['Profit for the quarter', '£3,226.00']),
                        $row(['Trial Balance debit / credit', '£53,738.80']),
                        $row(['Balance Sheet total', '£43,138.80']),
                        $row(['Bank ledger closing balance', '£24,212.00']),
                    ],
                ],
            ],
            [
                'heading' => 'Scope boundary',
                'paragraphs' => [
                    'The pack is not an HMRC form, tax advice or a filing-ready return. No submission has taken place.',
                    'The example assumes standard VAT accounting on an accrual basis. Cash accounting, Flat Rate Scheme, margin schemes, partial exemption, CIS and Northern Ireland/EU goods movements require separate review.',
                ],
            ],
        ],
        'notes' => [
            'Please record required changes in accountant-review-checklist.md.',
            'Confirm tax-code-to-VAT-box mappings before any production VAT submission work.',
            'Company details come from Legal Settings; the AccountingProfile is {{profile_consistency}} and must be synchronised before production invoicing.',
        ],
        'sources' => [
            'GOV.UK VAT Notice 700 §16.3.1',
            'GOV.UK VAT Notice 700/12',
            'HMRC VAT (MTD) API v1.0',
        ],
    ],
    [
        'filename' => '04-purchase-invoice-expense.pdf',
        'title' => 'Supplier VAT Invoice',
        'subtitle' => 'External purchase evidence preview',
        'classification' => 'SOURCE DOCUMENT (STORED)',
        'badge_tone' => 'external',
        'metadata' => [
            ['label' => 'Supplier invoice', 'value' => 'CF-88421'],
            ['label' => 'Invoice date', 'value' => '12 Jul 2026'],
            ['label' => 'Due date', 'value' => '11 Aug 2026'],
            ['label' => 'Currency', 'value' => 'GBP'],
        ],
        'intro' => 'This is an illustrative incoming supplier document. WebsiteExpert stores the original private attachment and its accounting extraction; it does not issue the supplier’s invoice.',
        'sections' => [
            ['heading' => 'Supplier and customer', 'table' => [
                'headers' => ['Supplier', 'Bill to'], 'right_columns' => [], 'rows' => [
                    $row(['Cloud Forge Hosting Ltd · 7 Temple Quay, Bristol BS1 6DZ · VAT GB 246 8013 57', '{{company_name}} · {{company_address_inline}}']),
                ],
            ]],
            ['heading' => 'Items', 'table' => [
                'headers' => ['Description', 'Qty', 'Unit net', 'VAT rate', 'Net', 'VAT', 'Gross'], 'right_columns' => [1, 2, 4, 5, 6], 'rows' => [
                    $row(['Managed cloud hosting — Jul–Sep 2026', '3', '£400.00', '20%', '£1,200.00', '£240.00', '£1,440.00']),
                    $row(['Total', '', '', '', '£1,200.00', '£240.00', '£1,440.00'], 'total'),
                ],
            ]],
            ['heading' => 'System evidence metadata', 'table' => [
                'headers' => ['Field', 'Value'], 'right_columns' => [], 'rows' => [
                    $row(['Attachment status', 'Clean / private storage']),
                    $row(['Accounting status', 'Posted']),
                    $row(['Input VAT mapping', 'Box 4 £240.00; Box 7 £1,200.00']),
                    $row(['Document integrity', 'SHA-256 recorded on upload']),
                ],
            ]],
        ],
        'notes' => ['Confirm whether the extracted metadata and original attachment are sufficient for the accountant workflow.'],
    ],
    [
        'filename' => '05-supplier-credit-note.pdf',
        'title' => 'Supplier Credit Note',
        'subtitle' => 'External purchase credit evidence preview',
        'classification' => 'SOURCE DOCUMENT (STORED)',
        'badge_tone' => 'external',
        'metadata' => [
            ['label' => 'Credit note', 'value' => 'CF-CN-1198'],
            ['label' => 'Original invoice', 'value' => 'CF-88421'],
            ['label' => 'Issue date', 'value' => '22 Jul 2026'],
            ['label' => 'Currency', 'value' => 'GBP'],
        ],
        'intro' => 'Illustrative supplier-issued credit note stored as private evidence and represented internally as a purchase credit note.',
        'sections' => [
            ['heading' => 'Credit details', 'table' => [
                'headers' => ['Reason', 'Net credit', 'VAT credit', 'Total credit'], 'right_columns' => [1, 2, 3], 'rows' => [
                    $row(['Service availability adjustment', '£200.00', '£40.00', '£240.00']),
                    $row(['Total supplier credit', '£200.00', '£40.00', '£240.00'], 'total'),
                ],
            ]],
            ['heading' => 'Accounting treatment', 'table' => [
                'headers' => ['Effect', 'Amount'], 'right_columns' => [1], 'rows' => [
                    $row(['Reduce cloud hosting expense', '£200.00']),
                    $row(['Reduce recoverable input VAT (Box 4)', '£40.00']),
                    $row(['Supplier cash refund received', '£240.00']),
                ],
            ]],
        ],
        'notes' => ['Confirm preferred terminology for allocated credits versus cash refunds.'],
    ],
    [
        'filename' => '06-payment-receipt.pdf',
        'title' => 'Customer Payment Receipt',
        'subtitle' => 'Receipt and allocation confirmation',
        'classification' => 'TARGET PREVIEW',
        'badge_tone' => 'preview',
        'metadata' => [
            ['label' => 'Receipt', 'value' => 'RCT-2026-0071'],
            ['label' => 'Payment date', 'value' => '18 Jul 2026'],
            ['label' => 'Customer', 'value' => 'Northstar Retail Ltd'],
            ['label' => 'Method', 'value' => 'Bank transfer'],
        ],
        'intro' => 'Proposed confirmation generated after a completed customer receipt is allocated to an invoice.',
        'sections' => [
            ['heading' => 'Allocation', 'table' => [
                'headers' => ['Bank reference', 'Invoice', 'Invoice gross', 'Allocated', 'Remaining'], 'right_columns' => [2, 3, 4], 'rows' => [
                    $row(['NORTHSTAR INV0101', 'INV-2026-0101', '£7,200.00', '£7,200.00', '£0.00']),
                    $row(['Total received', '', '', '£7,200.00', '£0.00'], 'total'),
                ],
            ]],
        ],
        'notes' => ['Confirm whether a payment receipt is required and whether bank details should be omitted or masked.'],
    ],
    [
        'filename' => '07-bank-reconciliation.pdf',
        'title' => 'Bank Reconciliation',
        'subtitle' => 'Operating account ending 8842',
        'classification' => 'TARGET PREVIEW',
        'badge_tone' => 'preview',
        'metadata' => $metadata('BANK-8842-Q3'),
        'intro' => 'Proposed period-end reconciliation between the imported bank statement and the bank ledger account.',
        'sections' => [
            ['heading' => 'Reconciliation summary', 'table' => [
                'headers' => ['Reconciliation item', 'Amount'], 'right_columns' => [1], 'rows' => [
                    $row(['Bank statement closing balance', '£24,362.00']),
                    $row(['Add: deposit in transit — NORTHSTAR PART', '£450.00']),
                    $row(['Less: unpresented supplier payment — SUB-4401', '(£600.00)']),
                    $row(['Adjusted bank statement balance', '£24,212.00'], 'subtotal'),
                    $row(['Bank ledger closing balance', '£24,212.00']),
                    $row(['Difference', '£0.00'], 'total'),
                ],
            ]],
            ['heading' => 'Matching status', 'table' => [
                'headers' => ['Status', 'Count', 'Value'], 'right_columns' => [1, 2], 'rows' => [
                    $row(['Matched', '8', '£18,480.00']),
                    $row(['Outstanding timing items', '2', '£1,050.00']),
                    $row(['Unexplained difference', '0', '£0.00'], 'total'),
                ],
            ]],
        ],
        'notes' => ['The matching and undo workflows are planned in UKFIN-405–407; the report is planned in UKFIN-408.'],
    ],
    [
        'filename' => '08-vat-return-boxes-1-to-9.pdf',
        'title' => 'VAT Return Review',
        'subtitle' => 'Nine-box working paper',
        'classification' => 'TARGET PREVIEW',
        'badge_tone' => 'preview',
        'metadata' => [
            ['label' => 'VAT period', 'value' => '1 Jul–30 Sep 2026'],
            ['label' => 'VAT number', 'value' => '{{vat_number}}'],
            ['label' => 'Scheme', 'value' => 'Standard / accrual'],
            ['label' => 'Submission', 'value' => 'Not submitted'],
        ],
        'intro' => 'Working paper preview only. Values are compiled from the demo digital records and have not been submitted to HMRC.',
        'sections' => [
            ['heading' => 'VAT Return boxes', 'table' => [
                'headers' => ['Box', 'Description', 'Value'], 'right_columns' => [2], 'rows' => [
                    $row(['1', 'VAT due on sales and other outputs', '£1,912.80']),
                    $row(['2', 'VAT due on acquisitions of goods made in Northern Ireland from EU member states', '£0.00']),
                    $row(['3', 'Total VAT due (Box 1 + Box 2)', '£1,912.80'], 'subtotal'),
                    $row(['4', 'VAT reclaimed on purchases and other inputs', '£1,200.00']),
                    $row(['5', 'Net VAT payable to HMRC', '£712.80'], 'total'),
                    $row(['6', 'Total value of sales and other outputs excluding VAT', '£9,564']),
                    $row(['7', 'Total value of purchases and other inputs excluding VAT', '£8,600']),
                    $row(['8', 'Supplies of goods and related costs from Northern Ireland to EU member states', '£0']),
                    $row(['9', 'Acquisitions of goods and related costs made in Northern Ireland from EU member states', '£0']),
                ],
            ]],
            ['heading' => 'Control', 'table' => [
                'headers' => ['Calculation', 'Result'], 'right_columns' => [1], 'rows' => [
                    $row(['Box 1 + Box 2 − Box 4', '£712.80']),
                    $row(['Difference to Box 5', '£0.00'], 'total'),
                ],
            ]],
        ],
        'notes' => [
            'Boxes 6–9 are shown in whole pounds for the review example.',
            'Tax-code mappings and special-scheme treatment require accountant approval before production submission.',
        ],
        'sources' => ['GOV.UK VAT Notice 700/12 (updated 19 Dec 2025)', 'HMRC VAT (MTD) API v1.0'],
    ],
    [
        'filename' => '09-vat-transaction-detail.pdf',
        'title' => 'VAT Transaction Detail',
        'subtitle' => 'Digital-record audit support',
        'classification' => 'TARGET PREVIEW',
        'badge_tone' => 'preview',
        'orientation' => 'landscape',
        'metadata' => $metadata('VAT-DETAIL-2026-Q3'),
        'intro' => 'Transaction-level support for the VAT Return. Credit notes reduce the relevant sales or purchase values.',
        'sections' => [[
            'heading' => 'VAT transactions', 'allow_break' => true, 'table' => [
                'headers' => ['Tax point', 'Document', 'Type', 'Counterparty', 'Net', 'VAT', 'Gross', 'VAT box effect'],
                'right_columns' => [4, 5, 6],
                'rows' => [
                    $row(['04 Jul', 'INV-2026-0101', 'Sale', 'Northstar Retail Ltd', '£6,000.00', '£1,200.00', '£7,200.00', 'B1 +1,200 / B6 +6,000']),
                    $row(['04 Jul', 'CN-2026-0003', 'Sales credit', 'Northstar Retail Ltd', '(£500.00)', '(£100.00)', '(£600.00)', 'B1 −100 / B6 −500']),
                    $row(['09 Aug', 'INV-2026-0102', 'Sale (EUR→GBP)', 'Northstar Retail Ltd', '£2,064.00', '£412.80', '£2,476.80', 'B1 +412.80 / B6 +2,064']),
                    $row(['14 Sep', 'INV-2026-0103', 'Sale', 'Beacon Arts CIC', '£2,000.00', '£400.00', '£2,400.00', 'B1 +400 / B6 +2,000']),
                    $row(['12 Jul', 'CF-88421', 'Purchase', 'Cloud Forge Hosting Ltd', '£1,200.00', '£240.00', '£1,440.00', 'B4 +240 / B7 +1,200']),
                    $row(['22 Jul', 'CF-CN-1198', 'Purchase credit', 'Cloud Forge Hosting Ltd', '(£200.00)', '(£40.00)', '(£240.00)', 'B4 −40 / B7 −200']),
                    $row(['01 Jul', 'RENT-Q3', 'Exempt purchase', 'Linenhall Property Ltd', '£2,400.00', '£0.00', '£2,400.00', 'B7 +2,400']),
                    $row(['03 Aug', 'EQUIP-7304', 'Capital purchase', 'Tech Supply UK Ltd', '£3,000.00', '£600.00', '£3,600.00', 'B4 +600 / B7 +3,000']),
                    $row(['18 Aug', 'RAIL-293', 'Zero-rated purchase', 'Rail Operator', '£200.00', '£0.00', '£200.00', 'B7 +200']),
                    $row(['07 Sep', 'SUB-4401', 'Purchase', 'Studio Partner Ltd', '£2,000.00', '£400.00', '£2,400.00', 'B4 +400 / B7 +2,000']),
                    $row(['Quarter totals', '', '', '', 'Sales £9,564 / Purchases £8,600', 'Output £1,912.80 / Input £1,200', '', 'Box 5 £712.80'], 'total'),
                ],
            ],
        ]],
        'notes' => ['The EUR invoice VAT is reported using its stored GBP snapshot of £412.80.'],
    ],
    [
        'filename' => '10-profit-and-loss.pdf',
        'title' => 'Profit and Loss',
        'subtitle' => 'Quarterly management report',
        'classification' => 'TARGET PREVIEW',
        'badge_tone' => 'preview',
        'metadata' => $metadata('P&L-2026-Q3'),
        'intro' => 'Accrual-basis management statement for the demonstration quarter. Comparative columns are proposed for the production report.',
        'sections' => [[
            'heading' => 'Profit and Loss', 'table' => [
                'headers' => ['Account', 'Quarter', 'Year to date'], 'right_columns' => [1, 2], 'rows' => [
                    $row(['Sales revenue', '£10,064.00', '£10,064.00']),
                    $row(['Less: sales returns and credits', '(£500.00)', '(£500.00)']),
                    $row(['Net revenue', '£9,564.00', '£9,564.00'], 'subtotal'),
                    $row(['Cloud hosting', '(£1,000.00)', '(£1,000.00)']),
                    $row(['Office rent', '(£2,400.00)', '(£2,400.00)']),
                    $row(['Travel', '(£200.00)', '(£200.00)']),
                    $row(['Subcontractors', '(£2,000.00)', '(£2,000.00)']),
                    $row(['Depreciation', '(£750.00)', '(£750.00)']),
                    $row(['Total operating expenses', '(£6,350.00)', '(£6,350.00)'], 'subtotal'),
                    $row(['Operating profit', '£3,214.00', '£3,214.00'], 'subtotal'),
                    $row(['Bank interest received', '£12.00', '£12.00']),
                    $row(['Profit for the period', '£3,226.00', '£3,226.00'], 'total'),
                ],
            ],
        ]],
    ],
    [
        'filename' => '11-balance-sheet.pdf',
        'title' => 'Balance Sheet',
        'subtitle' => 'As at 30 September 2026',
        'classification' => 'TARGET PREVIEW',
        'badge_tone' => 'preview',
        'metadata' => $metadata('BS-2026-09-30', 'As at 30 Sep 2026'),
        'intro' => 'Management balance sheet derived from the same closing ledger balances as the Trial Balance.',
        'sections' => [[
            'heading' => 'Assets, liabilities and equity', 'table' => [
                'headers' => ['Account', 'Amount'], 'right_columns' => [1], 'rows' => [
                    $row(['Bank', '£24,212.00']),
                    $row(['Trade debtors', '£7,676.80']),
                    $row(['Computer equipment at cost', '£15,000.00']),
                    $row(['Less: accumulated depreciation', '(£3,750.00)']),
                    $row(['Total assets', '£43,138.80'], 'total'),
                    $row(['Trade creditors', '£3,700.00']),
                    $row(['VAT payable', '£712.80']),
                    $row(['Total liabilities', '£4,412.80'], 'subtotal'),
                    $row(['Share capital', '£10,000.00']),
                    $row(['Retained earnings brought forward', '£25,500.00']),
                    $row(['Profit for the period', '£3,226.00']),
                    $row(['Total equity', '£38,726.00'], 'subtotal'),
                    $row(['Total equity and liabilities', '£43,138.80'], 'total'),
                    $row(['Balance check', '£0.00'], 'total'),
                ],
            ],
        ]],
    ],
    [
        'filename' => '12-trial-balance.pdf',
        'title' => 'Trial Balance',
        'subtitle' => 'Closing balances at 30 September 2026',
        'classification' => 'TARGET PREVIEW',
        'badge_tone' => 'preview',
        'metadata' => $metadata('TB-2026-09-30', 'As at 30 Sep 2026'),
        'intro' => 'Closing debit and credit balances before transferring the quarter profit into retained earnings.',
        'sections' => [[
            'heading' => 'Trial Balance', 'allow_break' => true, 'table' => [
                'headers' => ['Code', 'Account', 'Debit', 'Credit'], 'right_columns' => [2, 3], 'rows' => [
                    $row(['1200', 'Bank', '£24,212.00', '']),
                    $row(['1100', 'Trade debtors', '£7,676.80', '']),
                    $row(['1500', 'Computer equipment at cost', '£15,000.00', '']),
                    $row(['1510', 'Accumulated depreciation', '', '£3,750.00']),
                    $row(['2100', 'Trade creditors', '', '£3,700.00']),
                    $row(['2200', 'VAT control', '', '£712.80']),
                    $row(['3000', 'Share capital', '', '£10,000.00']),
                    $row(['3100', 'Retained earnings brought forward', '', '£25,500.00']),
                    $row(['4000', 'Sales revenue', '', '£10,064.00']),
                    $row(['4050', 'Sales returns', '£500.00', '']),
                    $row(['5000', 'Cloud hosting', '£1,000.00', '']),
                    $row(['5100', 'Office rent', '£2,400.00', '']),
                    $row(['5200', 'Travel', '£200.00', '']),
                    $row(['5300', 'Subcontractors', '£2,000.00', '']),
                    $row(['5400', 'Depreciation', '£750.00', '']),
                    $row(['7000', 'Bank interest received', '', '£12.00']),
                    $row(['Totals', '', '£53,738.80', '£53,738.80'], 'total'),
                    $row(['Difference', '', '£0.00', ''], 'total'),
                ],
            ],
        ]],
    ],
    [
        'filename' => '13-general-ledger.pdf',
        'title' => 'General Ledger',
        'subtitle' => 'Selected account detail',
        'classification' => 'TARGET PREVIEW',
        'badge_tone' => 'preview',
        'orientation' => 'landscape',
        'metadata' => $metadata('GL-1200-2026-Q3'),
        'intro' => 'Selected entries from bank account 1200. Production output will support account/date filters and immutable journal drill-down.',
        'sections' => [[
            'heading' => '1200 — Bank', 'allow_break' => true, 'table' => [
                'headers' => ['Date', 'Journal', 'Source', 'Description', 'Debit', 'Credit', 'Running balance'], 'right_columns' => [4, 5, 6], 'rows' => [
                    $row(['01 Jul', 'OPEN-2026', 'Opening', 'Balance brought forward', '£25,000.00', '', '£25,000.00 Dr']),
                    $row(['18 Jul', 'JRN-1008', 'Receipt', 'Northstar — INV-2026-0101', '£7,200.00', '', '£32,200.00 Dr']),
                    $row(['25 Jul', 'JRN-1014', 'Supplier payment', 'Cloud Forge — CF-88421', '', '£1,440.00', '£30,760.00 Dr']),
                    $row(['27 Jul', 'JRN-1017', 'Supplier refund', 'Cloud Forge — CF-CN-1198', '£240.00', '', '£31,000.00 Dr']),
                    $row(['01 Aug', 'JRN-1020', 'Supplier payment', 'Quarterly office rent', '', '£2,400.00', '£28,600.00 Dr']),
                    $row(['04 Aug', 'JRN-1023', 'Supplier payment', 'Computer equipment', '', '£3,600.00', '£25,000.00 Dr']),
                    $row(['18 Aug', 'JRN-1031', 'Supplier payment', 'Rail travel', '', '£200.00', '£24,800.00 Dr']),
                    $row(['20 Aug', 'JRN-1034', 'Customer refund', 'CN-2026-0003', '', '£600.00', '£24,200.00 Dr']),
                    $row(['20 Sep', 'JRN-1052', 'Receipt', 'Beacon Arts — INV-2026-0103', '£1,200.00', '', '£25,400.00 Dr']),
                    $row(['25 Sep', 'JRN-1058', 'Supplier payment', 'Studio Partner — SUB-4401', '', '£1,200.00', '£24,200.00 Dr']),
                    $row(['30 Sep', 'JRN-1062', 'Interest', 'Bank interest', '£12.00', '', '£24,212.00 Dr']),
                    $row(['Closing balance', '', '', '', '£8,652.00', '£9,440.00', '£24,212.00 Dr'], 'total'),
                ],
            ],
        ]],
    ],
    [
        'filename' => '14-accounts-receivable-ageing.pdf',
        'title' => 'Accounts Receivable Ageing',
        'subtitle' => 'Customer balances at 30 September 2026',
        'classification' => 'TARGET PREVIEW',
        'badge_tone' => 'preview',
        'metadata' => $metadata('AR-AGE-2026-09-30', 'As at 30 Sep 2026'),
        'sections' => [[
            'heading' => 'Receivables by customer', 'table' => [
                'headers' => ['Customer', 'Current', '1–30 days', '31–60 days', '61+ days', 'Total'], 'right_columns' => [1, 2, 3, 4, 5], 'rows' => [
                    $row(['Northstar Retail Ltd', '£0.00', '£2,476.80', '£0.00', '£0.00', '£2,476.80']),
                    $row(['Beacon Arts CIC', '£1,200.00', '£0.00', '£0.00', '£0.00', '£1,200.00']),
                    $row(['Opening-balance customers', '£0.00', '£0.00', '£4,000.00', '£0.00', '£4,000.00']),
                    $row(['Total receivables', '£1,200.00', '£2,476.80', '£4,000.00', '£0.00', '£7,676.80'], 'total'),
                ],
            ],
        ]],
        'notes' => ['Confirm ageing basis: invoice date or contractual due date. This preview uses due date.'],
    ],
    [
        'filename' => '15-accounts-payable-ageing.pdf',
        'title' => 'Accounts Payable Ageing',
        'subtitle' => 'Supplier balances at 30 September 2026',
        'classification' => 'TARGET PREVIEW',
        'badge_tone' => 'preview',
        'metadata' => $metadata('AP-AGE-2026-09-30', 'As at 30 Sep 2026'),
        'sections' => [[
            'heading' => 'Payables by supplier', 'table' => [
                'headers' => ['Supplier', 'Current', '1–30 days', '31–60 days', '61+ days', 'Total'], 'right_columns' => [1, 2, 3, 4, 5], 'rows' => [
                    $row(['Studio Partner Ltd', '£1,200.00', '£0.00', '£0.00', '£0.00', '£1,200.00']),
                    $row(['Opening-balance suppliers', '£0.00', '£0.00', '£0.00', '£2,500.00', '£2,500.00']),
                    $row(['Total payables', '£1,200.00', '£0.00', '£0.00', '£2,500.00', '£3,700.00'], 'total'),
                ],
            ],
        ]],
        'notes' => ['Confirm whether disputed and on-hold supplier balances need separate columns.'],
    ],
    [
        'filename' => '16-expenses-by-category.pdf',
        'title' => 'Expenses by Category',
        'subtitle' => 'Net of purchase credits',
        'classification' => 'TARGET PREVIEW',
        'badge_tone' => 'preview',
        'metadata' => $metadata('EXP-2026-Q3'),
        'sections' => [[
            'heading' => 'Expense analysis', 'table' => [
                'headers' => ['Category', 'Net expense', 'Recoverable VAT', 'Gross cash/evidence'], 'right_columns' => [1, 2, 3], 'rows' => [
                    $row(['Cloud hosting (after £200 credit)', '£1,000.00', '£200.00', '£1,200.00']),
                    $row(['Office rent — exempt', '£2,400.00', '£0.00', '£2,400.00']),
                    $row(['Travel — zero-rated', '£200.00', '£0.00', '£200.00']),
                    $row(['Subcontractors', '£2,000.00', '£400.00', '£2,400.00']),
                    $row(['Depreciation — non-cash', '£750.00', '£0.00', '£750.00']),
                    $row(['Operating expense totals', '£6,350.00', '£600.00', '£6,950.00'], 'total'),
                    $row(['Capital equipment (Balance Sheet)', '£3,000.00', '£600.00', '£3,600.00'], 'muted'),
                ],
            ],
        ]],
        'notes' => ['The capital equipment purchase is included in VAT Box 7 but excluded from the P&L expense total.'],
    ],
    [
        'filename' => '17-accounting-period-audit-trail.pdf',
        'title' => 'Period and Audit Trail',
        'subtitle' => 'Evidence of review and immutable posting history',
        'classification' => 'TARGET PREVIEW',
        'badge_tone' => 'preview',
        'orientation' => 'landscape',
        'metadata' => [
            ['label' => 'Period', 'value' => '2026-Q3'],
            ['label' => 'State', 'value' => 'Open (demo)'],
            ['label' => 'Generated', 'value' => '5 Nov 2026'],
            ['label' => 'Currency', 'value' => 'GBP'],
        ],
        'intro' => 'Proposed review extract showing who performed material accounting actions, when they occurred and which immutable source was affected.',
        'sections' => [[
            'heading' => 'Material events', 'allow_break' => true, 'table' => [
                'headers' => ['Timestamp (Europe/London)', 'Actor', 'Action', 'Record', 'Result', 'Audit note'], 'right_columns' => [], 'rows' => [
                    $row(['05 Jul 2026 09:14', 'Finance Manager', 'Invoice issued', 'INV-2026-0101', 'Posted', 'Journal JRN-1001; hash recorded']),
                    $row(['18 Jul 2026 10:03', 'Finance Manager', 'Customer receipt posted', 'RCT-2026-0071', 'Completed', 'Allocated £7,200 to INV-2026-0101']),
                    $row(['20 Jul 2026 15:42', 'Owner', 'Credit note issued', 'CN-2026-0003', 'Allocated', 'Reason: agreed scope adjustment']),
                    $row(['27 Jul 2026 11:08', 'Finance Manager', 'Supplier refund posted', 'CF-CN-1198', 'Completed', 'Bank receipt £240']),
                    $row(['30 Sep 2026 18:05', 'Accountant', 'Trial Balance reviewed', 'TB-2026-09-30', 'Passed', 'Debit = credit £53,738.80']),
                    $row(['05 Nov 2026 12:00', 'System', 'Demo pack generated', 'PACK-2026-Q3', 'Completed', 'Manifest contains SHA-256 for every artefact']),
                ],
            ],
        ]],
        'notes' => ['Production audit events must identify the authenticated actor and must never expose secret values or full bank credentials.'],
    ],
];

$readme = <<<'MARKDOWN'
# UK accountant demonstration pack

This directory contains English-language examples built with company details from the system and fictional transactions for review by a UK accountant. Every PDF is marked **DEMO / NOT FOR SUBMISSION**.

## Company details used

- Legal name: `{{company_name}}`
- Company number: `{{company_number}}`
- VAT number: `{{vat_number}}`
- Registered address: `{{company_address_inline}}`
- Contact: `{{company_email}}` · `{{company_phone}}`
- Source: the system's `Legal Settings`, with `AccountingProfile` as fallback.
- Profile consistency: **{{profile_consistency}}**. Synchronise Legal Settings and AccountingProfile before production invoicing.

## System status

- `01`–`03`: generated from the current WebsiteExpert invoice and customer credit-note templates.
- `04`–`05`: examples of supplier evidence that the system stores privately; WebsiteExpert does not issue these supplier documents.
- `06`–`17`: target previews for accountant review. They do not claim that the corresponding production export is already available.

All documents use the same period (1 July–30 September 2026) and reconcile to the control totals in `demo-dataset.json`. `manifest.sha256.json` records integrity hashes. The ZIP is the convenient hand-off artefact.

## Important boundary

This pack is not tax advice, an official HMRC form or a filing-ready VAT Return. The scenario assumes standard VAT accounting on an accrual basis. Special schemes and sector-specific rules are outside scope until reviewed.

## Official reference basis checked on 5 November 2026

- VAT invoice content: https://www.gov.uk/guidance/vat-guide-notice-700#section16
- General invoice content: https://www.gov.uk/invoicing-and-taking-payment-from-customers/invoices-what-they-must-include
- VAT Return boxes: https://www.gov.uk/guidance/how-to-fill-in-and-submit-your-vat-return-vat-notice-70012
- HMRC VAT (MTD) API: https://developer.service.hmrc.gov.uk/api-documentation/docs/api/service/vat-api/1.0

Please use `accountant-review-checklist.md` to record decisions before VAT mapping and report exports are treated as production-ready.
MARKDOWN;

$reviewChecklist = <<<'MARKDOWN'
# UK accountant review checklist

Reviewer: ____________________  Firm: ____________________  Date: ____________________

## Sales documents

- [ ] Legal name, Companies House number, VAT number and registered address have been checked against official records.
- [ ] Legal Settings and AccountingProfile have been synchronised.
- [ ] Full VAT invoice fields and terminology are acceptable.
- [ ] Company/VAT/address presentation is acceptable.
- [ ] Tax point, issue date and due date are sufficiently clear.
- [ ] Mixed/zero/exempt/reverse-charge wording requirements are identified.
- [ ] Foreign-currency invoice and sterling VAT presentation are acceptable.
- [ ] Customer credit note adequately references and reverses the original invoice.

## Purchases and evidence

- [ ] Supplier invoice/credit-note metadata is sufficient alongside the original attachment.
- [ ] Input VAT evidence and non-recoverable VAT requirements are identified.
- [ ] Capital purchases should be separated from P&L expenses as shown.

## VAT Return

- [ ] Box 1–9 descriptions and rounding presentation are acceptable.
- [ ] Tax-code mappings for standard, zero, exempt, outside-scope and reverse-charge transactions are approved.
- [ ] Required adjustments, partial exemption and special schemes are documented.
- [ ] VAT review/lock/declaration workflow expectations are documented.

## Ledger and reports

- [ ] Trial Balance, P&L and Balance Sheet layouts are acceptable.
- [ ] General Ledger drill-down fields are sufficient.
- [ ] AR/AP ageing should use due date (or note required alternative): ____________________.
- [ ] Bank reconciliation layout and outstanding-item treatment are acceptable.
- [ ] Audit trail fields and period lock/reopen controls are sufficient.

## Required changes / sign-off conditions

1. ____________________________________________________________________________
2. ____________________________________________________________________________
3. ____________________________________________________________________________
4. ____________________________________________________________________________

Outcome: [ ] Accepted  [ ] Accepted with changes  [ ] Rework required
MARKDOWN;

return [
    'control_totals' => [
        'vat_box_1' => 1912.80,
        'vat_box_2' => 0,
        'vat_box_4' => 1200,
        'vat_box_5' => 712.80,
        'trial_balance_debits' => 53738.80,
        'trial_balance_credits' => 53738.80,
        'balance_sheet_assets' => 43138.80,
        'balance_sheet_equity_and_liabilities' => 43138.80,
        'sales' => 9564,
        'expenses' => 6350,
        'other_income' => 12,
        'profit' => 3226,
    ],
    'dataset' => [
        'classification' => 'SYSTEM COMPANY DETAILS / FICTIONAL TRANSACTIONS / NOT FOR SUBMISSION',
        'period' => ['from' => '2026-07-01', 'to' => '2026-09-30'],
        'vat_return' => ['box_1' => 1912.80, 'box_2' => 0, 'box_3' => 1912.80, 'box_4' => 1200, 'box_5' => 712.80, 'box_6' => 9564, 'box_7' => 8600, 'box_8' => 0, 'box_9' => 0],
        'profit_and_loss' => ['net_sales' => 9564, 'operating_expenses' => 6350, 'other_income' => 12, 'profit' => 3226],
        'balance_sheet' => ['assets' => 43138.80, 'liabilities' => 4412.80, 'equity' => 38726, 'equity_and_liabilities' => 43138.80],
        'trial_balance' => ['debits' => 53738.80, 'credits' => 53738.80, 'difference' => 0],
        'bank_reconciliation' => ['statement_balance' => 24362, 'deposits_in_transit' => 450, 'unpresented_payments' => 600, 'ledger_balance' => 24212, 'difference' => 0],
    ],
    'documents' => $documents,
    'readme' => $readme.PHP_EOL,
    'review_checklist' => $reviewChecklist.PHP_EOL,
];
