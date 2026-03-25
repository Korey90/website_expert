<?php

namespace App\Filament\Resources;

use App\Filament\Resources\RoleResource\Pages;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleResource extends Resource
{
    protected static ?string $model = Role::class;
    protected static \BackedEnum|string|null $navigationIcon = 'heroicon-o-shield-check';
    protected static \UnitEnum|string|null $navigationGroup = 'Settings';
    protected static ?string $navigationLabel = 'Roles & Permissions';
    protected static ?int $navigationSort = 2;

    /**
     * Group definitions shared between form() and EditRole/CreateRole pages.
     */
    public static function permissionGroups(): array
    {
        return [
            'crm'         => ['label' => 'CRM',          'icon' => 'heroicon-o-building-office-2', 'suffixes' => ['clients', 'leads', 'contracts']],
            'finance'     => ['label' => 'Finance',       'icon' => 'heroicon-o-banknotes',          'suffixes' => ['quotes', 'invoices']],
            'projects'    => ['label' => 'Projects',      'icon' => 'heroicon-o-briefcase',           'suffixes' => ['projects']],
            'templates'   => ['label' => 'Templates',     'icon' => 'heroicon-o-document-duplicate',  'suffixes' => ['contract_templates', 'email_templates', 'sms_templates']],
            'automations' => ['label' => 'Automations',   'icon' => 'heroicon-o-bolt',                'suffixes' => ['automations']],
            'cms'         => ['label' => 'Website CMS',   'icon' => 'heroicon-o-globe-alt',           'suffixes' => ['pages', 'site_sections']],
            'users'       => ['label' => 'Users',         'icon' => 'heroicon-o-users',               'suffixes' => ['users']],
            'reports'     => ['label' => 'Reports',       'icon' => 'heroicon-o-chart-bar',           'suffixes' => ['reports']],
            'system'      => ['label' => 'System',        'icon' => 'heroicon-o-cog-6-tooth',         'suffixes' => ['settings', 'roles', 'pipeline', 'calculator', 'project_templates']],
        ];
    }

    public static function form(Schema $form): Schema
    {
        $allPerms   = Permission::orderBy('name')->get();
        $groupDefs  = static::permissionGroups();

        $groupKeys = array_keys($groupDefs);
        $expandJs  = implode('', array_map(fn ($k) => "window.dispatchEvent(new CustomEvent('expand-section',{detail:{id:'perm-{$k}'}}));", $groupKeys));
        $collapseJs = implode('', array_map(fn ($k) => "window.dispatchEvent(new CustomEvent('collapse-section',{detail:{id:'perm-{$k}'}}));", $groupKeys));

        $permSections = [];
        foreach ($groupDefs as $key => $group) {
            $opts = [];
            foreach ($allPerms as $perm) {
                foreach ($group['suffixes'] as $suffix) {
                    if (str_ends_with($perm->name, '_' . $suffix) || $perm->name === $suffix) {
                        $opts[$perm->name] = str_replace('_', ' ', ucwords($perm->name, '_'));
                        break;
                    }
                }
            }
            if (empty($opts)) {
                continue;
            }
            $permSections[] = Section::make($group['label'])
                ->id("perm-{$key}")
                ->icon($group['icon'])
                ->collapsible()
                ->collapsed()
                ->compact()
                ->columnSpanFull()
                ->schema([
                    Forms\Components\CheckboxList::make("perms_{$key}")
                        ->label('')
                        ->options($opts)
                        ->columns(3)
                        ->bulkToggleable()
                        ->columnSpanFull(),
                ]);
        }

        return $form->schema(array_merge(
            [
                Section::make('Role Details')
                    ->columns(2)
                    ->columnSpanFull()
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Role Name')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(100),
                        Forms\Components\TextInput::make('guard_name')
                            ->label('Guard')
                            ->default('web')
                            ->required(),
                        Forms\Components\Placeholder::make('perm_toggles')
                            ->label('')
                            ->content(new \Illuminate\Support\HtmlString(
                                '<div class="flex gap-2 justify-end">'
                                . '<button type="button" onclick="' . htmlspecialchars($expandJs, ENT_QUOTES) . '" class="inline-flex items-center gap-1.5 rounded-lg px-3 py-1.5 text-xs font-medium text-gray-700 dark:text-gray-300 ring-1 ring-inset ring-gray-300 dark:ring-gray-600 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors cursor-pointer">'
                                . '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="w-3.5 h-3.5"><path d="M13.28 7.78l3.22-3.22v2.69a.75.75 0 001.5 0v-4.5a.75.75 0 00-.75-.75h-4.5a.75.75 0 000 1.5h2.69l-3.22 3.22a.75.75 0 001.06 1.06zM2 17.25v-4.5a.75.75 0 011.5 0v2.69l3.22-3.22a.75.75 0 011.06 1.06L4.56 16.5h2.69a.75.75 0 010 1.5h-4.5a.75.75 0 01-.75-.75z"></path></svg>'
                                . 'Expand All</button>'
                                . '<button type="button" onclick="' . htmlspecialchars($collapseJs, ENT_QUOTES) . '" class="inline-flex items-center gap-1.5 rounded-lg px-3 py-1.5 text-xs font-medium text-gray-700 dark:text-gray-300 ring-1 ring-inset ring-gray-300 dark:ring-gray-600 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors cursor-pointer">'
                                . '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="w-3.5 h-3.5"><path d="M9.22 3.22a.75.75 0 011.06 0l.97.97V1.75a.75.75 0 011.5 0v4.5a.75.75 0 01-.75.75h-4.5a.75.75 0 010-1.5h2.69l-.97-.97a.75.75 0 010-1.06zm1.06 13.56a.75.75 0 01-1.06 0l-.97-.97v2.44a.75.75 0 01-1.5 0v-4.5a.75.75 0 01.75-.75h4.5a.75.75 0 010 1.5H9.31l.97.97a.75.75 0 010 1.06z"></path></svg>'
                                . 'Collapse All</button>'
                                . '</div>'
                            ))
                            ->columnSpanFull(),
                    ]),
            ],
            $permSections
        ));
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('guard_name')
                    ->label('Guard')
                    ->badge()
                    ->color('gray'),
                Tables\Columns\TextColumn::make('permissions_count')
                    ->label('Permissions')
                    ->counts('permissions')
                    ->badge()
                    ->color('primary'),
                Tables\Columns\TextColumn::make('users_count')
                    ->label('Users')
                    ->counts('users')
                    ->badge()
                    ->color('info'),
                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Updated')
                    ->date()
                    ->sortable(),
            ])
            ->actions([ViewAction::make(), EditAction::make(), DeleteAction::make()])
            ->defaultSort('name');
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListRoles::route('/'),
            'create' => Pages\CreateRole::route('/create'),
            'view'   => Pages\ViewRole::route('/{record}'),
            'edit'   => Pages\EditRole::route('/{record}/edit'),
        ];
    }
}
