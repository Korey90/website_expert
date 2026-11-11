<?php

namespace App\Actions\Finance;

use App\Models\Business;
use App\Models\HmrcConnection;
use App\Models\User;
use DomainException;
use Illuminate\Support\Facades\DB;

class UpdateHmrcSandboxVrnAction
{
    public function execute(Business $business, User $actor, string $vrn): HmrcConnection
    {
        $normalisedVrn = $this->normaliseVrn($vrn);

        return DB::transaction(function () use ($business, $actor, $normalisedVrn): HmrcConnection {
            Business::query()->lockForUpdate()->findOrFail($business->getKey());
            $this->assertAuthorised($business, $actor);

            if (config('hmrc.environment') !== 'sandbox') {
                throw new DomainException(__('finance.hmrc_connection.errors.sandbox_vrn_only'));
            }

            $connection = HmrcConnection::withoutGlobalScopes()
                ->where('business_id', $business->getKey())
                ->where('environment', 'sandbox')
                ->lockForUpdate()
                ->first();

            if (! $connection) {
                throw new DomainException(__('finance.hmrc_connection.errors.connection_required'));
            }

            if (hash_equals((string) $connection->vrn, $normalisedVrn)) {
                return $connection;
            }

            $connection->forceFill([
                'vrn' => $normalisedVrn,
                'vrn_updated_by' => $actor->getAuthIdentifier(),
                'vrn_updated_at' => now(),
            ])->save();

            return $connection->fresh(['vrnUpdatedBy']);
        }, 3);
    }

    private function normaliseVrn(string $vrn): string
    {
        $value = strtoupper(trim($vrn));

        if (preg_match('/^(?:GB)?[0-9\s-]+$/', $value) !== 1) {
            throw new DomainException(__('finance.hmrc_connection.errors.invalid_vrn'));
        }

        $digits = preg_replace('/\D+/', '', $value);

        if (! is_string($digits) || preg_match('/^\d{9}$/', $digits) !== 1) {
            throw new DomainException(__('finance.hmrc_connection.errors.invalid_vrn'));
        }

        return $digits;
    }

    private function assertAuthorised(Business $business, User $actor): void
    {
        $canManage = $actor->businessMemberships()
            ->where('business_id', $business->getKey())
            ->where('is_active', true)
            ->whereIn('role', ['owner', 'admin'])
            ->exists();

        if (! $canManage) {
            throw new DomainException(__('finance.hmrc_connection.errors.cannot_manage'));
        }
    }
}
