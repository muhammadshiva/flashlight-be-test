<?php

namespace App\Filament\Resources\POSTransactionResource\Pages;

use App\Filament\Resources\POSTransactionResource;
use App\Models\POSTransaction;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreatePOSTransaction extends CreateRecord
{
    protected static string $resource = POSTransactionResource::class;

    protected array $productsData = [];

    protected function mutateFormDataBeforeCreate(array $data): array
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

        // Store products data for after creation
        $this->productsData = $products;

        return $data;
    }

    protected function afterCreate(): void
    {
        $record = $this->getRecord();

        // Attach products to the transaction
        if (isset($this->productsData)) {
            foreach ($this->productsData as $productData) {
                $record->products()->attach($productData['product_id'], [
                    'quantity' => $productData['quantity'],
                    'price' => $productData['price'],
                    'subtotal' => $productData['subtotal'],
                ]);
            }
        }
    }
}
