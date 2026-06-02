<?php

namespace App\Http\Controllers\Portal;

use App\Actions\Domain\DeleteDnsRecordAction;
use App\Actions\Domain\FetchDnsRecordsAction;
use App\Actions\Domain\SaveDnsRecordAction;
use App\Models\Domain;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DomainDnsController extends BasePortalController
{
    public function index(Domain $domain): Response
    {
        $client = $this->clientForUser();
        abort_if(! $client || $domain->client_id !== $client->id, 403);

        $records = app(FetchDnsRecordsAction::class)->execute($domain);

        return Inertia::render('Portal/Domains/Dns', [
            'client'  => $client->only('id', 'company_name', 'primary_contact_name'),
            'domain'  => [
                'id'          => $domain->id,
                'full_domain' => $domain->full_domain,
                'nameservers' => $domain->nameservers ?? [],
                'provider'    => $domain->provider,
            ],
            'records' => $records,
        ]);
    }

    public function store(Domain $domain, Request $request): RedirectResponse
    {
        $client = $this->clientForUser();
        abort_if(! $client || $domain->client_id !== $client->id, 403);

        $validated = $request->validate([
            'type'  => ['required', 'string', 'in:A,AAAA,CNAME,MX,TXT,NS,SRV'],
            'name'  => ['required', 'string', 'max:255'],
            'value' => ['required', 'string', 'max:1024'],
            'ttl'   => ['required', 'integer', 'min:300', 'max:86400'],
            'prio'  => ['nullable', 'integer', 'min:0', 'max:65535'],
        ]);

        $validated['prio'] = $validated['prio'] ?? 0;

        app(SaveDnsRecordAction::class)->execute($domain, null, $validated);

        return redirect()->back()->with('success', 'DNS record created.');
    }

    public function update(Domain $domain, int $recordId, Request $request): RedirectResponse
    {
        $client = $this->clientForUser();
        abort_if(! $client || $domain->client_id !== $client->id, 403);

        $validated = $request->validate([
            'type'  => ['required', 'string', 'in:A,AAAA,CNAME,MX,TXT,NS,SRV'],
            'name'  => ['required', 'string', 'max:255'],
            'value' => ['required', 'string', 'max:1024'],
            'ttl'   => ['required', 'integer', 'min:300', 'max:86400'],
            'prio'  => ['nullable', 'integer', 'min:0', 'max:65535'],
        ]);

        $validated['prio'] = $validated['prio'] ?? 0;

        app(SaveDnsRecordAction::class)->execute($domain, $recordId, $validated);

        return redirect()->back()->with('success', 'DNS record updated.');
    }

    public function destroy(Domain $domain, int $recordId): RedirectResponse
    {
        $client = $this->clientForUser();
        abort_if(! $client || $domain->client_id !== $client->id, 403);

        app(DeleteDnsRecordAction::class)->execute($domain, $recordId);

        return redirect()->back()->with('success', 'DNS record deleted.');
    }
}
