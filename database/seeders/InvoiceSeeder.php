<?php

namespace Database\Seeders;

use App\Models\Client;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Payment;
use App\Models\Project;
use App\Models\User;
use Illuminate\Database\Seeder;

class InvoiceSeeder extends Seeder
{
    public function run(): void
    {
        $admin   = User::where('email', 'admin@websiteexpert.co.uk')->first();
        $manager = User::where('email', 'manager@websiteexpert.co.uk')->first();

        $hargreaves = Client::where('company_name', 'Hargreaves & Sons Solicitors')->first();
        $nts        = Client::where('company_name', 'Northern Trade Supplies Ltd')->first();
        $oakfield   = Client::where('company_name', 'Oakfield Dental Practice')->first();
        $pinnacle   = Client::where('company_name', 'Pinnacle Recruitment Group')->first();
        $bloom      = Client::where('company_name', 'Bloom & Grow Garden Centre')->first();
        $coastal    = Client::where('company_name', 'Coastal Escapes Holiday Rentals')->first();

        $projHargreaves = Project::where('title', 'Hargreaves Solicitors – Website Redesign')->first();
        $projNts        = Project::where('title', 'NTS Direct – E-Commerce Platform')->first();
        $projOakfield   = Project::where('title', 'Oakfield Dental – Practice Website')->first();

        $invoices = [
            // -------------------------------------------------------
            // Hargreaves – Paid in full
            // -------------------------------------------------------
            [
                'number'          => 'INV-2024-001',
                'client_id'       => $hargreaves?->id,
                'project_id'      => $projHargreaves?->id,
                'created_by'      => $manager?->id,
                'status'          => 'paid',
                'currency'        => 'GBP',
                'discount_amount' => 0.00,
                'vat_rate'        => 20.00,
                'notes'           => 'Deposit invoice — 50% of agreed project fee.',
                'terms'           => 'Payment due within 14 days.',
                'issue_date'      => now()->subMonths(5)->toDateString(),
                'due_date'        => now()->subMonths(5)->addDays(14)->toDateString(),
                'sent_at'         => now()->subMonths(5),
                'paid_at'         => now()->subMonths(5)->addDays(5),
                'items' => [
                    ['description' => 'Website Redesign – 50% Deposit', 'quantity' => 1, 'unit_price' => 1900.00, 'order' => 1],
                ],
                'payment' => ['amount' => 2280.00, 'method' => 'bank_transfer', 'reference' => 'BACS-HGS-001'],
            ],
            [
                'number'          => 'INV-2024-002',
                'client_id'       => $hargreaves?->id,
                'project_id'      => $projHargreaves?->id,
                'created_by'      => $manager?->id,
                'status'          => 'paid',
                'currency'        => 'GBP',
                'discount_amount' => 0.00,
                'vat_rate'        => 20.00,
                'notes'           => 'Final invoice — 50% balance on project completion.',
                'terms'           => 'Payment due within 14 days.',
                'issue_date'      => now()->subMonths(3)->toDateString(),
                'due_date'        => now()->subMonths(3)->addDays(14)->toDateString(),
                'sent_at'         => now()->subMonths(3),
                'paid_at'         => now()->subMonths(3)->addDays(8),
                'items' => [
                    ['description' => 'Website Redesign – Final Balance', 'quantity' => 1, 'unit_price' => 1900.00, 'order' => 1],
                ],
                'payment' => ['amount' => 2280.00, 'method' => 'bank_transfer', 'reference' => 'BACS-HGS-002'],
            ],
            // -------------------------------------------------------
            // NTS – 50% deposit paid, milestone invoice overdue
            // -------------------------------------------------------
            [
                'number'          => 'INV-2024-003',
                'client_id'       => $nts?->id,
                'project_id'      => $projNts?->id,
                'created_by'      => $manager?->id,
                'status'          => 'paid',
                'currency'        => 'GBP',
                'discount_amount' => 300.00,
                'vat_rate'        => 20.00,
                'notes'           => 'Project deposit — 50% of total project fee.',
                'terms'           => 'Payment due within 7 days of invoice.',
                'issue_date'      => now()->subMonths(2)->toDateString(),
                'due_date'        => now()->subMonths(2)->addDays(7)->toDateString(),
                'sent_at'         => now()->subMonths(2),
                'paid_at'         => now()->subMonths(2)->addDays(6),
                'items' => [
                    ['description' => 'E-Commerce Platform – 50% Project Deposit', 'quantity' => 1, 'unit_price' => 6250.00, 'order' => 1],
                ],
                'payment' => ['amount' => 7140.00, 'method' => 'bank_transfer', 'reference' => 'BACS-NTS-001'],
            ],
            [
                'number'          => 'INV-2024-004',
                'client_id'       => $nts?->id,
                'project_id'      => $projNts?->id,
                'created_by'      => $manager?->id,
                'status'          => 'overdue',
                'currency'        => 'GBP',
                'discount_amount' => 0.00,
                'vat_rate'        => 20.00,
                'notes'           => 'Milestone billing: Design & Development phase completion.',
                'terms'           => 'Payment due within 14 days.',
                'issue_date'      => now()->subDays(45)->toDateString(),
                'due_date'        => now()->subDays(31)->toDateString(),
                'sent_at'         => now()->subDays(45),
                'items' => [
                    ['description' => 'Design Phase Completion',    'quantity' => 1, 'unit_price' => 2000.00, 'order' => 1],
                    ['description' => 'Development – Week 1–4',     'quantity' => 1, 'unit_price' => 1800.00, 'order' => 2],
                ],
            ],
            // -------------------------------------------------------
            // Oakfield – First invoice sent (awaiting payment)
            // -------------------------------------------------------
            [
                'number'          => 'INV-2025-001',
                'client_id'       => $oakfield?->id,
                'project_id'      => $projOakfield?->id,
                'created_by'      => $manager?->id,
                'status'          => 'sent',
                'currency'        => 'GBP',
                'discount_amount' => 0.00,
                'vat_rate'        => 20.00,
                'notes'           => 'Deposit invoice — project commencement.',
                'terms'           => 'Payment due within 14 days.',
                'issue_date'      => now()->subWeeks(4)->toDateString(),
                'due_date'        => now()->subWeeks(2)->toDateString(),
                'sent_at'         => now()->subWeeks(4),
                'items' => [
                    ['description' => 'Dental Website – 50% Deposit', 'quantity' => 1, 'unit_price' => 1100.00, 'order' => 1],
                ],
            ],
            // -------------------------------------------------------
            // Pinnacle – Draft invoice
            // -------------------------------------------------------
            [
                'number'          => 'INV-2025-002',
                'client_id'       => $pinnacle?->id,
                'project_id'      => null,
                'created_by'      => $admin?->id,
                'status'          => 'draft',
                'currency'        => 'GBP',
                'discount_amount' => 0.00,
                'vat_rate'        => 20.00,
                'notes'           => 'Draft — pending quote acceptance before sending.',
                'terms'           => '40% on project start, 40% at beta, 20% on launch.',
                'issue_date'      => now()->toDateString(),
                'due_date'        => now()->addDays(14)->toDateString(),
                'items' => [
                    ['description' => 'Job Board Application – 40% Deposit', 'quantity' => 1, 'unit_price' => 2600.00, 'order' => 1],
                ],
            ],
            // -------------------------------------------------------
            // Coastal – Paid in full (completed project)
            // -------------------------------------------------------
            [
                'number'          => 'INV-2024-005',
                'client_id'       => $coastal?->id,
                'project_id'      => null,
                'created_by'      => $manager?->id,
                'status'          => 'paid',
                'currency'        => 'GBP',
                'discount_amount' => 0.00,
                'vat_rate'        => 20.00,
                'notes'           => 'Full project invoice — static brochure site.',
                'terms'           => 'Paid on completion.',
                'issue_date'      => now()->subMonths(2)->toDateString(),
                'due_date'        => now()->subMonths(2)->addDays(14)->toDateString(),
                'sent_at'         => now()->subMonths(2),
                'paid_at'         => now()->subMonths(2)->addDays(3),
                'items' => [
                    ['description' => 'Brochure Website (5 pages) – Full',     'quantity' => 1, 'unit_price' => 1500.00, 'order' => 1],
                    ['description' => '12 Months SSL & Managed Hosting',        'quantity' => 1, 'unit_price' => 150.00,  'order' => 2],
                ],
                'payment' => ['amount' => 1980.00, 'method' => 'stripe', 'reference' => 'STRIPE-CE-001'],
            ],
            // -------------------------------------------------------
            // Bloom – Overdue (sent, not paid)
            // -------------------------------------------------------
            [
                'number'          => 'INV-2025-003',
                'client_id'       => $bloom?->id,
                'project_id'      => null,
                'created_by'      => $manager?->id,
                'status'          => 'overdue',
                'currency'        => 'GBP',
                'discount_amount' => 0.00,
                'vat_rate'        => 20.00,
                'notes'           => 'Retainer invoice – ongoing maintenance and hosting.',
                'terms'           => 'Monthly retainer due on the 1st of each month.',
                'issue_date'      => now()->subDays(35)->toDateString(),
                'due_date'        => now()->subDays(5)->toDateString(),
                'sent_at'         => now()->subDays(35),
                'items' => [
                    ['description' => 'Monthly Website Maintenance Retainer – March 2025', 'quantity' => 1, 'unit_price' => 250.00, 'order' => 1],
                    ['description' => 'Managed WordPress Hosting – March 2025',            'quantity' => 1, 'unit_price' => 40.00,  'order' => 2],
                ],
            ],
        ];

        foreach ($invoices as $invoiceData) {
            $items   = $invoiceData['items'];
            $payment = $invoiceData['payment'] ?? null;
            unset($invoiceData['items'], $invoiceData['payment']);

            $invoice = Invoice::firstOrCreate(
                ['number' => $invoiceData['number']],
                array_merge($invoiceData, ['subtotal' => 0, 'vat_amount' => 0, 'total' => 0, 'amount_paid' => 0, 'amount_due' => 0])
            );

            foreach ($items as $itemData) {
                $itemData['invoice_id'] = $invoice->id;
                $itemData['amount']     = $itemData['quantity'] * $itemData['unit_price'];

                InvoiceItem::firstOrCreate(
                    ['invoice_id' => $invoice->id, 'order' => $itemData['order']],
                    $itemData
                );
            }

            // Recalculate
            $subtotal = $invoice->items()->sum('amount');
            $discount = $invoiceData['discount_amount'] ?? 0;
            $vatRate  = $invoiceData['vat_rate'] ?? 20;
            $vat      = round(($subtotal - $discount) * ($vatRate / 100), 2);
            $total    = $subtotal - $discount + $vat;

            $amountPaid = 0;
            if ($payment) {
                $paymentRecord = Payment::firstOrCreate(
                    ['invoice_id' => $invoice->id, 'reference' => $payment['reference']],
                    [
                        'invoice_id' => $invoice->id,
                        'amount'     => $payment['amount'],
                        'currency'   => $invoiceData['currency'],
                        'method'     => $payment['method'],
                        'reference'  => $payment['reference'],
                        'status'     => 'completed',
                        'paid_at'    => $invoiceData['paid_at'] ?? now(),
                    ]
                );
                $amountPaid = $payment['amount'];
            }

            $invoice->update([
                'subtotal'    => $subtotal,
                'vat_amount'  => $vat,
                'total'       => $total,
                'amount_paid' => $amountPaid,
                'amount_due'  => max(0, $total - $amountPaid),
            ]);
        }
    }
}
