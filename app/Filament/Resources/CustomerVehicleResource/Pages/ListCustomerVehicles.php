<?php

namespace App\Filament\Resources\CustomerVehicleResource\Pages;

use App\Filament\Resources\CustomerVehicleResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListCustomerVehicles extends ListRecords
{
    protected static string $resource = CustomerVehicleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
