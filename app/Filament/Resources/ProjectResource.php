<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProjectResource\Pages;
use App\Filament\Resources\ProjectResource\RelationManagers\MessagesRelationManager;
use App\Models\Client;
use App\Models\Project;
use App\Models\ProjectTemplate;
use App\Models\User;
use Filament\Forms;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Table;

class ProjectResource extends Resource
{
    protected static ?string $model = Project::class;
    protected static \BackedEnum|string|null $navigationIcon = 'heroicon-o-briefcase';
    protected static \UnitEnum|string|null $navigationGroup = 'Projects';
    protected static ?int $navigationSort = 1;

    public static function form(Schema $form): Schema
    {
        return $form->schema([
            Section::make('Project Details')
                ->columns(2)
                ->schema([
                    Forms\Components\TextInput::make('title')->required()->maxLength(255)->columnSpanFull(),
                    Forms\Components\Select::make('client_id')
                        ->label('Client')
                        ->options(Client::pluck('company_name', 'id'))
                        ->searchable()
                        ->required(),
                    Forms\Components\Select::make('assigned_to')
                        ->label('Lead Developer')
                        ->options(User::whereHas('roles', fn ($q) => $q->whereIn('name', ['admin', 'manager', 'developer']))->pluck('name', 'id'))
                        ->searchable(),
                    Forms\Components\Select::make('status')
                        ->options(['draft' => 'Draft', 'active' => 'Active', 'on_hold' => 'On Hold', 'completed' => 'Completed', 'cancelled' => 'Cancelled'])
                        ->default('draft')->required(),
                    Forms\Components\Select::make('service_type')
                        ->options(['wizytowka' => 'Business Card Site', 'landing' => 'Landing Page', 'ecommerce' => 'E-Commerce', 'aplikacja' => 'Web Application', 'seo' => 'SEO', 'other' => 'Other']),
                    Forms\Components\Select::make('template_id')
                        ->label('Project Template')
                        ->options(ProjectTemplate::where('is_active', true)->pluck('name', 'id'))
                        ->reactive()
                        ->helperText('Selecting a template will auto-create project phases on save.'),
                    Forms\Components\Select::make('currency')->options(['GBP' => '£ GBP', 'EUR' => '€ EUR', 'USD' => '$ USD'])->default('GBP'),
                    Forms\Components\TextInput::make('budget')->numeric()->prefix('£'),
                    Forms\Components\DatePicker::make('start_date'),
                    Forms\Components\DatePicker::make('deadline'),
                ]),

            Forms\Components\Textarea::make('description')->rows(4)->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('title')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('client.company_name')->label('Client')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('service_type')->badge(),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn ($state) => match($state) { 'draft' => 'gray', 'active' => 'success', 'on_hold' => 'warning', 'completed' => 'primary', 'cancelled' => 'danger', default => 'gray' }),
                Tables\Columns\TextColumn::make('budget')->money('GBP')->sortable(),
                Tables\Columns\TextColumn::make('deadline')->date()->sortable(),
                Tables\Columns\TextColumn::make('assignedTo.name')->label('Developer'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options(['draft' => 'Draft', 'active' => 'Active', 'on_hold' => 'On Hold', 'completed' => 'Completed', 'cancelled' => 'Cancelled']),
                Tables\Filters\SelectFilter::make('service_type')
                    ->options(['wizytowka' => 'Business Card', 'landing' => 'Landing', 'ecommerce' => 'E-Commerce', 'aplikacja' => 'Application']),
                Tables\Filters\TrashedFilter::make(),
            ])
            ->actions([
                ViewAction::make(),
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getRelations(): array
    {
        return [
            MessagesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListProjects::route('/'),
            'create' => Pages\CreateProject::route('/create'),
            'view'   => Pages\ViewProject::route('/{record}'),
            'edit'   => Pages\EditProject::route('/{record}/edit'),
            'tasks'  => Pages\ManageProjectTasks::route('/{record}/tasks'),
        ];
    }

    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        return parent::getEloquentQuery()->withTrashed();
    }
}

