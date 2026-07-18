<?php

namespace App\Filament\Resources\DomainPriceListResource\Pages;

use App\Actions\Domain\FetchAvailableTldsAction;
use App\Actions\Domain\FetchOpenproviderPricesAction;
use App\Actions\Domain\ImportTldsFromOpenproviderAction;
use App\Filament\Resources\DomainPriceListResource;
use App\Models\DomainPriceList;
use App\Models\Setting;
use Filament\Actions;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\HtmlString;

class ListDomainPriceLists extends ListRecords
{
    protected static string $resource = DomainPriceListResource::class;

    protected function getHeaderActions(): array
    {
        // ── CSV Import (modal) ────────────────────────────────────────────
        $importCsvAction = Actions\Action::make('importCsv')
            ->label('Import CSV')
            ->icon('heroicon-o-arrow-up-tray')
            ->modalHeading('Import Domain Price List from CSV')
            ->modalDescription(new HtmlString(
                'Upload a CSV file with columns: <code>tld, currency, is_active, register_price, renew_price, '
                . 'transfer_price, wholesale_register, wholesale_renew, wholesale_transfer, margin_percent, notes</code>. '
                . 'Existing rows are matched by <strong>tld + currency</strong> and updated; new rows are created. '
                . '<a href="' . route('admin.domain-price-list.export-template') . '" class="underline">Download template</a>'
            ))
            ->modalWidth('lg')
            ->modalSubmitActionLabel('Import')
            ->form([
                Forms\Components\FileUpload::make('csv_file')
                    ->label('CSV file')
                    ->disk('local')
                    ->directory('csv-imports')
                    ->visibility('private')
                    ->acceptedFileTypes(['text/csv', 'text/plain', 'application/vnd.ms-excel', 'application/octet-stream'])
                    ->required(),
            ])
            ->action(function (array $data): void {
                // FileUpload may return string or single-element array
                $csvFile = $data['csv_file'];
                if (is_array($csvFile)) {
                    $csvFile = reset($csvFile);
                }

                if (empty($csvFile)) {
                    Notification::make()->title('No file was uploaded')->danger()->send();
                    return;
                }

                $path = Storage::disk('local')->path($csvFile);

                if (! file_exists($path)) {
                    Notification::make()->title('Uploaded file not found on disk')->danger()->send();
                    return;
                }

                // Strip UTF-8 BOM bytes that may be prepended by the export or Excel
                $raw = file_get_contents($path);
                if ($raw !== false && str_starts_with($raw, "\xEF\xBB\xBF")) {
                    $raw = substr($raw, 3);
                    file_put_contents($path, $raw);
                }

                $handle  = fopen($path, 'r');
                $headers = array_map('trim', fgetcsv($handle) ?: []);

                $col = fn (string $name) => array_search($name, $headers, true);

                $tldIdx    = $col('tld');
                $currIdx   = $col('currency');
                $activeIdx = $col('is_active');
                $regIdx    = $col('register_price');
                $renewIdx  = $col('renew_price');
                $transIdx  = $col('transfer_price');
                $wRegIdx   = $col('wholesale_register');
                $wRenewIdx = $col('wholesale_renew');
                $wTransIdx = $col('wholesale_transfer');
                $marginIdx = $col('margin_percent');
                $notesIdx  = $col('notes');

                if ($tldIdx === false || $currIdx === false) {
                    fclose($handle);
                    Storage::disk('local')->delete($csvFile);
                    $found = implode(', ', array_map(fn ($h) => '"' . addslashes($h) . '"', $headers));
                    Notification::make()
                        ->title('Invalid CSV: columns "tld" and "currency" are required')
                        ->body("Headers found: [{$found}]")
                        ->danger()->send();
                    return;
                }

                $created = $updated = $skipped = 0;

                $num = fn ($idx, array $row): ?float =>
                    $idx !== false && ($row[$idx] ?? '') !== ''
                        ? round((float) $row[$idx], 2)
                        : null;

                while (($row = fgetcsv($handle)) !== false) {
                    $tld      = strtolower(trim($row[$tldIdx] ?? ''));
                    $currency = strtoupper(trim($row[$currIdx] ?? ''));

                    if ($tld === '' || $currency === '') {
                        $skipped++;
                        continue;
                    }

                    $existing = DomainPriceList::where('tld', $tld)->where('currency', $currency)->first();
                    $isNew    = $existing === null;

                    $fill = array_filter([
                        'is_active'          => $activeIdx !== false
                            ? ((int) ($row[$activeIdx] ?? 1) === 1)
                            : ($existing?->is_active ?? true),
                        'register_price'     => $num($regIdx, $row),
                        'renew_price'        => $num($renewIdx, $row),
                        'transfer_price'     => $num($transIdx, $row),
                        'wholesale_register' => $num($wRegIdx, $row),
                        'wholesale_renew'    => $num($wRenewIdx, $row),
                        'wholesale_transfer' => $num($wTransIdx, $row),
                        'margin_percent'     => $num($marginIdx, $row),
                        'notes'              => $notesIdx !== false && ($row[$notesIdx] ?? '') !== ''
                            ? $row[$notesIdx]
                            : null,
                    ], fn ($v) => $v !== null);

                    DomainPriceList::updateOrCreate(['tld' => $tld, 'currency' => $currency], $fill);
                    $isNew ? $created++ : $updated++;
                }

                fclose($handle);
                Storage::disk('local')->delete($csvFile);

                Notification::make()
                    ->title("Import complete: {$created} created, {$updated} updated"
                        . ($skipped ? ", {$skipped} skipped" : ''))
                    ->success()->send();
            });

        // ── Fetch new TLDs from Openprovider ──────────────────────────────
        $fetchTldsAction = Actions\Action::make('fetchTldsFromOpenprovider')
            ->label('Fetch new TLDs')
            ->icon('heroicon-o-magnifying-glass-plus')
            ->modalHeading('Import New TLDs from Openprovider')
            ->modalDescription(
                'Fetches the list of available domain extensions from Openprovider. '
                . 'Select the TLDs you want to add — prices are fetched when you click Import.'
            )
            ->modalWidth('3xl')
            ->modalSubmitActionLabel('Import selected')
            ->mountUsing(function (Schema $form): void {
                $tlds = app(FetchAvailableTldsAction::class)->execute();
                $form->fill([
                    'tlds_json'     => json_encode($tlds),
                    'selected_tlds' => [],
                ]);
            })
            ->form([
                Forms\Components\Hidden::make('tlds_json'),
                Forms\Components\Hidden::make('selected_tlds')->default([]),
                Forms\Components\Toggle::make('activate_immediately')
                    ->label('Activate imported TLDs immediately')
                    ->helperText('When enabled, imported TLDs will be visible to customers right away.')
                    ->default(true),
                Forms\Components\Placeholder::make('tld_list')
                    ->label('')
                    ->content(function (Get $get): HtmlString {
                        $tlds = json_decode($get('tlds_json') ?? '[]', true);
                        return new HtmlString(
                            view('filament.actions.op-tlds-import', ['tlds' => $tlds ?? []])->render()
                        );
                    }),
            ])
            ->action(function (array $data): void {
                $selected = $data['selected_tlds'] ?? [];

                if (empty($selected)) {
                    Notification::make()->title('No TLDs selected')->warning()->send();
                    return;
                }

                $currency      = strtoupper((string) config('currencies.default', 'GBP'));
                $defaultMargin = (float) Setting::get('domain_default_margin', 50);
                $activate      = (bool) ($data['activate_immediately'] ?? true);

                $created = app(ImportTldsFromOpenproviderAction::class)
                    ->execute($selected, $currency, $defaultMargin, $activate);

                Notification::make()
                    ->title($created > 0
                        ? "{$created} TLD(s) imported successfully"
                        : 'No TLDs imported — prices could not be retrieved'
                    )
                    ->color($created > 0 ? 'success' : 'warning')
                    ->send();
            });

        // ── Sync wholesale prices from Openprovider ───────────────────────
        $syncPricesAction = Actions\Action::make('syncFromOpenprovider')
            ->label('Sync wholesale prices')
            ->icon('heroicon-o-arrow-path')
            ->modalHeading('Sync Wholesale Prices from Openprovider')
            ->modalDescription(
                'Fetching current wholesale prices from the Openprovider API. '
                . 'Changed TLDs are highlighted — retail prices are recalculated using each TLD\'s margin %.'
            )
            ->modalWidth('4xl')
            ->modalSubmitActionLabel('Apply changes')
            ->mountUsing(function (Schema $form): void {
                $changes  = app(FetchOpenproviderPricesAction::class)->execute();
                $selected = collect($changes)
                    ->where('changed', true)
                    ->pluck('id')
                    ->map(fn ($id) => (string) $id)
                    ->all();
                $form->fill([
                    'changes_json'  => json_encode($changes),
                    'selected_tlds' => $selected,
                ]);
            })
            ->form([
                Forms\Components\Hidden::make('changes_json'),
                Forms\Components\Hidden::make('selected_tlds'),
                Forms\Components\Placeholder::make('diff_table')
                    ->label('')
                    ->content(function (Get $get): HtmlString {
                        $changes = json_decode($get('changes_json') ?? '[]', true);
                        return new HtmlString(
                            view('filament.actions.op-prices-diff', ['changes' => $changes ?? []])->render()
                        );
                    }),
            ])
            ->action(function (array $data): void {
                $changes       = json_decode($data['changes_json'] ?? '[]', true);
                $selectedIds   = array_map('intval', $data['selected_tlds'] ?? []);
                $defaultMargin = (float) Setting::get('domain_default_margin', 50);
                $applied       = 0;

                foreach ($changes as $change) {
                    if (! $change['changed']) {
                        continue;
                    }
                    if (! in_array((int) $change['id'], $selectedIds, true)) {
                        continue;
                    }

                    $record = DomainPriceList::find($change['id']);
                    if (! $record) {
                        continue;
                    }

                    $margin     = $record->margin_percent > 0
                        ? (float) $record->margin_percent
                        : $defaultMargin;
                    $multiplier = 1 + ($margin / 100);

                    $record->update([
                        'wholesale_register'  => $change['op_register'],
                        'wholesale_renew'     => $change['op_renew'],
                        'wholesale_transfer'  => $change['op_transfer'],
                        'register_price'      => round((float) ($change['op_register'] ?? 0) * $multiplier, 2),
                        'renew_price'         => round((float) ($change['op_renew'] ?? 0) * $multiplier, 2),
                        'transfer_price'      => $change['op_transfer'] !== null
                            ? round((float) $change['op_transfer'] * $multiplier, 2)
                            : null,
                    ]);

                    $applied++;
                }

                Notification::make()
                    ->title($applied > 0
                        ? "Prices updated: {$applied} TLD(s) changed"
                        : 'All prices are already up to date'
                    )
                    ->success()->send();
            });

        // ── Header: 3 items — New | CSV ▼ | Openprovider ▼ ───────────────
        return [
            Actions\CreateAction::make(),

            Actions\ActionGroup::make([
                Actions\Action::make('exportCsv')
                    ->label('Export CSV')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->url(route('admin.domain-price-list.export'))
                    ->openUrlInNewTab(),
                $importCsvAction,
                Actions\Action::make('downloadTemplate')
                    ->label('Download CSV template')
                    ->icon('heroicon-o-document-text')
                    ->url(route('admin.domain-price-list.export-template'))
                    ->openUrlInNewTab(),
            ])
                ->label('CSV')
                ->icon('heroicon-o-document-arrow-down')
                ->color('gray')
                ->button(),

            Actions\ActionGroup::make([
                $fetchTldsAction,
                $syncPricesAction,
            ])
                ->label('Openprovider')
                ->icon('heroicon-o-globe-alt')
                ->color('info')
                ->button(),
        ];
    }
}
