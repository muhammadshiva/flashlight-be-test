<?php

namespace App\Filament\Resources\WashTransactionResource\Pages;

use App\Filament\Resources\WashTransactionResource;
use App\Models\Product;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

class EditWashTransaction extends EditRecord
{
    protected static string $resource = WashTransactionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        $products = $data['products'] ?? [];
        unset($data['products']);

        $record->update($data);

        // Sync products
        $record->products()->detach();
        if (!empty($products)) {
            foreach ($products as $product) {
                $productModel = Product::find($product['product_id']);
                if ($productModel) {
                    $record->products()->attach($product['product_id'], [
                        'quantity' => $product['quantity'],
                        'price' => $productModel->price,
                        'subtotal' => $productModel->price * $product['quantity'],
                    ]);
                }
            }
        }

        return $record;
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $record = $this->getRecord();

        $data['products'] = $record->products->map(function ($product) {
            return [
                'product_id' => $product->id,
                'quantity' => $product->pivot->quantity,
                'price' => $product->pivot->price,
                'subtotal' => $product->pivot->subtotal,
            ];
        })->toArray();

        return $data;
    }
}
