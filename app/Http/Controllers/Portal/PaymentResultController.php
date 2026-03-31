<?php

namespace App\Http\Controllers\Portal;

use App\Models\Invoice;
use App\Models\Setting;
use App\Services\PayuService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;

class PaymentResultController extends BasePortalController
{
    public function show(Invoice $invoice, Request $request): Response|RedirectResponse
    {
        $client = $this->clientForUser();

        if (! $client || $invoice->client_id !== $client->id) {
            abort(403);
        }

        $status = $request->query('payment');

        if ($status === 'success') {
            return Inertia::render('Portal/PaymentResult', [
                'client'    => $client->only('id', 'company_name'),
                'invoice'   => $invoice->only('id', 'number', 'total', 'currency'),
                'success'   => true,
                'errorCode' => null,
            ]);
        }

        return Inertia::render('Portal/PaymentResult', [
            'client'    => $client->only('id', 'company_name'),
            'invoice'   => $invoice->only('id', 'number', 'total', 'currency'),
            'success'   => false,
            'errorCode' => $request->query('error'),
        ]);
    }
}
