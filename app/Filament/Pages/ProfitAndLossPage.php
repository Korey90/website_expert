<?php

namespace App\Filament\Pages;

use App\Models\AccountingPeriod;
use App\Services\Finance\ProfitAndLossService;
use Illuminate\Support\Collection;

class ProfitAndLossPage extends BasePage
{
    protected string $view = 'filament.pages.profit-and-loss';

    protected static \BackedEnum|string|null $navigationIcon = 'heroicon-o-chart-bar';

    protected static \UnitEnum|string|null $navigationGroup = 'Reports';

    protected static ?int $navigationSort = 2;

    protected static ?string $slug = 'profit-and-loss';

    public string $periodId = '';

    public string $comparisonPeriodId = '';

    public static function getNavigationLabel(): string
    {
        return __('finance.profit_and_loss.navigation');
    }

    public function getTitle(): string
    {
        return __('finance.profit_and_loss.title');
    }

    public function getSubheading(): ?string
    {
        return __('admin.migration.reports.profit_and_loss');
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

    /** @return array{period: object, comparison_period: ?object, sections: Collection<int, object>, totals: object}|null */
    public function getReport(): ?array
    {
        if ($this->periodId === '') {
            return null;
        }

        return app(ProfitAndLossService::class)->report(
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

        return route('admin.finance.profit-and-loss.csv', array_filter([
            'period_id' => $this->periodId,
            'comparison_period_id' => $this->comparisonPeriodId !== '' ? $this->comparisonPeriodId : null,
        ], fn (mixed $value): bool => $value !== null));
    }
}
