<?php

namespace App\Filament\Cashier\Pages;

use Filament\Pages\Dashboard as BasePage;

class Dashboard extends BasePage
{
    protected static ?string $navigationIcon = 'heroicon-o-home';

    protected ?string $heading = 'Cashier Dashboard';

    protected static string $view = 'filament.cashier.pages.dashboard';
}
