<?php

namespace App\Filament\Pages;

use App\Services\Finance\AgedReceivablesPayablesService;
use Illuminate\Support\Collection;

class AgedReceivablesPayablesPage extends BasePage
{
    protected string $view = 'filament.pages.aged-receivables-payables';

    protected static \BackedEnum|string|null $navigationIcon = 'heroicon-o-clock';

    protected static \UnitEnum|string|null $navigationGroup = 'Reports';

    protected static ?int $navigationSort = 5;

    protected static ?string $slug = 'aged-receivables-payables';

    public string $asOf = '';

    public static function getNavigationLabel(): string
    {
        return __('finance.aged_receivables_payables.navigation');
    }

    public function getTitle(): string
    {
        return __('finance.aged_receivables_payables.title');
    }

    public function mount(): void
    {
        $this->asOf = today()->toDateString();
    }

    /** @return array{as_of: string, buckets: list<string>, sections: Collection<int, object>}|null */
    public function getReport(): ?array
    {
        if ($this->asOf === '') {
            return null;
        }

        return app(AgedReceivablesPayablesService::class)->report(currentBusiness()->id, $this->asOf);
    }

    public function getExportUrl(string $format = 'csv'): ?string
    {
        if ($this->asOf === '' || ! in_array($format, ['csv', 'xlsx', 'pdf'], true)) {
            return null;
        }

        return route('admin.finance.aged-receivables-payables.'.$format, ['as_of' => $this->asOf]);
    }
}
