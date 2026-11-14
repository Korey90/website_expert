<?php

namespace App\Filament\Pages;

use App\Models\LedgerAccount;
use App\Services\Finance\GeneralLedgerService;
use Illuminate\Support\Collection;

class GeneralLedgerPage extends BasePage
{
    protected string $view = 'filament.pages.general-ledger';

    protected static \BackedEnum|string|null $navigationIcon = 'heroicon-o-scale';

    protected static \UnitEnum|string|null $navigationGroup = 'Finance';

    protected static ?int $navigationSort = 11;

    protected static ?string $slug = 'general-ledger';

    public string $dateFrom = '';

    public string $dateTo = '';

    public string $accountId = '';

    public static function getNavigationLabel(): string
    {
        return __('finance.general_ledger.navigation');
    }

    public function getTitle(): string
    {
        return __('finance.general_ledger.title');
    }

    public function getSubheading(): ?string
    {
        return __('admin.migration.accounting.general_ledger.subheading');
    }

    public function mount(): void
    {
        $this->dateFrom = now()->startOfYear()->toDateString();
        $this->dateTo = today()->toDateString();
    }

    /** @return array{accounts: Collection<int, object>, totals: object} */
    public function getReport(): array
    {
        return app(GeneralLedgerService::class)->report(
            currentBusiness()->id,
            $this->dateFrom,
            $this->dateTo,
            $this->accountId !== '' ? (int) $this->accountId : null,
        );
    }

    /** @return array<int, string> */
    public function getAccountOptions(): array
    {
        return LedgerAccount::query()
            ->where('is_postable', true)
            ->orderBy('code')
            ->get()
            ->mapWithKeys(fn (LedgerAccount $account): array => [$account->id => $account->code.' — '.$account->name])
            ->all();
    }
}
