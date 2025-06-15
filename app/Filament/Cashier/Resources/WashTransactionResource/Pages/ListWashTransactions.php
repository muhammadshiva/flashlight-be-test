<?php

namespace App\Filament\Cashier\Resources\WashTransactionResource\Pages;

use App\Filament\Cashier\Resources\WashTransactionResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListWashTransactions extends ListRecords
{
    protected static string $resource = WashTransactionResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
