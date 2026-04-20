<?php

namespace App\Services\Account;

use App\Mail\PortalInviteMail;
use App\Models\BusinessUser;
use App\Models\Client;
use App\Models\User;
use DomainException;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class PortalAccessService
{
    /**
     * @param  array{
     *     email?: string,
     *     name?: string,
     *     grant_workspace_access?: bool,
     *     invited_by?: int|null,
     *     send_invite?: bool,
     *     queue_invite?: bool
     * }  $options
     * @return array{
     *     user: User,
     *     user_was_created: bool,
     *     workspace_membership_created: bool,
     *     plain_password: string|null,
     *     invite_sent: bool
     * }
     */
    public function ensurePortalAccess(?Client $client, array $options = []): array
    {
        $email = $options['email'] ?? $client?->primary_contact_email;
        $name = $options['name'] ?? $client?->primary_contact_name ?? $client?->company_name;

        if (! $email) {
            throw new DomainException('Portal access requires a contact email address.');
        }

        if (! $name) {
            throw new DomainException('Portal access requires a contact name or company name.');
        }

        $grantWorkspaceAccess = (bool) ($options['grant_workspace_access'] ?? false);
        $sendInvite = (bool) ($options['send_invite'] ?? true);
        $queueInvite = (bool) ($options['queue_invite'] ?? true);
        $invitedByUserId = $options['invited_by'] ?? null;

        $user = $client?->portalUser ?: User::where('email', $email)->first();
        $plainPassword = null;
        $userWasCreated = false;

        if (! $user) {
            $plainPassword = Str::password(12, symbols: false);

            $user = User::create([
                'name'      => $name,
                'email'     => $email,
                'password'  => Hash::make($plainPassword),
                'is_active' => true,
                'locale'    => 'pl',
            ]);

            $userWasCreated = true;
        }

        if (! $user->hasRole('client')) {
            $user->assignRole('client');
        }

        if ($client && $client->portal_user_id !== $user->id) {
            $client->update(['portal_user_id' => $user->id]);
        }

        $workspaceMembershipCreated = false;
        if ($grantWorkspaceAccess) {
            $workspaceMembershipCreated = $this->grantWorkspaceAccess($client, $user, $invitedByUserId);
        }

        $inviteSent = false;
        if ($userWasCreated && $sendInvite && $plainPassword) {
            $inviteSent = true;
            $this->sendInvite(
                email: $email,
                name: $name,
                plainPassword: $plainPassword,
                queue: $queueInvite,
            );
        }

        return [
            'user' => $user,
            'user_was_created' => $userWasCreated,
            'workspace_membership_created' => $workspaceMembershipCreated,
            'plain_password' => $plainPassword,
            'invite_sent' => $inviteSent,
        ];
    }

    public function grantWorkspaceAccess(?Client $client, User $user, ?int $invitedByUserId = null): bool
    {
        if (! $client) {
            throw new DomainException('Workspace access requires a linked client record.');
        }

        if (! $client->business_id) {
            throw new DomainException('Workspace access requires the client to be linked to a business.');
        }

        $activeBusiness = $user->currentBusiness();
        if ($activeBusiness && $activeBusiness->id !== $client->business_id) {
            throw new DomainException('Workspace access cannot be granted because this user already belongs to a different active workspace.');
        }

        $membership = BusinessUser::firstOrNew([
            'business_id' => $client->business_id,
            'user_id' => $user->id,
        ]);

        $wasExisting = $membership->exists;

        $membership->role = $membership->role ?: 'client';
        $membership->is_active = true;
        $membership->joined_at = $membership->joined_at ?: now();

        if ($invitedByUserId && ! $membership->invited_by) {
            $membership->invited_by = $invitedByUserId;
        }

        if (! $wasExisting || $membership->isDirty()) {
            $membership->save();
        }

        return ! $wasExisting;
    }

    private function sendInvite(string $email, string $name, string $plainPassword, bool $queue): void
    {
        $mailable = new PortalInviteMail(
            clientName: $name,
            loginEmail: $email,
            plainPassword: $plainPassword,
            loginUrl: route('login'),
            companyName: config('mail.from.name', config('app.name')),
        );

        if ($queue) {
            Mail::to($email)->queue($mailable);
            return;
        }

        Mail::to($email)->send($mailable);
    }
}