<?php

namespace App\Http\Controllers\Domain;

use App\Actions\Domain\CheckDomainAvailabilityAction;
use App\Http\Controllers\Controller;
use App\Models\SiteSection;
use App\Services\Domain\DomainPricingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PublicDomainController extends Controller
{
    public function __construct(
        private readonly DomainPricingService          $pricing,
        private readonly CheckDomainAvailabilityAction $checkAction,
    ) {}

    /**
     * GET /domains — domain registration landing page with TLD prices.
     */
    public function index(): Response
    {
        $prices = $this->pricing->getAllActivePrices()
            ->map(fn ($p) => [
                'tld'            => $p->tld,
                'register_price' => (float) $p->registerPrice,
                'renew_price'    => (float) $p->renewPrice,
                'currency'       => $p->currency,
            ])
            ->values()
            ->all();

        $footer = ($s = SiteSection::where('key', 'footer')->where('is_active', true)->first())
            ? ['extra' => $s->extra]
            : null;

        return Inertia::render('Domains/Index', [
            'prices'  => $prices,
            'auth'    => auth()->check() ? ['user' => auth()->user()->only('id', 'name')] : null,
            'footer'  => $footer,
        ]);
    }

    /**
     * GET /domains/check?q=example — domain availability check results.
     */
    public function check(Request $request): Response
    {
        $query   = trim($request->input('q', ''));
        $results = $query !== '' ? $this->checkAction->execute($query) : [];

        $footer = ($s = SiteSection::where('key', 'footer')->where('is_active', true)->first())
            ? ['extra' => $s->extra]
            : null;

        return Inertia::render('Domains/Check', [
            'query'   => $query,
            'results' => $results,
            'auth'    => auth()->check() ? ['user' => auth()->user()->only('id', 'name')] : null,
            'footer'  => $footer,
        ]);
    }

    /**
     * GET /domains/availability?q=example.co.uk — JSON availability check for AJAX callers (e.g. calculator).
     */
    public function availability(Request $request): JsonResponse
    {
        $query = trim($request->input('q', ''));

        if ($query === '' || mb_strlen($query) < 2) {
            return response()->json(['results' => []]);
        }

        try {
            $results = $this->checkAction->execute($query);
        } catch (\Throwable) {
            $results = [];
        }

        return response()->json(['results' => $results]);
    }
}

