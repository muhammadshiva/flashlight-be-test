<?php

namespace App\Filament\Cashier\Resources;

use App\Filament\Cashier\Resources\PaymentResource\Pages;
use App\Models\WashTransaction;
use App\Models\Payment;
use App\Models\Product;
use App\Services\ThermalPrinterService;
use App\Services\QRISService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Filament\Notifications\Notification;
use Filament\Tables\Actions\Action;

class PaymentResource extends Resource
{
    protected static ?string $model = WashTransaction::class;

    protected static ?string $navigationIcon = 'heroicon-o-credit-card';

    protected static ?string $navigationLabel = 'Payment Processing';

    protected static ?string $recordTitleAttribute = 'transaction_number';

    protected static ?string $label = 'Payment Processing';

    protected static ?string $pluralLabel = 'Payment Processing';

    public static function form(Form $form): Form
    {
        return $form->schema([]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->query(
                WashTransaction::query()
                    ->where('status', WashTransaction::STATUS_COMPLETED)
                    ->whereDoesntHave('payments', function ($query) {
                        $query->where('status', Payment::STATUS_COMPLETED);
                    })
                    ->with(['customer.user', 'customerVehicle.vehicle', 'products', 'primaryProduct'])
            )
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('No')
                    ->sortable(),

                Tables\Columns\TextColumn::make('customerVehicle.vehicle.name')
                    ->label('Motorbike')
                    ->formatStateUsing(function ($record) {
                        $vehicleName = $record->customerVehicle->vehicle->name ?? 'N/A';
                        $licensePlate = $record->customerVehicle->license_plate ?? '';
                        return $vehicleName . ($licensePlate ? ' (' . $licensePlate . ')' : '');
                    })
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('customer.user.name')
                    ->label('Customer Name')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('additional_services')
                    ->label('Additional Services')
                    ->getStateUsing(function ($record) {
                        $additionalProducts = $record->products()
                            ->where('product_id', '!=', $record->product_id)
                            ->get();

                        if ($additionalProducts->isEmpty()) {
                            return 'None';
                        }

                        return $additionalProducts->pluck('name')->join(', ');
                    }),

                Tables\Columns\TextColumn::make('food_drinks')
                    ->label('Food and Drinks')
                    ->getStateUsing(function ($record) {
                        $foodDrinks = $record->products()
                            ->whereHas('category', function ($query) {
                                $query->whereIn('name', ['Food', 'Drinks', 'Makanan', 'Minuman']);
                            })
                            ->get();

                        if ($foodDrinks->isEmpty()) {
                            return 'None';
                        }

                        return $foodDrinks->map(function ($product) {
                            return $product->name . ' (x' . $product->pivot->quantity . ')';
                        })->join(', ');
                    }),

                Tables\Columns\TextColumn::make('total_price')
                    ->label('Total Amount')
                    ->money('IDR')
                    ->sortable(),

                Tables\Columns\TextColumn::make('wash_date')
                    ->label('Date')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
            ])
            ->actions([
                Action::make('pay_now')
                    ->label('Pay Now')
                    ->icon('heroicon-o-credit-card')
                    ->color('success')
                    ->form([
                        Forms\Components\Select::make('payment_method')
                            ->label('Payment Method')
                            ->options([
                                'cash' => 'Cash',
                                'qris' => 'QRIS'
                            ])
                            ->required()
                            ->reactive()
                            ->afterStateUpdated(fn($state, callable $set) => $set('amount_paid', null)),

                        Forms\Components\TextInput::make('amount_paid')
                            ->label('Amount Paid')
                            ->numeric()
                            ->prefix('IDR')
                            ->visible(fn(callable $get) => $get('payment_method') === 'cash')
                            ->required(fn(callable $get) => $get('payment_method') === 'cash')
                            ->reactive()
                            ->afterStateUpdated(function ($state, callable $set, callable $get, $record) {
                                if ($get('payment_method') === 'cash' && $state && $record) {
                                    $change = $state - $record->total_price;
                                    $set('change_amount', max(0, $change));
                                }
                            }),

                        Forms\Components\TextInput::make('change_amount')
                            ->label('Change')
                            ->prefix('IDR')
                            ->disabled()
                            ->visible(fn(callable $get) => $get('payment_method') === 'cash'),

                        Forms\Components\Placeholder::make('qris_info')
                            ->label('QRIS Payment')
                            ->content('QRIS code will be generated for customer to scan')
                            ->visible(fn(callable $get) => $get('payment_method') === 'qris'),

                        Forms\Components\ViewField::make('qris_display')
                            ->label('QRIS Code')
                            ->view('filament.qris-display')
                            ->visible(fn(callable $get) => $get('payment_method') === 'qris')
                            ->viewData([]),

                        Forms\Components\Checkbox::make('print_receipt')
                            ->label('Print Receipt')
                            ->default(true),
                    ])
                    ->action(function (array $data, WashTransaction $record) {
                        // Validate cash payment
                        if ($data['payment_method'] === 'cash' && $data['amount_paid'] < $record->total_price) {
                            Notification::make()
                                ->title('Insufficient Amount')
                                ->body('Amount paid must be at least IDR ' . number_format($record->total_price))
                                ->danger()
                                ->send();
                            return;
                        }

                        // Handle QRIS payment
                        if ($data['payment_method'] === 'qris') {
                            $qrisService = new QRISService();
                            $qrisData = $qrisService->generateQRIS($record);

                            // Create pending payment record
                            $payment = Payment::create([
                                'wash_transaction_id' => $record->id,
                                'staff_id' => Auth::user()->staff->id ?? 1,
                                'method' => 'qris',
                                'amount_paid' => $record->total_price,
                                'change_amount' => 0,
                                'qris_transaction_id' => $qrisData['transaction_id'],
                                'status' => Payment::STATUS_PENDING,
                                'receipt_data' => [
                                    'transaction' => $record->toArray(),
                                    'products' => $record->products->toArray(),
                                    'customer' => $record->customer->toArray(),
                                    'vehicle' => $record->customerVehicle->toArray(),
                                    'qris_data' => $qrisData,
                                ]
                            ]);

                            Notification::make()
                                ->title('QRIS Payment Initiated')
                                ->body('Waiting for customer to complete payment')
                                ->info()
                                ->send();
                            return;
                        }

                        // Create payment record for cash
                        $payment = Payment::create([
                            'wash_transaction_id' => $record->id,
                            'staff_id' => Auth::user()->staff->id ?? 1,
                            'method' => $data['payment_method'],
                            'amount_paid' => $data['amount_paid'],
                            'change_amount' => max(0, $data['amount_paid'] - $record->total_price),
                            'status' => Payment::STATUS_COMPLETED,
                            'paid_at' => now(),
                            'receipt_data' => [
                                'transaction' => $record->toArray(),
                                'products' => $record->products->toArray(),
                                'customer' => $record->customer->toArray(),
                                'vehicle' => $record->customerVehicle->toArray(),
                            ]
                        ]);

                        // Update transaction payment method
                        $record->update([
                            'payment_method' => 'cash'
                        ]);

                        // Handle receipt printing
                        if ($data['print_receipt']) {
                            $printerService = new ThermalPrinterService();

                            if ($printerService->printReceipt($payment)) {
                                Notification::make()
                                    ->title('Payment Processed Successfully')
                                    ->body('Receipt printed successfully')
                                    ->success()
                                    ->send();
                            } else {
                                Notification::make()
                                    ->title('Payment Processed')
                                    ->body('Receipt printing failed - please check printer connection')
                                    ->warning()
                                    ->send();
                            }
                        } else {
                            Notification::make()
                                ->title('Payment Processed Successfully')
                                ->success()
                                ->send();
                        }
                    })
                    ->modalHeading('Process Payment')
                    ->modalWidth('md'),
            ])
            ->bulkActions([])
            ->defaultSort('id', 'desc')
            ->poll('10s');
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPayments::route('/'),
        ];
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit($record): bool
    {
        return false;
    }

    public static function canDelete($record): bool
    {
        return false;
    }

    public static function canView($record): bool
    {
        return false;
    }
}
