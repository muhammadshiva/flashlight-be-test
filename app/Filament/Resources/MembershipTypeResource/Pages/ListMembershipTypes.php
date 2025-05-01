<?php

namespace App\Filament\Resources\MembershipTypeResource\Pages;

use App\Filament\Resources\MembershipTypeResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListMembershipTypes extends ListRecords
{
    protected static string $resource = MembershipTypeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
