<?php

namespace App\Actions\Domain;

use App\Models\Domain;
use App\Services\Domain\DomainRegistrarInterface;

class FetchDnsRecordsAction
{
    public function __construct(private readonly DomainRegistrarInterface $registrar) {}

    /**
     * Fetch DNS records from registrar and cache in Domain.dns_records.
     * Array index is used as the pseudo-ID (registrars like Openprovider have no record IDs).
     * @return array<int, array>
     */
    public function execute(Domain $domain): array
    {
        $records = $this->registrar->getDnsRecords($domain->full_domain);

        $mapped = [];
        foreach ($records as $index => $r) {
            $arr       = $r->toArray();
            $arr['id'] = $index;
            $mapped[]  = $arr;
        }

        $domain->update(['dns_records' => $mapped]);

        return $mapped;
    }
}
