<?php

namespace App\Filament\Resources\ServiceTypeCategoryResource\Pages;

use App\Filament\Resources\ServiceTypeCategoryResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListServiceTypeCategories extends ListRecords
{
    protected static string $resource = ServiceTypeCategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
