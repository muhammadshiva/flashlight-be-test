<?php

namespace App\Filament\Resources\POSTransactionResource\Pages;

use App\Filament\Resources\POSTransactionResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditPOSTransaction extends EditRecord
{
    protected static string $resource = POSTransactionResource::class;

    protected array $productsData = [];

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        // Extract products data from the form
        $products = $data['products'] ?? [];
        unset($data['products']);

        // Calculate totals if not provided
        if (!isset($data['subtotal'])) {
            $data['subtotal'] = collect($products)->sum('subtotal');
        }

        if (!isset($data['total_amount'])) {
            $taxAmount = $data['tax_amount'] ?? 0;
            $discountAmount = $data['discount_amount'] ?? 0;
            $data['total_amount'] = $data['subtotal'] + $taxAmount - $discountAmount;
        }

        // Store products data for after save
        $this->productsData = $products;

        return $data;
    }

    protected function afterSave(): void
    {
        $record = $this->getRecord();

        // Sync products with the transaction
        if (isset($this->productsData)) {
            $productsToSync = [];
            foreach ($this->productsData as $productData) {
                $productsToSync[$productData['product_id']] = [
                    'quantity' => $productData['quantity'],
                    'price' => $productData['price'],
                    'subtotal' => $productData['subtotal'],
                ];
            }
            $record->products()->sync($productsToSync);
        }
    }
}
