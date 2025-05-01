<?php

namespace App\Filament\Resources\ServiceTypeCategoryResource\Pages;

use App\Filament\Resources\ServiceTypeCategoryResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditServiceTypeCategory extends EditRecord
{
    protected static string $resource = ServiceTypeCategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
