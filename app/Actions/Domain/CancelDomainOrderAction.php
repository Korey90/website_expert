<?php

namespace App\Actions\Domain;

use App\Models\DomainOrder;
use App\Services\Domain\DomainOrderService;

class CancelDomainOrderAction
{
    private const CANCELLABLE_STATUSES = ['pending_payment', 'paid', 'failed'];

    public function __construct(private readonly DomainOrderService $orderService) {}

    /**
     * Cancel a domain order.
     * Only orders in 'pending_payment', 'paid', or 'failed' status can be cancelled.
     *
     * @throws \InvalidArgumentException if the order is in a non-cancellable state
     */
    public function execute(DomainOrder $order, ?string $reason = null): DomainOrder
    {
        if (! in_array($order->status, self::CANCELLABLE_STATUSES, true)) {
            throw new \InvalidArgumentException(
                "Cannot cancel domain order #{$order->id}: status '{$order->status}' is not cancellable."
            );
        }

        return $this->orderService->cancelOrder($order, $reason);
    }
}
