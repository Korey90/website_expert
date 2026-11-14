<?php

namespace App\Filament\Pages;

use App\Models\BankAccount;
use App\Services\Finance\BankReconciliationReportService;
use Illuminate\Support\Collection;

class BankReconciliationReportPage extends BasePage
{
    protected string $view = 'filament.pages.bank-reconciliation-report';

    protected static \BackedEnum|string|null $navigationIcon = 'heroicon-o-document-chart-bar';

    protected static \UnitEnum|string|null $navigationGroup = 'Reports';

    protected static ?int $navigationSort = 3;

    protected static ?string $slug = 'bank-reconciliation-report';

    public string $dateFrom = '';

    public string $dateTo = '';

    public string $accountId = '';

    public static function getNavigationLabel(): string
    {
        return __('finance.bank_reconciliation_report.navigation');
    }

    public function getTitle(): string
    {
        return __('finance.bank_reconciliation_report.title');
    }

    public function getSubheading(): ?string
    {
        return __('admin.migration.reports.bank_reconciliation');
    }

    public function mount(): void
    {
        $this->dateFrom = now()->startOfMonth()->toDateString();
        $this->dateTo = today()->toDateString();
    }

    /** @return array{accounts: Collection<int, object>, transactions: Collection<int, object>, totals: object} */
    public function getReport(): array
    {
        return app(BankReconciliationReportService::class)->report(
            currentBusiness()->id,
            $this->dateFrom,
            $this->dateTo,
            $this->accountId !== '' ? (int) $this->accountId : null,
        );
    }

    /** @return array<int, string> */
    public function getAccountOptions(): array
    {
        return BankAccount::query()
            ->orderBy('name')
            ->get()
            ->mapWithKeys(fn (BankAccount $account): array => [
                $account->id => $account->name.' · '.$account->masked_account_number.' · '.$account->currency,
            ])
            ->all();
    }

    public function getExportUrl(): string
    {
        return route('admin.finance.bank-reconciliation-report.csv', array_filter([
            'date_from' => $this->dateFrom,
            'date_to' => $this->dateTo,
            'account_id' => $this->accountId !== '' ? $this->accountId : null,
        ], fn (mixed $value): bool => $value !== null));
    }
}
