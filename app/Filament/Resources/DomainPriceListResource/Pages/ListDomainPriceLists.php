<?php

namespace App\Filament\Resources\DomainPriceListResource\Pages;

use App\Actions\Domain\FetchOpenproviderPricesAction;
use App\Filament\Resources\DomainPriceListResource;
use App\Models\DomainPriceList;
use App\Models\Setting;
use Filament\Actions;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Illuminate\Support\HtmlString;

class ListDomainPriceLists extends ListRecords
{
    protected static string $resource = DomainPriceListResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),

            Actions\Action::make('syncFromOpenprovider')
                ->label('Sync from Openprovider')
                ->icon('heroicon-o-arrow-path')
                ->color('warning')
                ->modalHeading('Sync Wholesale Prices from Openprovider')
                ->modalDescription(
                    'Fetching current wholesale prices from the Openprovider API. ' .
                    'Changed TLDs are highlighted — retail prices are recalculated using each TLD\'s margin %.'
                )
                ->modalWidth('4xl')
                ->modalSubmitActionLabel('Apply changes')
                ->mountUsing(function (Schema $form) {
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
                                view('filament.actions.op-prices-diff', [
                                    'changes' => $changes ?? [],
                                ])->render()
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
                        ->success()
                        ->send();
                }),
        ];
    }
}
