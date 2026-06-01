<?php

namespace App\Actions\Domain;

use App\Jobs\RegisterDomainJob;
use App\Models\DomainOrder;

class RegisterDomainAction
{
    /**
     * Dispatch the RegisterDomainJob for a paid order.
     * The order must have status 'paid'.
     *
     * @throws \InvalidArgumentException if the order is not in 'paid' status
     */
    public function execute(DomainOrder $order): void
    {
        if ($order->status !== 'paid') {
            throw new \InvalidArgumentException(
                "Cannot register domain order #{$order->id}: status is '{$order->status}', expected 'paid'."
            );
        }

        RegisterDomainJob::dispatch($order);
    }
}
