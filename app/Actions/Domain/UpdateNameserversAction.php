<?php

namespace App\Actions\Domain;

use App\Models\Domain;
use App\Models\DomainEvent;
use App\Services\Domain\DomainRegistrarInterface;

class UpdateNameserversAction
{
    public function __construct(private readonly DomainRegistrarInterface $registrar) {}

    /**
     * Update nameservers for an active domain via the registrar and persist locally.
     *
     * @param  string[]  $nameservers  e.g. ['ns1.example.com', 'ns2.example.com']
     * @throws \RuntimeException on registrar failure
     */
    public function execute(Domain $domain, array $nameservers): Domain
    {
        $success = $this->registrar->updateNameservers($domain->full_domain, $nameservers);

        if (! $success) {
            throw new \RuntimeException(
                "Failed to update nameservers for {$domain->full_domain}."
            );
        }

        $domain->update(['nameservers' => $nameservers]);

        DomainEvent::log(
            domainId: $domain->id,
            domainOrderId: null,
            type: 'nameservers_updated',
            description: "Nameservers updated for {$domain->full_domain}",
            payload: ['nameservers' => $nameservers],
        );

        return $domain->fresh();
    }
}
