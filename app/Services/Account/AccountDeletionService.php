<?php

namespace App\Services\Account;

use App\Mail\AccountDeletedMail;
use App\Models\Client;
use App\Models\User;
use Illuminate\Support\Facades\Mail;

class AccountDeletionService
{
    /**
     * Permanently delete a user account in a GDPR-compliant manner.
     *
     * What is deleted:
     *  - social_accounts     — hard delete
     *  - business_users      — hard delete (pivot)
     *  - businesses          — soft-delete if user is the sole active member
     *  - clients.portal_user_id → set to null (agency keeps CRM record)
     *  - user record itself  — hard delete
     *
     * A confirmation e-mail is sent before deletion.
     */
    public function delete(User $user): void
    {
        // Capture before delete
        $userName  = $user->name;
        $userEmail = $user->email;

        // 1. Sever portal link — agency retains the Client CRM record
        Client::where('portal_user_id', $user->id)
            ->update(['portal_user_id' => null]);

        // 2. Handle businesses where user is the sole active member
        foreach ($user->businesses as $business) {
            $otherActiveMembers = $business->users()
                ->where('users.id', '!=', $user->id)
                ->wherePivot('is_active', true)
                ->count();

            if ($otherActiveMembers === 0) {
                $business->delete(); // soft-delete preserves landing pages, leads etc.
            }
        }

        // 3. Remove business membership pivots
        $user->businessMemberships()->delete();

        // 4. Delete linked social accounts
        $user->socialAccounts()->delete();

        // 5. Send confirmation e-mail BEFORE deleting user (we still have email/name)
        try {
            Mail::to($userEmail)->send(new AccountDeletedMail($userName));
        } catch (\Throwable) {
            // Non-blocking — deletion proceeds even if the mailer fails
        }

        // 6. Hard-delete the user (cascades Spatie role assignments, notifications)
        $user->delete();
    }
}
