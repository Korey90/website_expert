<?php

namespace App\Actions\Domain;

use App\Models\Domain;
use App\Models\DomainEvent;
use App\Services\Domain\DomainRegistrarInterface;

class DeleteDnsRecordAction
{
    public function __construct(private readonly DomainRegistrarInterface $registrar) {}

    public function execute(Domain $domain, int $recordId): void
    {
        $cached = $domain->dns_records ?? [];
        $record = $cached[$recordId] ?? null;

        if ($record) {
            $clean = collect($record)->except('id')->toArray();
            $this->registrar->deleteDnsRecord($domain->full_domain, $clean);
        }

        DomainEvent::log(
            domainId:      $domain->id,
            domainOrderId: null,
            type:          'dns_record_deleted',
            description:   "DNS record #{$recordId} deleted for {$domain->full_domain}",
            payload:       ['record_id' => $recordId],
        );

        app(FetchDnsRecordsAction::class)->execute($domain);
    }
}
