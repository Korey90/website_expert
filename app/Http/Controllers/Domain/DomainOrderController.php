<?php

namespace App\Http\Controllers\Domain;

use App\Actions\Domain\CreateDomainOrderAction;
use App\Actions\Domain\ProcessDomainPaymentAction;
use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\DomainOrder;
use App\Models\Setting;
use App\Services\Domain\DomainOrderService;
use App\Services\Domain\DomainPricingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;
use Stripe\Checkout\Session as StripeSession;
use Stripe\Exception\ApiErrorException;
use Stripe\Stripe;

class DomainOrderController extends Controller
{
    public function __construct(
        private readonly DomainPricingService $pricing,
        private readonly CreateDomainOrderAction $createAction,
        private readonly ProcessDomainPaymentAction $paymentAction,
    ) {}

    /**
     * GET /domains/order?domain=example&tld=.co.uk&action=register
     * Public order form — requires auth.
     */
    public function order(Request $request): Response|RedirectResponse
    {
        $domainName = strtolower(trim($request->input('domain', '')));
        $tld = strtolower(trim($request->input('tld', '.co.uk')));
        $action = in_array($request->input('action'), ['register', 'transfer', 'renew'])
            ? $request->input('action')
            : 'register';

        if ($domainName === '') {
            return redirect()->route('domains.check');
        }

        // Pre-compute retail prices for 1–5 years so the form can update dynamically
        $priceSnapshot = $this->pricing->getPriceForTld($tld);
        $currency = $priceSnapshot?->currency ?? $this->pricing->resolveCurrency();
        $pricesByYear = [];
        for ($y = 1; $y <= 5; $y++) {
            $pricesByYear[$y] = round($this->pricing->calculateRetailPrice($tld, $y, $action, $currency) ?? 0, 2);
        }

        // Pre-fill contact details from the linked client record if available
        $client = Client::forPortalUser(auth()->id())->first();
        $prefill = $client ? [
            'first_name' => explode(' ', $client->primary_contact_name ?? '')[0] ?? '',
            'last_name' => ltrim(strstr($client->primary_contact_name ?? '', ' ') ?: ''),
            'email' => $client->primary_contact_email ?? '',
            'phone' => $client->primary_contact_phone ?? '',
            'organisation' => $client->company_name ?? '',
            'address_line1' => $client->address_line1 ?? '',
            'address_line2' => $client->address_line2 ?? '',
            'city' => $client->city ?? '',
            'county' => $client->county ?? '',
            'postcode' => $client->postcode ?? '',
            'country_code' => $client->country ?? 'GB',
        ] : [
            'email' => auth()->user()?->email ?? '',
        ];

        return Inertia::render('Domains/Order', [
            'domain_name' => $domainName,
            'tld' => $tld,
            'full_domain' => $domainName.$tld,
            'action' => $action,
            'prices' => $pricesByYear,
            'currency' => $currency,
            'prefill' => $prefill,
            'auth' => ['user' => auth()->user()->only('id', 'name')],
        ]);
    }

    /**
     * POST /domains/order — validate form, create DomainOrder + DomainContact, redirect to checkout.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'domain_name' => ['required', 'string', 'max:63', 'regex:/^[a-z0-9]([a-z0-9\-]{0,61}[a-z0-9])?$/i'],
            'tld' => ['required', 'string', 'max:20', 'starts_with:.'],
            'years' => ['required', 'integer', 'min:1', 'max:10'],
            'action' => ['required', 'in:register,transfer,renew'],
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email:rfc', 'max:255'],
            'phone' => ['nullable', 'string', 'max:20'],
            'organisation' => ['nullable', 'string', 'max:255'],
            'address_line1' => ['required', 'string', 'max:255'],
            'address_line2' => ['nullable', 'string', 'max:255'],
            'city' => ['required', 'string', 'max:100'],
            'county' => ['nullable', 'string', 'max:100'],
            'postcode' => ['required', 'string', 'max:20'],
            'country_code' => ['required', 'string', 'size:2'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $client = Client::forPortalUser(auth()->id())->first();
        $order = $this->createAction->execute($validated, $client);

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

        $netPrice = (float) $order->retail_price;
        $vatRate = 20.0;
        $vatAmount = round($netPrice * $vatRate / 100, 2);
        $total = round($netPrice + $vatAmount, 2);

        return Inertia::render('Domains/Checkout', [
            'order' => [
                'id' => $order->id,
                'full_domain' => $order->full_domain,
                'action' => $order->action,
                'years' => $order->years,
                'retail_price' => $netPrice,
                'vat_rate' => $vatRate,
                'vat_amount' => $vatAmount,
                'total' => $total,
                'currency' => $order->currency,
                'status' => $order->status,
            ],
            'auth' => ['user' => auth()->user()->only('id', 'name')],
        ]);
    }

    /**
     * POST /domains/order/{order}/checkout — create Stripe Checkout Session and redirect.
     */
    public function pay(Request $request, DomainOrder $order): RedirectResponse|\Symfony\Component\HttpFoundation\Response
    {
        Log::info("Initiating payment for domain order #{$order->id} by user ".auth()->id());
        $this->authorizeOrder($order);

        Log::info("Domain order #{$order->id} current status: '{$order->status}'");
        if ($order->status !== 'pending_payment') {
            return back()->withErrors(['order' => 'This order cannot be paid at this time.']);
        }

        Log::info("Creating Stripe Checkout Session for domain order #{$order->id}");

        try {
            Log::info("Stripe configuration check for domain order #{$order->id}");
            $session = $this->paymentAction->execute(
                $order,
                route('domains.result', $order->id).'?payment=success',
                route('domains.result', $order->id).'?payment=cancelled',
            );

            Log::info("Stripe Checkout Session created for domain order #{$order->id}: session_id={$session->id} url={$session->url}");

            return Inertia::location($session->url);
        } catch (\RuntimeException $e) {
            abort(503, $e->getMessage());
        } catch (ApiErrorException $e) {
            Log::error('Stripe domain checkout error: '.$e->getMessage(), ['order_id' => $order->id]);

            return back()->withErrors(['stripe' => 'Payment provider error. Please try again.']);
        }
    }

    /**
     * GET /domains/order/{order}/result?payment=success|cancelled
     */
    public function result(Request $request, DomainOrder $order): Response
    {
        $this->authorizeOrder($order);

        // Fallback: webhook may not have arrived (local dev / delayed delivery).
        // Verify the Stripe session directly and process the order if still pending.
        if ($request->input('payment') === 'success'
            && $order->status === 'pending_payment'
            && $request->filled('session_id')
        ) {
            $this->verifyAndProcess($order, $request->input('session_id'));
            $order->refresh();
        }

        return Inertia::render('Domains/Result', [
            'order' => [
                'id' => $order->id,
                'full_domain' => $order->full_domain,
                'action' => $order->action,
                'years' => $order->years,
                'retail_price' => (float) $order->retail_price,
                'currency' => $order->currency,
                'status' => $order->fresh()->status,
            ],
            'payment' => $request->input('payment', ''),
            'auth' => ['user' => auth()->user()->only('id', 'name')],
        ]);
    }

    private function verifyAndProcess(DomainOrder $order, string $sessionId): void
    {
        try {
            $stripeSecret = config('services.stripe.secret') ?: Setting::get('stripe_sk', '');
            if (empty($stripeSecret)) {
                return;
            }

            Stripe::setApiKey($stripeSecret);
            $session = StripeSession::retrieve($sessionId);

            Log::info("Verifying Stripe session {$sessionId} for domain order #{$order->id}: payment_status='{$session->payment_status}' metadata=".json_encode($session->metadata));

            if ($session->payment_status === 'paid'
                && (string) ($session->metadata->domain_order_id ?? '') === (string) $order->id
            ) {
                app(DomainOrderService::class)->markAsPaid(
                    $order,
                    $session->payment_intent ?? $session->id,
                );
                Log::info("Domain order #{$order->id} processed via success-URL session verification.");
            }
            Log::info("Stripe session verification completed for domain order #{$order->id}.");
        } catch (\Throwable $e) {
            Log::warning("Domain order #{$order->id} session verification failed: ".$e->getMessage());
        }
    }

    private function authorizeOrder(DomainOrder $order): void
    {
        Log::info("Authorizing access to domain order #{$order->id} for user ".auth()->id());
        // Allow owner or admin-created orders (no created_by set)
        if ($order->created_by && $order->created_by !== auth()->id()) {
            abort(403);
        }
    }
}
