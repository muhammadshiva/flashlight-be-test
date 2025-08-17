<?php

namespace App\Filament\Cashier\Resources\WashStatusResource\Pages;

use App\Filament\Cashier\Resources\WashStatusResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListWashStatuses extends ListRecords
{
    protected static string $resource = WashStatusResource::class;

    protected function getHeaderActions(): array
    {
        return [
            //
        ];
    }

    public function getTitle(): string
    {
        return 'Wash Status Management';
    }

    protected function getHeaderWidgets(): array
    {
        return [
            //
        ];
    }
}
