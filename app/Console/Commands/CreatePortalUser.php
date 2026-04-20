<?php

namespace App\Console\Commands;

use App\Models\Client;
use App\Services\Account\PortalAccessService;
use DomainException;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('app:create-portal-user {email : E-mail adres klienta} {--client= : ID klienta do powiązania} {--workspace : Nadaj też dostęp do workspace klienta, jeśli jest powiązany z business}')]
#[Description('Tworzy konto portalu klienta i wysyła dane logowania e-mailem')]
class CreatePortalUser extends Command
{
    public function handle(PortalAccessService $portalAccessService): int
    {
        $email    = $this->argument('email');
        $clientId = $this->option('client');
        $workspace = (bool) $this->option('workspace');

        // Find the Client record if --client given
        $client = $clientId ? Client::find($clientId) : Client::where('primary_contact_email', $email)->first();

        if ($clientId && ! $client) {
            $this->error("Nie znaleziono klienta o ID: {$clientId}");
            return self::FAILURE;
        }

        if ($workspace && ! $client) {
            $this->error('Opcja --workspace wymaga klienta powiązanego z rekordem Client.');
            return self::FAILURE;
        }

        $name = $client ? ($client->primary_contact_name ?: $client->company_name) : $this->ask('Imię i nazwisko użytkownika');

        try {
            $result = $portalAccessService->ensurePortalAccess($client, [
                'email' => $email,
                'name' => $name,
                'grant_workspace_access' => $workspace,
                'send_invite' => true,
                'queue_invite' => false,
            ]);
        } catch (DomainException $e) {
            $this->error($e->getMessage());
            return self::FAILURE;
        }

        if ($result['user_was_created']) {
            $this->info("✓ Konto portalu utworzone dla: {$email}");
            if ($result['plain_password']) {
                $this->line("  Hasło tymczasowe: <comment>{$result['plain_password']}</comment>");
            }
            $this->line('  E-mail z danymi logowania wysłany.');
        } elseif ($client) {
            $this->info("✓ Istniejące konto {$result['user']->email} powiązane z klientem.");
        } else {
            $this->info("✓ Konto {$result['user']->email} jest już gotowe do użycia.");
        }

        if ($workspace) {
            $this->line($result['workspace_membership_created']
                ? '  Workspace access został nadany.'
                : '  Workspace access był już aktywny.'
            );
        }

        return self::SUCCESS;
    }
}

