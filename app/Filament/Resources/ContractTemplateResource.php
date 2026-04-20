<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ContractTemplateResource\Pages;
use App\Models\ContractTemplate;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Html;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;

class ContractTemplateResource extends BaseResource
{
    protected static ?string $model = ContractTemplate::class;
    protected static \BackedEnum|string|null $navigationIcon = 'heroicon-o-document-text';
    protected static \UnitEnum|string|null $navigationGroup = 'Finance';
    protected static ?int $navigationSort = 4;
    protected static ?string $navigationLabel = 'Contract Templates';

    public static function infolist(Schema $schema): Schema
    {
        return $schema->columns(1)->schema([
            Section::make()
                ->columns(4)
                ->schema([
                    TextEntry::make('name')
                        ->label('Template Name')
                        ->weight('bold')
                        ->size('lg')
                        ->columnSpan(2),

                    TextEntry::make('type')
                        ->label('Type')
                        ->badge()
                        ->formatStateUsing(fn ($state) => match ($state) {
                            'web_development' => 'Web Development',
                            'maintenance'     => 'Maintenance',
                            default           => $state,
                        })
                        ->color(fn ($state) => match ($state) {
                            'web_development' => 'info',
                            'maintenance'     => 'warning',
                            default           => 'gray',
                        }),

                    IconEntry::make('is_active')
                        ->label('Active')
                        ->boolean()
                        ->trueIcon('heroicon-o-check-circle')
                        ->falseIcon('heroicon-o-x-circle')
                        ->trueColor('success')
                        ->falseColor('danger'),

                    TextEntry::make('language')
                        ->label('Language')
                        ->badge()
                        ->formatStateUsing(fn ($state) => match ($state) {
                            'en' => '🇬🇧 English',
                            'pl' => '🇵🇱 Polish',
                            'pt' => '🇵🇹 Portuguese',
                            default => strtoupper($state),
                        })
                        ->color('gray'),

                    TextEntry::make('created_at')
                        ->label('Created')
                        ->dateTime('d M Y, H:i')
                        ->since(),

                    TextEntry::make('updated_at')
                        ->label('Last Updated')
                        ->dateTime('d M Y, H:i')
                        ->since(),
                ]),

            Section::make('Contract Content Preview')
                ->description('Placeholders like {{legal.company_name}} will be replaced with actual values when used in a contract.')
                ->schema([
                    Html::make(function ($record) {
                        $name = e($record->name);
                        return <<<HTML
<style>
  .cp-wrap{background:#fff;color:#1a1a1a;border-radius:12px;overflow:hidden;border:1px solid rgba(0,0,0,.12)}
  .dark .cp-wrap{background:#18181b;color:#e4e4e7;border:1px solid rgba(255,255,255,.08)}
  .cp-toolbar{display:flex;align-items:center;gap:8px;padding:8px 14px;background:rgba(0,0,0,.04);border-bottom:1px solid rgba(0,0,0,.08)}
  .dark .cp-toolbar{background:rgba(255,255,255,.03);border-bottom:1px solid rgba(255,255,255,.07)}
  .contract-preview{font-family:Georgia,"Times New Roman",serif;font-size:14px;line-height:1.8;padding:36px 48px;max-height:70vh;overflow-y:auto}
  .contract-preview h2{font-family:system-ui,sans-serif;font-size:.9rem;font-weight:700;letter-spacing:.06em;text-transform:uppercase;margin:2rem 0 .5rem;padding-bottom:.35rem;border-bottom:2px solid rgba(0,0,0,.15)}
  .dark .contract-preview h2{border-bottom-color:rgba(255,255,255,.12)}
  .contract-preview h3{font-family:system-ui,sans-serif;font-size:.8rem;font-weight:600;letter-spacing:.04em;text-transform:uppercase;margin:1.4rem 0 .4rem;opacity:.6}
  .contract-preview p{margin:.65rem 0}
  .contract-preview ul,.contract-preview ol{margin:.5rem 0 .5rem 1.5rem;padding:0}
  .contract-preview li{margin:.3rem 0}
  .contract-preview strong{font-weight:700}
  .contract-preview a{text-decoration:underline;opacity:.7}
  .contract-preview table{width:100%;border-collapse:collapse;margin-top:1.5rem}
  .contract-preview td{vertical-align:top;padding-right:20px}
  .cp-print-title{display:none;font-family:system-ui,sans-serif;font-size:14pt;font-weight:700;text-align:center;text-transform:uppercase;letter-spacing:.05em;margin-bottom:2rem;padding-bottom:1rem;border-bottom:2px solid #111}
  .cp-btn{display:inline-flex;align-items:center;gap:5px;font-family:system-ui,sans-serif;font-size:11px;font-weight:500;padding:3px 10px;border-radius:6px;border:1px solid rgba(0,0,0,.15);background:rgba(0,0,0,.04);color:inherit;cursor:pointer;transition:background .15s;line-height:1.4}
  .dark .cp-btn{border-color:rgba(255,255,255,.12);background:rgba(255,255,255,.04)}
  .cp-btn:hover{background:rgba(0,0,0,.09)}
  .dark .cp-btn:hover{background:rgba(255,255,255,.09)}
  .cp-btn svg{width:12px;height:12px;flex-shrink:0}
  @media print{
    *{visibility:hidden!important}
    .contract-preview,.contract-preview *{visibility:visible!important}
    .cp-wrap{position:absolute!important;top:0;left:0;width:100%;border:none!important}
    .contract-preview{position:static!important;max-height:none!important;overflow:visible!important;padding:15mm 20mm!important;background:#fff!important;color:#111!important;font-size:11pt!important;line-height:1.7!important}
    .cp-toolbar{display:none!important}
    .cp-print-title{display:block!important}
    .contract-preview h2{page-break-after:avoid}
    .contract-preview p,.contract-preview li{orphans:3;widows:3}
  }
</style>
<div x-data="{ copied: false }" class="cp-wrap" data-title="{$name}">
  <div class="cp-toolbar">
    <span style="width:10px;height:10px;border-radius:50%;background:#f87171;display:inline-block"></span>
    <span style="width:10px;height:10px;border-radius:50%;background:#fbbf24;display:inline-block"></span>
    <span style="width:10px;height:10px;border-radius:50%;background:#34d399;display:inline-block"></span>
    <span style="font-family:monospace;font-size:11px;opacity:.45;margin-left:2px;flex:1">{$name} — preview</span>
    <button class="cp-btn" @click="navigator.clipboard.writeText(\$root.dataset.title+'\\n\\n'+\$root.querySelector('.contract-preview').innerText).then(()=>{copied=true;setTimeout(()=>copied=false,2000)})">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="9" y="9" width="13" height="13" rx="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/></svg>
      <span x-text="copied ? 'Copied!' : 'Copy'">Copy</span>
    </button>
    <button class="cp-btn" @click="window.print()">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 6 2 18 2 18 9"/><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><rect x="6" y="14" width="12" height="8"/></svg>
      <span>Print</span>
    </button>
  </div>
  <div class="contract-preview">
    <h1 class="cp-print-title">{$name}</h1>
    {$record->content}
  </div>
</div>
HTML;
                    })->columnSpanFull(),
                ]),
        ]);
    }

    public static function form(Schema $form): Schema
    {
        return $form->schema([
            Section::make('Template Info')
                ->columns(3)
                ->schema([
                    Forms\Components\TextInput::make('name')
                        ->required()
                        ->maxLength(255)
                        ->columnSpan(2),
                    Forms\Components\Toggle::make('is_active')
                        ->label('Active')
                        ->default(true)
                        ->columnSpan(1),
                    Forms\Components\Select::make('type')
                        ->options([
                            'web_development' => 'Web Development Agreement',
                            'maintenance'     => 'Website Maintenance Agreement',
                        ])
                        ->required(),
                    Forms\Components\Select::make('language')
                        ->options([
                            'en' => '🇬🇧 English',
                            'pl' => '🇵🇱 Polish',
                            'pt' => '🇵🇹 Portuguese',
                        ])
                        ->required(),
                ]),

            Section::make('Content')
                ->schema([
                    Forms\Components\RichEditor::make('content')
                        ->label('')
                        ->columnSpanFull()
                        ->fileAttachmentsDisk('public')
                        ->fileAttachmentsDirectory('contract-templates')
                        ->helperText('Use {{legal.company_name}}, {{legal.company_email}}, {{legal.deposit_percent}}, {{legal.payment_terms_days}} etc. as dynamic placeholders.'),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->weight('bold'),
                Tables\Columns\TextColumn::make('type')
                    ->badge()
                    ->formatStateUsing(fn ($state) => match ($state) {
                        'web_development' => 'Web Development',
                        'maintenance'     => 'Maintenance',
                        default           => $state,
                    })
                    ->color(fn ($state) => match ($state) {
                        'web_development' => 'info',
                        'maintenance'     => 'warning',
                        default           => 'gray',
                    }),
                Tables\Columns\TextColumn::make('language')
                    ->badge()
                    ->formatStateUsing(fn ($state) => match ($state) {
                        'en' => '🇬🇧 EN',
                        'pl' => '🇵🇱 PL',
                        'pt' => '🇵🇹 PT',
                        default => strtoupper($state),
                    })
                    ->color('gray'),
                Tables\Columns\IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean(),
                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Last updated')
                    ->since()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->options([
                        'web_development' => 'Web Development',
                        'maintenance'     => 'Maintenance',
                    ]),
                Tables\Filters\SelectFilter::make('language')
                    ->options([
                        'en' => 'English',
                        'pl' => 'Polish',
                        'pt' => 'Portuguese',
                    ]),
                Tables\Filters\TernaryFilter::make('is_active')->label('Active'),
            ])
            ->actions([
                ViewAction::make(),
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->bulkActions([
                DeleteBulkAction::make(),
            ])
            ->defaultSort('type')
            ->reorderable('id');
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListContractTemplates::route('/'),
            'create' => Pages\CreateContractTemplate::route('/create'),
            'view'   => Pages\ViewContractTemplate::route('/{record}'),
            'edit'   => Pages\EditContractTemplate::route('/{record}/edit'),
        ];
    }
}
