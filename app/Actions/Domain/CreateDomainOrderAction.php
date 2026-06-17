<?php

namespace App\Actions\Domain;

use App\Models\Client;
use App\Models\DomainContact;
use App\Models\DomainOrder;
use App\Services\Domain\DomainOrderService;
use App\Services\Domain\DomainPricingService;

class CreateDomainOrderAction
{
    public function __construct(
        private readonly DomainOrderService $orderService,
        private readonly DomainPricingService $pricing,
    ) {}

    /**
     * Create a domain order from validated form data and an optional client.
     * Uses defaultBusiness() as the tenant since portal clients are not workspace members.
     */
    public function execute(array $data, ?Client $client): DomainOrder
    {
        $tld = $data['tld'];
        $years = (int) ($data['years'] ?? 1);
        $action = $data['action'] ?? 'register';

        $price = $this->pricing->getPriceForTld($tld);
        $currency = $price?->currency ?? $this->pricing->resolveCurrency();
        $retailPrice = $this->pricing->calculateRetailPrice($tld, $years, $action, $currency) ?? 0.00;
        $businessId = defaultBusiness()?->id;

        $order = $this->orderService->createOrder([
            'business_id' => $businessId,
            'client_id' => $client?->id,
            'created_by' => auth()->id(),
            'domain_name' => $data['domain_name'],
            'tld' => $tld,
            'full_domain' => $data['domain_name'].$tld,
            'years' => $years,
            'action' => $action,
            'retail_price' => $retailPrice,
            'currency' => $currency,
            'notes' => $data['notes'] ?? null,
        ]);

        // Create registrant contact record
        DomainContact::create([
            'domain_order_id' => $order->id,
            'type' => 'registrant',
            'first_name' => $data['first_name'],
            'last_name' => $data['last_name'],
            'email' => $data['email'],
            'phone' => $data['phone'] ?? null,
            'organisation' => $data['organisation'] ?? null,
            'address_line1' => $data['address_line1'],
            'address_line2' => $data['address_line2'] ?? null,
            'city' => $data['city'],
            'county' => $data['county'] ?? null,
            'postcode' => $data['postcode'],
            'country_code' => $data['country_code'] ?? 'GB',
        ]);

        return $order;
    }
}
