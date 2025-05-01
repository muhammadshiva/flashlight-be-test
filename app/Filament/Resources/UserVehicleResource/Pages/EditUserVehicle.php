<?php

namespace App\Filament\Resources\UserVehicleResource\Pages;

use App\Filament\Resources\UserVehicleResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditUserVehicle extends EditRecord
{
    protected static string $resource = UserVehicleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
