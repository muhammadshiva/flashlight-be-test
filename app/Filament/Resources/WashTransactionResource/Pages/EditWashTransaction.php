<?php

namespace App\Filament\Resources\WashTransactionResource\Pages;

use App\Filament\Resources\WashTransactionResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditWashTransaction extends EditRecord
{
    protected static string $resource = WashTransactionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
