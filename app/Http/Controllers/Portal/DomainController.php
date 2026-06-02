<?php

namespace App\Http\Controllers\Portal;

use App\Actions\Domain\UpdateNameserversAction;
use App\Models\Domain;
use App\Services\Domain\DomainPricingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DomainController extends BasePortalController
{
    public function __construct(
        private readonly DomainPricingService $pricing,
    ) {}

    /**
     * GET /portal/domains — list domains belonging to the authenticated client.
     */
    public function index(): Response
    {
        $client  = $this->clientForUser();
        $domains = $client
            ? Domain::forClient($client->id)
                ->orderByRaw(
                    "CASE WHEN status = 'active' AND expires_at IS NOT NULL AND expires_at <= ? THEN 0 ELSE 1 END",
                    [now()->addDays(30)]
                )
                ->orderBy('expires_at')
                ->get()
                ->map(fn (Domain $d) => [
                    'id'            => $d->id,
                    'full_domain'   => $d->full_domain,
                    'status'        => $d->status,
                    'expires_at'    => $d->expires_at?->toDateString(),
                    'registered_at' => $d->registered_at?->toDateString(),
                    'auto_renew'    => $d->auto_renew,
                    'whois_privacy' => $d->whois_privacy,
                ])
            : [];

        return Inertia::render('Portal/Domains/Index', [
            'client'  => $client?->only('id', 'company_name', 'primary_contact_name'),
            'domains' => $domains,
        ]);
    }

    /**
     * GET /portal/domains/{domain} — detail page for a single domain.
     */
    public function show(Domain $domain): Response
    {
        $client = $this->clientForUser();
        abort_if(! $client || $domain->client_id !== $client->id, 403);

        $renewals = $domain->renewals()
            ->limit(5)
            ->get()
            ->map(fn ($r) => [
                'id'       => $r->id,
                'due_date' => $r->due_date?->toDateString(),
                'years'    => $r->years,
                'amount'   => (float) $r->amount,
                'currency' => $r->currency,
                'status'   => $r->status,
            ]);

        return Inertia::render('Portal/Domains/Show', [
            'client'   => $client->only('id', 'company_name', 'primary_contact_name'),
            'domain'   => [
                'id'            => $domain->id,
                'full_domain'   => $domain->full_domain,
                'name'          => $domain->name,
                'tld'           => $domain->tld,
                'status'        => $domain->status,
                'expires_at'    => $domain->expires_at?->toDateString(),
                'registered_at' => $domain->registered_at?->toDateString(),
                'auto_renew'    => $domain->auto_renew,
                'whois_privacy' => $domain->whois_privacy,
                'nameservers'   => $domain->nameservers ?? [],
                'provider'      => $domain->provider,
            ],
            'renewals' => $renewals,
        ]);
    }

    public function updateNameservers(Domain $domain, Request $request): RedirectResponse
    {
        $client = $this->clientForUser();
        abort_if(! $client || $domain->client_id !== $client->id, 403);

        $validated = $request->validate([
            'nameservers'   => ['required', 'array', 'min:1', 'max:5'],
            'nameservers.*' => ['required', 'string', 'max:255',
                'regex:/^[a-zA-Z0-9]([a-zA-Z0-9\-]{0,61}[a-zA-Z0-9])?(\.[a-zA-Z0-9]([a-zA-Z0-9\-]{0,61}[a-zA-Z0-9])?)*$/'],
        ]);

        app(UpdateNameserversAction::class)->execute($domain, $validated['nameservers']);

        return redirect()->back()->with('success', 'Nameservers updated successfully.');
    }

    /**
     * GET /portal/domains/order?domain=example&tld=.co.uk&action=register
     * Show the "Order a domain" form pre-filled with domain + pricing.
     */
    public function order(Request $request): Response|RedirectResponse
    {
        $client     = $this->clientForUser();
        $domainName = strtolower(trim($request->input('domain', '')));
        $tld        = strtolower(trim($request->input('tld', '.co.uk')));
        $action     = $request->input('action', 'register');

        if ($domainName === '') {
            return redirect()->route('domains.check');
        }

        // Pre-compute retail prices for 1–5 years so the form can update dynamically
        $pricesByYear = [];
        for ($y = 1; $y <= 5; $y++) {
            $pricesByYear[$y] = round($this->pricing->calculateRetailPrice($tld, $y, $action), 2);
        }

        // Pre-fill contact details from the client record if available
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
        ] : [];

        return Inertia::render('Portal/Domains/Order', [
            'client'      => $client?->only('id', 'company_name', 'primary_contact_name'),
            'domain_name' => $domainName,
            'tld'         => $tld,
            'full_domain' => $domainName . $tld,
            'action'      => $action,
            'prices'      => $pricesByYear,
            'prefill'     => $prefill,
        ]);
    }
}
