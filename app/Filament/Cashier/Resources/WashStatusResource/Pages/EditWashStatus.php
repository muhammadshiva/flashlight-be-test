<?php

namespace App\Filament\Cashier\Resources\WashStatusResource\Pages;

use App\Filament\Cashier\Resources\WashStatusResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Filament\Notifications\Notification;

class EditWashStatus extends EditRecord
{
    protected static string $resource = WashStatusResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make()
                ->hidden(), // Hide delete action for wash status management
        ];
    }

    public function getTitle(): string
    {
        return 'Update Wash Status';
    }

    protected function getRedirectUrl(): string
    {
        return $this->previousUrl ?? $this->getResource()::getUrl('index');
    }

    protected function getSavedNotification(): ?Notification
    {
        return Notification::make()
            ->success()
            ->title('Status Updated')
            ->body('Wash transaction status has been updated successfully.');
    }
}
