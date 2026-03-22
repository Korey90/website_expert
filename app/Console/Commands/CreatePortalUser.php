<?php

namespace App\Console\Commands;

use App\Mail\PortalInviteMail;
use App\Models\Client;
use App\Models\User;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

#[Signature('app:create-portal-user {email : E-mail adres klienta} {--client= : ID klienta do powiązania}')]
#[Description('Tworzy konto portalu klienta i wysyła dane logowania e-mailem')]
class CreatePortalUser extends Command
{
    public function handle(): int
    {
        $email    = $this->argument('email');
        $clientId = $this->option('client');

        // Find the Client record if --client given
        $client = $clientId ? Client::find($clientId) : Client::where('primary_contact_email', $email)->first();

        if ($clientId && ! $client) {
            $this->error("Nie znaleziono klienta o ID: {$clientId}");
            return self::FAILURE;
        }

        // Check if User already exists
        $existing = User::where('email', $email)->first();

        if ($existing) {
            if ($client) {
                $client->update(['portal_user_id' => $existing->id]);
            }
            if (! $existing->hasRole('client')) {
                $existing->assignRole('client');
            }
            $this->info("✓ Istniejące konto {$email} powiązane z klientem.");
            return self::SUCCESS;
        }

        // Ask for name if not derivable from client record
        $name = $client ? ($client->primary_contact_name ?: $client->company_name) : $this->ask('Imię i nazwisko użytkownika');

        $plainPassword = Str::password(12, symbols: false);

        $user = User::create([
            'name'      => $name,
            'email'     => $email,
            'password'  => Hash::make($plainPassword),
            'is_active' => true,
            'locale'    => 'pl',
        ]);

        $user->assignRole('client');

        if ($client) {
            $client->update(['portal_user_id' => $user->id]);
        }

        $companyName = config('mail.from.name', config('app.name'));

        Mail::to($email)->send(new PortalInviteMail(
            clientName:    $name,
            loginEmail:    $email,
            plainPassword: $plainPassword,
            loginUrl:      route('login'),
            companyName:   $companyName,
        ));

        $this->info("✓ Konto portalu utworzone dla: {$email}");
        $this->line("  Hasło tymczasowe: <comment>{$plainPassword}</comment>");
        $this->line("  E-mail z danymi logowania wysłany.");

        return self::SUCCESS;
    }
}

