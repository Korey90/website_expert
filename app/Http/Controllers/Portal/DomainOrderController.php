<?php

namespace App\Http\Controllers\Portal;

use App\Actions\Domain\CreateDomainOrderAction;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class DomainOrderController extends BasePortalController
{
    public function __construct(
        private readonly CreateDomainOrderAction $createAction,
    ) {}

    /**
     * POST /portal/domains/order — validate form, create DomainOrder + DomainContact, redirect to checkout.
     */
    public function store(Request $request): RedirectResponse
    {
        $client = $this->clientForUser();

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

        $order = $this->createAction->execute($validated, $client);

        return redirect()
            ->route('portal.domains.checkout', $order->id)
            ->with('success', 'Order created successfully. Please complete your payment.');
    }
}
