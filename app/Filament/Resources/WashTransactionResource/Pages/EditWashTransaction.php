<?php

namespace App\Filament\Resources\WashTransactionResource\Pages;

use App\Filament\Resources\WashTransactionResource;
use App\Models\Product;
use App\Models\User;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class EditWashTransaction extends EditRecord
{
    protected static string $resource = WashTransactionResource::class;

    protected function getHeaderActions(): array
    {
        // Only show delete action for non-cashiers
        if (Auth::user()->type !== User::TYPE_CASHIER) {
            return [
                Actions\DeleteAction::make(),
            ];
        }

        return [];
    }

    public function mount(int|string $record): void
    {
        parent::mount($record);

        // Make form readonly for cashiers
        if (Auth::user()->type === User::TYPE_CASHIER) {
            $this->form->disable();
        }
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        // Don't allow updates for cashiers
        if (Auth::user()->type === User::TYPE_CASHIER) {
            return $record;
        }

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

    // Change page title based on user role
    public function getTitle(): string
    {
        if (Auth::user()->type === User::TYPE_CASHIER) {
            return 'View Wash Transaction';
        }

        return parent::getTitle();
    }

    // Hide the save button for cashiers
    protected function getSaveFormAction(): Actions\Action
    {
        $action = parent::getSaveFormAction();

        if (Auth::user()->type === User::TYPE_CASHIER) {
            $action->hidden();
        }

        return $action;
    }
}
