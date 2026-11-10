<?php

namespace App\Filament\Pages;

use App\Actions\Finance\VatReturnCalculationAction;
use App\Models\AccountingPeriod;
use Illuminate\Support\Collection;

class VatDetailPage extends BasePage
{
    protected string $view = 'filament.pages.vat-detail';

    protected static \BackedEnum|string|null $navigationIcon = 'heroicon-o-receipt-percent';

    protected static \UnitEnum|string|null $navigationGroup = 'Reports';

    protected static ?int $navigationSort = 6;

    protected static ?string $slug = 'vat-detail';

    public string $dateFrom = '';

    public string $dateTo = '';

    public static function getNavigationLabel(): string
    {
        return __('finance.vat_detail.navigation');
    }

    public function getTitle(): string
    {
        return __('finance.vat_detail.title');
    }

    public function mount(): void
    {
        $period = AccountingPeriod::query()
            ->whereDate('start_date', '<=', today())
            ->whereDate('end_date', '>=', today())
            ->orderByDesc('end_date')
            ->first() ?? AccountingPeriod::query()->orderByDesc('end_date')->first();

        $this->dateFrom = $period?->start_date->toDateString() ?? today()->startOfQuarter()->toDateString();
        $this->dateTo = $period?->end_date->toDateString() ?? today()->endOfQuarter()->toDateString();
    }

    /** @return array<string, mixed>|null */
    public function getWorkspace(): ?array
    {
        if ($this->dateFrom === '' || $this->dateTo === '' || $this->dateFrom > $this->dateTo) {
            return null;
        }

        return app(VatReturnCalculationAction::class)->execute(
            currentBusiness()->id,
            $this->dateFrom,
            $this->dateTo,
        );
    }

    /** @return array{date_from: string, date_to: string, rows: Collection<int, object>, tax_codes: Collection<int, object>, totals: object}|null */
    public function getReport(): ?array
    {
        return $this->getWorkspace()['vat_detail'] ?? null;
    }

    public function getExportUrl(): ?string
    {
        if ($this->dateFrom === '' || $this->dateTo === '' || $this->dateFrom > $this->dateTo) {
            return null;
        }

        return route('admin.finance.vat-detail.csv', [
            'date_from' => $this->dateFrom,
            'date_to' => $this->dateTo,
        ]);
    }
}
