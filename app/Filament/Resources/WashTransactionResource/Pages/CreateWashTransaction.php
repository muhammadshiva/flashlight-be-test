<?php

namespace App\Filament\Resources\WashTransactionResource\Pages;

use App\Filament\Resources\WashTransactionResource;
use App\Models\Product;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateWashTransaction extends CreateRecord
{
    protected static string $resource = WashTransactionResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        $products = $data['products'] ?? [];
        unset($data['products']);

        $totalPrice = 0;

        $transaction = static::getModel()::create($data);

        if (!empty($products)) {
            foreach ($products as $product) {
                $productModel = Product::find($product['product_id']);
                if ($productModel) {
                    $price = floatval($productModel->price);
                    $quantity = intval($product['quantity']);
                    $subtotal = $price * $quantity;
                    $totalPrice += $subtotal;

                    $transaction->products()->attach($product['product_id'], [
                        'quantity' => $quantity,
                        'price' => $price,
                        'subtotal' => $subtotal,
                    ]);
                }
            }
        }

        // Update total price after all products are attached
        $transaction->update(['total_price' => $totalPrice]);

        return $transaction;
    }
}
