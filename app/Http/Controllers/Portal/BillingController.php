<?php

namespace App\Http\Controllers\Portal;

use App\Models\Business;
use App\Services\Billing\PlanService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;
use Stripe\Checkout\Session as StripeSession;
use Stripe\Exception\ApiErrorException;
use Stripe\Stripe;

class BillingController extends BasePortalController
{
    public function __construct(
        private readonly PlanService $planService,
    ) {}

    /**
     * GET /portal/billing
     * Show current plan, usage, and upgrade options.
     */
    public function index(): Response|RedirectResponse
    {
        $client   = $this->clientForUser();
        $business = currentBusiness();

        if (! $business) {
            return $this->redirectWithoutWorkspace('Workspace access is required for billing and plan management.');
        }

        $plans = collect(PlanService::getPlans())->map(fn ($limits, $key) => [
            'key'           => $key,
            'name'          => $limits['name'],
            'price'         => (int) round(($limits['price'] ?? 0) / 100), // pence → pounds
            'currency'      => 'GBP',
            'landing_pages' => ($limits['landing_pages'] ?? PHP_INT_MAX) >= PHP_INT_MAX ? null : $limits['landing_pages'],
            'ai_per_month'  => ($limits['ai_per_month'] ?? PHP_INT_MAX) >= PHP_INT_MAX ? null : $limits['ai_per_month'],
        ])->values()->all();

        return Inertia::render('Portal/Billing/Index', [
            'client'   => $client?->only('id', 'company_name'),
            'business' => [
                'id'              => $business->id,
                'name'            => $business->name,
                'plan'            => $business->plan,
                'trial_ends_at'   => $business->trial_ends_at?->toIso8601String(),
                'trial_remaining' => $this->planService->getTrialDaysRemaining($business),
                'on_trial'        => $this->planService->isOnTrial($business),
                'effective_plan'  => $this->planService->getEffectivePlan($business),
                'has_stripe'      => (bool) $business->stripe_customer_id,
            ],
            'plans'                => $plans,
            'ai_used'              => $this->planService->getCurrentMonthAiCount($business),
            'ai_limit'             => $this->planService->getAiGenerationLimit($business),
            'ai_remaining'         => $this->planService->getRemainingAiGenerations($business),
            'lp_count'             => $this->planService->getCurrentLandingPageCount($business),
            'lp_limit'             => $this->planService->getLandingPageLimit($business),
            'can_create_lp'        => $this->planService->canCreateLandingPage($business),
            'can_use_ai'           => $this->planService->canUseAiGenerator($business),
        ]);
    }

    /**
     * POST /portal/billing/checkout/{plan}
     * Initiate Stripe Checkout for plan upgrade.
     */
    public function checkout(Request $request, string $plan): RedirectResponse
    {
        $business = currentBusiness();

        if (! $business) {
            return $this->redirectWithoutWorkspace('Workspace access is required for billing and plan management.');
        }

        $priceId = match ($plan) {
            'pro'    => config('services.stripe.price_pro_monthly'),
            'agency' => config('services.stripe.price_agency_monthly'),
            default  => null,
        };

        if (! $priceId) {
            return back()->withErrors(['plan' => 'Invalid plan selected.']);
        }

        try {
            Stripe::setApiKey(config('services.stripe.secret'));

            $session = StripeSession::create([
                'payment_method_types' => ['card'],
                'mode'                 => 'subscription',
                'customer_email'       => auth()->user()->email,
                'client_reference_id'  => $business->id,
                'line_items'           => [[
                    'price'    => $priceId,
                    'quantity' => 1,
                ]],
                'metadata' => [
                    'business_id' => $business->id,
                    'plan'        => $plan,
                ],
                'success_url' => route('portal.billing.success') . '?session_id={CHECKOUT_SESSION_ID}',
                'cancel_url'  => route('portal.billing'),
            ]);

            return redirect($session->url);
        } catch (ApiErrorException $e) {
            Log::error('Stripe Checkout error: ' . $e->getMessage(), ['business_id' => $business->id]);
            return back()->withErrors(['stripe' => 'Payment provider error. Please try again.']);
        }
    }

    /**
     * GET /portal/billing/success
     * Handle successful Stripe Checkout redirect.
     */
    public function success(Request $request): Response|RedirectResponse
    {
        $client   = $this->clientForUser();
        $business = currentBusiness();

        if (! $business) {
            return $this->redirectWithoutWorkspace('Workspace access is required for billing and plan management.');
        }

        return Inertia::render('Portal/Billing/Success', [
            'client'   => $client?->only('id', 'company_name'),
            'business' => $business->only('id', 'name', 'plan'),
        ]);
    }

    /**
     * POST /portal/billing/portal
     * Redirect to Stripe Customer Portal for subscription management.
     */
    public function portal(): RedirectResponse
    {
        $business = currentBusiness();

        if (! $business) {
            return $this->redirectWithoutWorkspace('Workspace access is required for billing and plan management.');
        }

        if (! $business->stripe_customer_id) {
            return back()->withErrors(['stripe' => 'No active subscription found.']);
        }

        try {
            Stripe::setApiKey(config('services.stripe.secret'));

            $session = \Stripe\BillingPortal\Session::create([
                'customer'   => $business->stripe_customer_id,
                'return_url' => route('portal.billing'),
            ]);

            return redirect($session->url);
        } catch (ApiErrorException $e) {
            Log::error('Stripe Portal error: ' . $e->getMessage());
            return back()->withErrors(['stripe' => 'Could not open billing portal.']);
        }
    }

}
