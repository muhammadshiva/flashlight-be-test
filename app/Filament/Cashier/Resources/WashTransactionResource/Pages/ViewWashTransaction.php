<?php

namespace App\Filament\Cashier\Resources\WashTransactionResource\Pages;

use App\Filament\Cashier\Resources\WashTransactionResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewWashTransaction extends ViewRecord
{
    protected static string $resource = WashTransactionResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
