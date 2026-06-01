<?php

namespace App\Http\Controllers\Domain;

use App\Actions\Domain\CreateDomainOrderAction;
use App\Actions\Domain\ProcessDomainPaymentAction;
use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\DomainOrder;
use App\Services\Domain\DomainPricingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;
use Stripe\Exception\ApiErrorException;

class DomainOrderController extends Controller
{
    public function __construct(
        private readonly DomainPricingService       $pricing,
        private readonly CreateDomainOrderAction    $createAction,
        private readonly ProcessDomainPaymentAction $paymentAction,
    ) {}

    /**
     * GET /domains/order?domain=example&tld=.co.uk&action=register
     * Public order form — requires auth.
     */
    public function order(Request $request): Response|RedirectResponse
    {
        $domainName = strtolower(trim($request->input('domain', '')));
        $tld        = strtolower(trim($request->input('tld', '.co.uk')));
        $action     = in_array($request->input('action'), ['register', 'transfer', 'renew'])
            ? $request->input('action')
            : 'register';

        if ($domainName === '') {
            return redirect()->route('domains.check');
        }

        // Pre-compute retail prices for 1–5 years so the form can update dynamically
        $pricesByYear = [];
        for ($y = 1; $y <= 5; $y++) {
            $pricesByYear[$y] = round($this->pricing->calculateRetailPrice($tld, $y, $action) ?? 0, 2);
        }

        // Pre-fill contact details from the linked client record if available
        $client  = Client::forPortalUser(auth()->id())->first();
        $prefill = $client ? [
            'first_name'    => explode(' ', $client->primary_contact_name ?? '')[0] ?? '',
            'last_name'     => ltrim(strstr($client->primary_contact_name ?? '', ' ') ?: ''),
            'email'         => $client->primary_contact_email ?? '',
            'phone'         => $client->primary_contact_phone ?? '',
            'organisation'  => $client->company_name ?? '',
            'address_line1' => $client->address_line1 ?? '',
            'address_line2' => $client->address_line2 ?? '',
            'city'          => $client->city ?? '',
            'county'        => $client->county ?? '',
            'postcode'      => $client->postcode ?? '',
            'country_code'  => $client->country ?? 'GB',
        ] : [
            'email' => auth()->user()?->email ?? '',
        ];

        return Inertia::render('Domains/Order', [
            'domain_name' => $domainName,
            'tld'         => $tld,
            'full_domain' => $domainName . $tld,
            'action'      => $action,
            'prices'      => $pricesByYear,
            'prefill'     => $prefill,
            'auth'        => ['user' => auth()->user()->only('id', 'name')],
        ]);
    }

    /**
     * POST /domains/order — validate form, create DomainOrder + DomainContact, redirect to checkout.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'domain_name'   => ['required', 'string', 'max:63', 'regex:/^[a-z0-9]([a-z0-9\-]{0,61}[a-z0-9])?$/i'],
            'tld'           => ['required', 'string', 'max:20', 'starts_with:.'],
            'years'         => ['required', 'integer', 'min:1', 'max:10'],
            'action'        => ['required', 'in:register,transfer,renew'],
            'first_name'    => ['required', 'string', 'max:100'],
            'last_name'     => ['required', 'string', 'max:100'],
            'email'         => ['required', 'email:rfc', 'max:255'],
            'phone'         => ['nullable', 'string', 'max:20'],
            'organisation'  => ['nullable', 'string', 'max:255'],
            'address_line1' => ['required', 'string', 'max:255'],
            'address_line2' => ['nullable', 'string', 'max:255'],
            'city'          => ['required', 'string', 'max:100'],
            'county'        => ['nullable', 'string', 'max:100'],
            'postcode'      => ['required', 'string', 'max:20'],
            'country_code'  => ['required', 'string', 'size:2'],
            'notes'         => ['nullable', 'string', 'max:1000'],
        ]);

        $client = Client::forPortalUser(auth()->id())->first();
        $order  = $this->createAction->execute($validated, $client);

        return redirect()
            ->route('domains.checkout', $order->id)
            ->with('success', 'Order created. Please complete your payment.');
    }

    /**
     * GET /domains/order/{order}/checkout — payment summary page.
     */
    public function checkout(DomainOrder $order): Response|RedirectResponse
    {
        $this->authorizeOrder($order);

        if ($order->status === 'completed') {
            return redirect()
                ->route('domains.result', $order->id)
                ->with('payment', 'success');
        }

        return Inertia::render('Domains/Checkout', [
            'order' => [
                'id'           => $order->id,
                'full_domain'  => $order->full_domain,
                'action'       => $order->action,
                'years'        => $order->years,
                'retail_price' => (float) $order->retail_price,
                'currency'     => $order->currency,
                'status'       => $order->status,
            ],
            'auth' => ['user' => auth()->user()->only('id', 'name')],
        ]);
    }

    /**
     * POST /domains/order/{order}/checkout — create Stripe Checkout Session and redirect.
     */
    public function pay(Request $request, DomainOrder $order): RedirectResponse
    {
        $this->authorizeOrder($order);

        if ($order->status !== 'pending_payment') {
            return back()->withErrors(['order' => 'This order cannot be paid at this time.']);
        }

        try {
            $session = $this->paymentAction->execute(
                $order,
                route('domains.result', $order->id) . '?payment=success',
                route('domains.result', $order->id) . '?payment=cancelled',
            );

            return redirect($session->url);
        } catch (\RuntimeException $e) {
            abort(503, $e->getMessage());
        } catch (ApiErrorException $e) {
            Log::error('Stripe domain checkout error: ' . $e->getMessage(), ['order_id' => $order->id]);

            return back()->withErrors(['stripe' => 'Payment provider error. Please try again.']);
        }
    }

    /**
     * GET /domains/order/{order}/result?payment=success|cancelled
     */
    public function result(Request $request, DomainOrder $order): Response
    {
        $this->authorizeOrder($order);

        return Inertia::render('Domains/Result', [
            'order' => [
                'id'           => $order->id,
                'full_domain'  => $order->full_domain,
                'action'       => $order->action,
                'years'        => $order->years,
                'retail_price' => (float) $order->retail_price,
                'currency'     => $order->currency,
                'status'       => $order->fresh()->status,
            ],
            'payment' => $request->input('payment', ''),
            'auth'    => ['user' => auth()->user()->only('id', 'name')],
        ]);
    }

    private function authorizeOrder(DomainOrder $order): void
    {
        // Allow owner or admin-created orders (no created_by set)
        if ($order->created_by && $order->created_by !== auth()->id()) {
            abort(403);
        }
    }
}
