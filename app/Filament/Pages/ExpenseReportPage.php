<?php

namespace App\Filament\Pages;

use App\Services\Finance\ExpenseReportService;
use Illuminate\Support\Collection;

class ExpenseReportPage extends BasePage
{
    protected string $view = 'filament.pages.expense-report';

    protected static \BackedEnum|string|null $navigationIcon = 'heroicon-o-chart-bar-square';

    protected static \UnitEnum|string|null $navigationGroup = 'Reports';

    protected static ?int $navigationSort = 2;

    protected static ?string $slug = 'expense-report';

    public string $dateFrom = '';

    public string $dateTo = '';

    public string $groupBy = 'category';

    public static function getNavigationLabel(): string
    {
        return __('finance.expense_report.navigation');
    }

    public function getTitle(): string
    {
        return __('finance.expense_report.title');
    }

    public function mount(): void
    {
        $this->dateFrom = now()->startOfYear()->toDateString();
        $this->dateTo = today()->toDateString();
    }

    /** @return array{rows: Collection<int, object>, totals: object} */
    public function getReport(): array
    {
        return app(ExpenseReportService::class)->report(
            currentBusiness()->id,
            $this->dateFrom,
            $this->dateTo,
            $this->groupBy,
        );
    }

    public function getExportUrl(): string
    {
        return route('admin.finance.expense-report.csv', [
            'date_from' => $this->dateFrom,
            'date_to' => $this->dateTo,
            'group_by' => $this->groupBy,
        ]);
    }
}
