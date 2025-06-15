<?php

namespace App\Filament\Cashier\Resources\PaymentResource\Pages;

use App\Filament\Cashier\Resources\PaymentResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListPayments extends ListRecords
{
    protected static string $resource = PaymentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // Header actions removed - no more printer functionality
        ];
    }

    public function getTitle(): string
    {
        return 'Payment Processing';
    }
}
