<?php

namespace App\Filament\Resources\POSTransactionResource\Pages;

use App\Filament\Resources\POSTransactionResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListPOSTransactions extends ListRecords
{
    protected static string $resource = POSTransactionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
