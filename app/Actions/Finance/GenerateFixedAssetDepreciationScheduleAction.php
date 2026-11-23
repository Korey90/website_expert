<?php

namespace App\Actions\Finance;

use App\Models\Business;
use App\Models\FixedAsset;
use App\Models\FixedAssetDepreciationEntry;
use App\Models\User;
use App\Services\Finance\FixedAssetDepreciationScheduleCalculator;
use App\Support\PermissionHelper;
use Carbon\CarbonImmutable;
use DomainException;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class GenerateFixedAssetDepreciationScheduleAction
{
    public function __construct(private readonly FixedAssetDepreciationScheduleCalculator $calculator) {}

    /** @return Collection<int, FixedAssetDepreciationEntry> */
    public function execute(FixedAsset $asset, User $actor, ?string $throughDate = null): Collection
    {
        return DB::transaction(function () use ($asset, $actor, $throughDate): Collection {
            $locked = FixedAsset::withoutGlobalScopes()->lockForUpdate()->findOrFail($asset->id);
            Business::query()->lockForUpdate()->findOrFail($locked->business_id);
            $this->assertAuthorized($locked, $actor);

            if ($throughDate !== null) {
                try {
                    $cutoff = CarbonImmutable::createFromFormat('!Y-m-d', trim($throughDate));
                } catch (\Throwable) {
                    $cutoff = null;
                }

                if (! $cutoff || $cutoff->format('Y-m-d') !== trim($throughDate)) {
                    throw new DomainException('The depreciation schedule end date must use a valid YYYY-MM-DD value.');
                }

                if (FixedAssetDepreciationEntry::withoutGlobalScopes()
                    ->where('fixed_asset_id', $locked->id)
                    ->where('status', FixedAssetDepreciationEntry::STATUS_POSTED)
                    ->whereDate('period_end', '>', $cutoff)
                    ->exists()) {
                    throw new DomainException('Reverse depreciation posted after the requested schedule end date first.');
                }
            }

            FixedAssetDepreciationEntry::withoutGlobalScopes()
                ->where('fixed_asset_id', $locked->id)
                ->where('status', FixedAssetDepreciationEntry::STATUS_PLANNED)
                ->lockForUpdate()
                ->get()
                ->each->delete();

            $rows = $this->calculator->calculate($locked, $throughDate);
            foreach ($rows as $row) {
                $hasPosted = FixedAssetDepreciationEntry::withoutGlobalScopes()
                    ->where('fixed_asset_id', $locked->id)
                    ->whereDate('period_end', $row['period_end'])
                    ->where('status', FixedAssetDepreciationEntry::STATUS_POSTED)
                    ->exists();

                if ($hasPosted) {
                    continue;
                }

                FixedAssetDepreciationEntry::withoutGlobalScopes()->create([
                    ...$row,
                    'business_id' => $locked->business_id,
                    'fixed_asset_id' => $locked->id,
                    'status' => FixedAssetDepreciationEntry::STATUS_PLANNED,
                    'created_by' => $actor->id,
                ]);
            }

            return FixedAssetDepreciationEntry::withoutGlobalScopes()
                ->where('fixed_asset_id', $locked->id)
                ->orderBy('period_end')->orderBy('id')->get();
        }, 3);
    }

    private function assertAuthorized(FixedAsset $asset, User $actor): void
    {
        if (! $actor->businessMemberships()->where('business_id', $asset->business_id)->where('is_active', true)->exists()
            || ! PermissionHelper::allows($actor, 'manage_finance')) {
            throw new DomainException('The actor cannot generate this fixed asset depreciation schedule.');
        }
    }
}
