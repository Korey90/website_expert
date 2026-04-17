<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;

class CalculatorAdminPage extends Page
{
    protected string $view = 'filament.pages.calculator-admin';

    protected static \BackedEnum|string|null $navigationIcon = 'heroicon-o-calculator';
    protected static \UnitEnum|string|null   $navigationGroup = 'Settings';
    protected static ?string $navigationLabel = 'Calculator';
    protected static ?int    $navigationSort  = 5;
    protected static ?string $title           = 'Calculator Management';
    protected static ?string $slug            = 'calculator';
}
