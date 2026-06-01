<?php

namespace App\Services\Domain;

use App\Jobs\RegisterDomainJob;
use App\Models\Domain;
use App\Models\DomainEvent;
use App\Models\DomainOrder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class DomainOrderService
{
    /**
     * Create a new domain order (status: pending_payment).
     */
    public function createOrder(array $data): DomainOrder
    {
        $order = DomainOrder::create([
            'business_id'   => $data['business_id'],
            'client_id'     => $data['client_id'] ?? null,
            'created_by'    => $data['created_by'],
            'domain_name'   => strtolower(trim($data['domain_name'])),
            'tld'           => strtolower(trim($data['tld'])),
            'full_domain'   => strtolower(trim($data['full_domain'])),
            'years'         => (int) ($data['years'] ?? 1),
            'action'        => $data['action'] ?? 'register',
            'status'        => 'pending_payment',
            'retail_price'  => $data['retail_price'],
            'currency'      => $data['currency'] ?? 'GBP',
            'notes'         => $data['notes'] ?? null,
        ]);

        DomainEvent::log(
            domainId: null,
            domainOrderId: $order->id,
            type: 'order_created',
            description: "Domain order created for {$order->full_domain}",
            payload: ['action' => $order->action, 'years' => $order->years],
        );

        return $order;
    }

    /**
     * Mark an order as paid after successful Stripe payment.
     */
    public function markAsPaid(DomainOrder $order, string $stripePaymentIntentId): DomainOrder
    {
        $order->update([
            'status'                    => 'paid',
            'stripe_payment_intent_id'  => $stripePaymentIntentId,
        ]);

        DomainEvent::log(
            domainId: null,
            domainOrderId: $order->id,
            type: 'order_paid',
            description: "Payment received for {$order->full_domain}",
            payload: ['stripe_payment_intent_id' => $stripePaymentIntentId],
        );

        RegisterDomainJob::dispatch($order->fresh());

        return $order->fresh();
    }

    /**
     * Mark an order as 'registering' — the RegisterDomainJob is in progress.
     */
    public function markAsRegistering(DomainOrder $order): DomainOrder
    {
        $order->update(['status' => 'registering']);

        DomainEvent::log(
            domainId: null,
            domainOrderId: $order->id,
            type: 'registration_started',
            description: "Domain registration initiated for {$order->full_domain}",
        );

        return $order->fresh();
    }

    /**
     * Complete an order by creating the Domain record.
     */
    public function completeOrder(
        DomainOrder $order,
        string $providerDomainId,
        Carbon $registeredAt,
        ?Carbon $expiresAt,
    ): Domain {
        $domain = Domain::create([
            'business_id'       => $order->business_id,
            'client_id'         => $order->client_id,
            'domain_order_id'   => $order->id,
            'provider'          => $order->provider,
            'provider_domain_id'=> $providerDomainId,
            'name'              => $order->domain_name,
            'tld'               => $order->tld,
            'full_domain'       => $order->full_domain,
            'status'            => 'active',
            'registered_at'     => $registeredAt,
            'expires_at'        => $expiresAt,
            'auto_renew'        => false,
            'whois_privacy'     => true,
        ]);

        $order->update([
            'status'        => 'completed',
            'completed_at'  => now(),
        ]);

        DomainEvent::log(
            domainId: $domain->id,
            domainOrderId: $order->id,
            type: 'registered',
            description: "Domain {$domain->full_domain} successfully registered",
            payload: [
                'provider_domain_id' => $providerDomainId,
                'registered_at'      => $registeredAt->toIso8601String(),
                'expires_at'         => $expiresAt?->toIso8601String(),
            ],
        );

        return $domain;
    }

    /**
     * Mark an order as failed.
     */
    public function failOrder(DomainOrder $order, string $reason): DomainOrder
    {
        $order->update(['status' => 'failed']);

        DomainEvent::log(
            domainId: null,
            domainOrderId: $order->id,
            type: 'registration_failed',
            description: "Domain registration failed for {$order->full_domain}: {$reason}",
            payload: ['reason' => $reason],
        );

        return $order->fresh();
    }

    /**
     * Cancel an order (before registration).
     */
    public function cancelOrder(DomainOrder $order, ?string $reason = null): DomainOrder
    {
        $order->update(['status' => 'cancelled']);

        DomainEvent::log(
            domainId: null,
            domainOrderId: $order->id,
            type: 'order_cancelled',
            description: "Order cancelled for {$order->full_domain}" . ($reason ? ": {$reason}" : ''),
            payload: $reason ? ['reason' => $reason] : null,
        );

        return $order->fresh();
    }

    /**
     * Get all pending orders older than 1 hour (admin clean-up helper).
     *
     * @return Collection<int, DomainOrder>
     */
    public function getStalePendingOrders(): Collection
    {
        return DomainOrder::pendingPayment()
            ->where('created_at', '<', now()->subHour())
            ->get();
    }
}
