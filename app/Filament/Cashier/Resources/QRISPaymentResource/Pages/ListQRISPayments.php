<?php

namespace App\Filament\Cashier\Resources\QRISPaymentResource\Pages;

use App\Filament\Cashier\Resources\QRISPaymentResource;
use Filament\Resources\Pages\ListRecords;

class ListQRISPayments extends ListRecords
{
    protected static string $resource = QRISPaymentResource::class;

    public function getTitle(): string
    {
        return 'QRIS Payments';
    }
}
