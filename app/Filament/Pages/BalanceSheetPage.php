<?php

namespace App\Filament\Pages;

use App\Models\AccountingPeriod;
use App\Services\Finance\BalanceSheetService;
use Illuminate\Support\Collection;

class BalanceSheetPage extends BasePage
{
    protected string $view = 'filament.pages.balance-sheet';

    protected static \BackedEnum|string|null $navigationIcon = 'heroicon-o-scale';

    protected static \UnitEnum|string|null $navigationGroup = 'Reports';

    protected static ?int $navigationSort = 3;

    protected static ?string $slug = 'balance-sheet';

    public string $periodId = '';

    public string $comparisonPeriodId = '';

    public static function getNavigationLabel(): string
    {
        return __('finance.balance_sheet.navigation');
    }

    public function getTitle(): string
    {
        return __('finance.balance_sheet.title');
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

        return app(BalanceSheetService::class)->report(
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

    public function getExportUrl(string $format = 'csv'): ?string
    {
        if ($this->periodId === '' || ! in_array($format, ['csv', 'xlsx', 'pdf'], true)) {
            return null;
        }

        return route('admin.finance.balance-sheet.'.$format, array_filter([
            'period_id' => $this->periodId,
            'comparison_period_id' => $this->comparisonPeriodId !== '' ? $this->comparisonPeriodId : null,
        ], fn (mixed $value): bool => $value !== null));
    }
}
