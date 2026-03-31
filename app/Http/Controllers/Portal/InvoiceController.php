<?php

namespace App\Http\Controllers\Portal;

use App\Models\Invoice;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class InvoiceController extends BasePortalController
{
    public function index(): Response|RedirectResponse
    {
        $client = $this->clientForUser();

        if (! $client) {
            return redirect()->route('portal.dashboard');
        }

        $invoices = Invoice::where('client_id', $client->id)
            ->latest()
            ->get(['id', 'number', 'status', 'total', 'amount_due', 'amount_paid', 'due_date', 'issue_date', 'currency', 'stripe_payment_link']);

        return Inertia::render('Portal/Invoices', [
            'client'   => $client->only('id', 'company_name'),
            'invoices' => $invoices,
        ]);
    }

    public function show(Invoice $invoice): Response|RedirectResponse
    {
        $client = $this->clientForUser();

        if (! $client || $invoice->client_id !== $client->id) {
            abort(403);
        }

        if ($invoice->status === 'draft') {
            abort(403);
        }

        $invoice->load('items');

        return Inertia::render('Portal/Invoice', [
            'client'  => $client->only('id', 'company_name'),
            'invoice' => $invoice,
        ]);
    }
}
