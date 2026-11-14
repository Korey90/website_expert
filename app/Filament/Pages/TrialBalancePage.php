<?php

namespace App\Filament\Pages;

use App\Models\AccountingPeriod;
use App\Services\Finance\TrialBalanceService;
use Illuminate\Support\Collection;

class TrialBalancePage extends BasePage
{
    protected string $view = 'filament.pages.trial-balance';

    protected static \BackedEnum|string|null $navigationIcon = 'heroicon-o-table-cells';

    protected static \UnitEnum|string|null $navigationGroup = 'Reports';

    protected static ?int $navigationSort = 1;

    protected static ?string $slug = 'trial-balance';

    public string $periodId = '';

    public string $comparisonPeriodId = '';

    public static function getNavigationLabel(): string
    {
        return __('finance.trial_balance.navigation');
    }

    public function getTitle(): string
    {
        return __('finance.trial_balance.title');
    }

    public function getSubheading(): ?string
    {
        return __('admin.migration.reports.trial_balance');
    }

    public function mount(): void
    {
        $period = AccountingPeriod::query()
            ->whereDate('start_date', '<=', today())
            ->whereDate('end_date', '>=', today())
            ->orderByDesc('end_date')
            ->first() ?? AccountingPeriod::query()->orderByDesc('end_date')->first();

        if (! $period) {
            return;
        }

        $this->periodId = (string) $period->id;
        $comparison = AccountingPeriod::query()
            ->whereDate('end_date', '<', $period->start_date)
            ->orderByDesc('end_date')
            ->first();
        $this->comparisonPeriodId = $comparison ? (string) $comparison->id : '';
    }

    public function updatedPeriodId(): void
    {
        if ($this->comparisonPeriodId === $this->periodId) {
            $this->comparisonPeriodId = '';
        }
    }

    /** @return array{period: object, comparison_period: ?object, rows: Collection<int, object>, totals: object}|null */
    public function getReport(): ?array
    {
        if ($this->periodId === '') {
            return null;
        }

        return app(TrialBalanceService::class)->report(
            currentBusiness()->id,
            (int) $this->periodId,
            $this->comparisonPeriodId !== '' ? (int) $this->comparisonPeriodId : null,
        );
    }

    /** @return array<int, string> */
    public function getPeriodOptions(): array
    {
        return AccountingPeriod::query()
            ->orderByDesc('end_date')
            ->get()
            ->mapWithKeys(fn (AccountingPeriod $period): array => [
                $period->id => $period->name.' · '.$period->start_date->toDateString().' – '.$period->end_date->toDateString(),
            ])
            ->all();
    }

    /** @return array<int, string> */
    public function getComparisonOptions(): array
    {
        return collect($this->getPeriodOptions())
            ->except([(int) $this->periodId])
            ->all();
    }

    public function getExportUrl(): ?string
    {
        if ($this->periodId === '') {
            return null;
        }

        return route('admin.finance.trial-balance.csv', array_filter([
            'period_id' => $this->periodId,
            'comparison_period_id' => $this->comparisonPeriodId !== '' ? $this->comparisonPeriodId : null,
        ], fn (mixed $value): bool => $value !== null));
    }
}
