<?php

namespace App\Filament\Resources\CustomerVehicleResource\Pages;

use App\Filament\Resources\CustomerVehicleResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditCustomerVehicle extends EditRecord
{
    protected static string $resource = CustomerVehicleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
            Actions\ForceDeleteAction::make(),
            Actions\RestoreAction::make(),
        ];
    }
}
