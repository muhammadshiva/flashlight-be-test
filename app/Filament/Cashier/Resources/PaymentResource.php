<?php

namespace App\Filament\Cashier\Resources;

use App\Filament\Cashier\Resources\PaymentResource\Pages;
use App\Models\WashTransaction;
use App\Models\Payment;
use App\Models\Product;
use App\Services\QRISService;
use App\Services\FCMService;
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
                    ->whereIn('status', [WashTransaction::STATUS_IN_PROGRESS, WashTransaction::STATUS_PENDING])
                    ->whereDoesntHave('payments', function ($query) {
                        $query->where('status', Payment::STATUS_COMPLETED);
                    })
                    ->with(['customer.user', 'customerVehicle.vehicle', 'products', 'primaryProduct', 'payments'])
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

                Tables\Columns\TextColumn::make('payment_method')
                    ->label('Payment Method')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'cash' => 'success',
                        'transfer' => 'info',
                        'cashless' => 'warning',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn(string $state): string => match ($state) {
                        'cash' => 'Cash',
                        'transfer' => 'Transfer',
                        'cashless' => 'Cashless',
                        default => ucfirst($state),
                    })
                    ->sortable(),

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
                    ->label(function (WashTransaction $record) {
                        // Check if payment already exists
                        $hasCompletedPayment = $record->payments()->where('status', Payment::STATUS_COMPLETED)->exists();
                        return $hasCompletedPayment ? 'Completed' : 'Process Payment';
                    })
                    ->icon(function (WashTransaction $record) {
                        $hasCompletedPayment = $record->payments()->where('status', Payment::STATUS_COMPLETED)->exists();
                        return $hasCompletedPayment ? 'heroicon-o-check-circle' : 'heroicon-o-credit-card';
                    })
                    ->color(function (WashTransaction $record) {
                        $hasCompletedPayment = $record->payments()->where('status', Payment::STATUS_COMPLETED)->exists();
                        return $hasCompletedPayment ? 'gray' : 'success';
                    })
                    ->disabled(function (WashTransaction $record) {
                        // Disable if payment already completed
                        return $record->payments()->where('status', Payment::STATUS_COMPLETED)->exists();
                    })
                    ->form([
                        Forms\Components\Select::make('payment_method')
                            ->label('Payment Method')
                            ->options([
                                'cash' => 'Cash',
                                'transfer' => 'Bank Transfer',
                                'qris' => 'QRIS (Not Available)'
                            ])
                            ->required()
                            ->reactive()
                            ->default(function (WashTransaction $record) {
                                // Use the payment method sent from mobile app as default
                                $mobilePaymentMethod = $record->payment_method;

                                // Map mobile app payment methods to our form options
                                return match ($mobilePaymentMethod) {
                                    'cash' => 'cash',
                                    'cashless' => 'transfer', // Map cashless to transfer for now
                                    'transfer' => 'transfer',
                                    default => 'cash' // Default to cash if not specified
                                };
                            })
                            ->helperText(function (WashTransaction $record) {
                                $mobileMethod = $record->payment_method ?? 'not specified';
                                return "Customer selected: " . ucfirst($mobileMethod) . " via mobile app";
                            })
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

                        Forms\Components\Placeholder::make('transfer_info')
                            ->label('Bank Transfer Payment')
                            ->content('Customer will transfer payment outside the system. Click "Confirm Transfer" once you have verified that the transfer has been completed.')
                            ->visible(fn(callable $get) => $get('payment_method') === 'transfer'),

                        Forms\Components\Placeholder::make('qris_info')
                            ->label('QRIS Payment')
                            ->content('QRIS feature is currently not available. Please use Cash or Transfer payment method.')
                            ->visible(fn(callable $get) => $get('payment_method') === 'qris'),
                    ])
                    ->action(function (array $data, WashTransaction $record) {
                        // Skip QRIS payment - not available
                        if ($data['payment_method'] === 'qris') {
                            Notification::make()
                                ->title('QRIS Not Available')
                                ->body('QRIS payment method is currently not available. Please use Cash or Transfer payment method.')
                                ->warning()
                                ->send();
                            return;
                        }

                        // Validate cash payment
                        if ($data['payment_method'] === 'cash' && $data['amount_paid'] < $record->total_price) {
                            Notification::make()
                                ->title('Insufficient Amount')
                                ->body('Amount paid must be at least IDR ' . number_format($record->total_price))
                                ->danger()
                                ->send();
                            return;
                        }

                        // Handle QRIS payment (legacy code - will not be reached due to early return above)
                        if ($data['payment_method'] === 'qris') {
                            $qrisService = new QRISService();
                            $qrisData = $qrisService->generateQRIS($record);

                            // Create pending payment record
                            $payment = Payment::create([
                                'wash_transaction_id' => $record->id,
                                'user_id' => Auth::user()->id,
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

                            // Send FCM notification for QRIS payment initiated using Laravel Notification
                            try {
                                $customer = $record->customer;
                                $customerUser = $customer ? $customer->user : null;

                                if ($customerUser && $customerUser->hasFcmToken()) {
                                    $customerUser->notify(new \App\Notifications\FcmNotification(
                                        'QRIS Payment Initiated',
                                        "QRIS payment for {$customerUser->name}'s vehicle wash is waiting for completion. Amount: IDR " . number_format($payment->amount_paid),
                                        [
                                            'type' => 'qris_initiated',
                                            'payment_id' => $payment->id,
                                            'transaction_id' => $record->id,
                                            'qris_transaction_id' => $payment->qris_transaction_id,
                                            'amount' => $payment->amount_paid,
                                            'customer_name' => $customerUser->name,
                                            'transaction_number' => $record->transaction_number,
                                        ]
                                    ));

                                    Notification::make()
                                        ->title('QRIS Payment Initiated')
                                        ->body('Waiting for customer to complete payment. Notification sent successfully.')
                                        ->info()
                                        ->send();
                                } else {
                                    $debugInfo = [
                                        'customer_exists' => $customer !== null,
                                        'user_exists' => $customerUser !== null,
                                        'fcm_token_exists' => $customerUser ? $customerUser->hasFcmToken() : false,
                                        'fcm_token_value' => $customerUser && $customerUser->fcm_token ? 'has_value' : 'empty',
                                    ];

                                    Notification::make()
                                        ->title('QRIS Payment Initiated')
                                        ->body('Waiting for customer to complete payment. Debug: ' . json_encode($debugInfo))
                                        ->warning()
                                        ->send();
                                }
                            } catch (\Exception $e) {
                                Notification::make()
                                    ->title('QRIS Payment Initiated')
                                    ->body('Waiting for customer to complete payment. (FCM notification failed: ' . $e->getMessage() . ')')
                                    ->warning()
                                    ->send();
                            }
                            return;
                        }

                        // Handle Bank Transfer payment
                        if ($data['payment_method'] === 'transfer') {
                            // Create payment record for transfer
                            $payment = Payment::create([
                                'wash_transaction_id' => $record->id,
                                'user_id' => Auth::user()->id,
                                'method' => 'transfer',
                                'amount_paid' => $record->total_price,
                                'change_amount' => 0,
                                'status' => Payment::STATUS_COMPLETED,
                                'paid_at' => now(),
                                'receipt_data' => [
                                    'transaction' => $record->toArray(),
                                    'products' => $record->products->toArray(),
                                    'customer' => $record->customer->toArray(),
                                    'vehicle' => $record->customerVehicle->toArray(),
                                ]
                            ]);

                            // Update transaction status to completed and payment method
                            $record->update([
                                'status' => WashTransaction::STATUS_COMPLETED,
                                'payment_method' => 'transfer'
                            ]);

                            // Send FCM notification to logged in user (cashier/admin)
                            try {
                                $customer = $record->customer;
                                $loggedInUser = Auth::user();

                                \Illuminate\Support\Facades\Log::info('Transfer Payment: Checking logged in user FCM token', [
                                    'customer_exists' => $customer !== null,
                                    'customer_id' => $customer ? $customer->id : null,
                                    'logged_in_user_id' => $loggedInUser ? $loggedInUser->id : null,
                                    'logged_in_user_name' => $loggedInUser ? $loggedInUser->name : null,
                                    'logged_in_user_has_fcm_token' => $loggedInUser ? $loggedInUser->hasFcmToken() : false,
                                ]);

                                if ($loggedInUser && $loggedInUser->hasFcmToken()) {
                                    $customerName = $customer && $customer->user ? $customer->user->name : 'Customer';
                                    $notification = new \App\Notifications\FcmNotification(
                                        'Transfer Payment Confirmed',
                                        "Transfer payment confirmed for {$customerName}. Bank transfer of IDR " . number_format($payment->amount_paid) . " has been confirmed and processed.",
                                        [
                                            'type' => 'transfer_payment_confirmed',
                                            'is_print_receipt' => false,
                                            'wash_transaction_id' => (string) $record->id,
                                            'payment_id' => (string) $payment->id,
                                            'amount' => (string) $payment->amount_paid,
                                            'customer_name' => $customerName,
                                        ]
                                    );

                                    // Send notification and check result
                                    try {
                                        $result = $loggedInUser->notify($notification);

                                        \Illuminate\Support\Facades\Log::info('Transfer Payment: FCM notification attempted', [
                                            'user_id' => $loggedInUser->id,
                                            'user_email' => $loggedInUser->email,
                                            'notification_result' => $result,
                                        ]);

                                        // Check if user still has FCM token after notification attempt
                                        $loggedInUser->refresh();
                                        if ($loggedInUser->hasFcmToken()) {
                                            Notification::make()
                                                ->title('Transfer Payment Confirmed')
                                                ->body('Bank transfer payment confirmed and notification sent to you successfully.')
                                                ->success()
                                                ->send();
                                        } else {
                                            Notification::make()
                                                ->title('Transfer Payment Confirmed')
                                                ->body('Bank transfer payment confirmed. FCM notification failed: Invalid/expired token (token cleared).')
                                                ->warning()
                                                ->send();
                                        }
                                    } catch (\Exception $notificationException) {
                                        \Illuminate\Support\Facades\Log::error('Transfer Payment: FCM notification exception', [
                                            'error' => $notificationException->getMessage(),
                                            'user_id' => $loggedInUser->id,
                                        ]);

                                        Notification::make()
                                            ->title('Transfer Payment Confirmed')
                                            ->body('Bank transfer payment confirmed. FCM notification failed: ' . $notificationException->getMessage())
                                            ->warning()
                                            ->send();
                                    }
                                } else {
                                    \Illuminate\Support\Facades\Log::warning('Transfer Payment: Cannot send FCM notification', [
                                        'reason' => 'No FCM token found for logged in user',
                                        'logged_in_user_id' => $loggedInUser ? $loggedInUser->id : null,
                                        'logged_in_user_name' => $loggedInUser ? $loggedInUser->name : null,
                                    ]);

                                    Notification::make()
                                        ->title('Transfer Payment Confirmed')
                                        ->body('Bank transfer payment confirmed. FCM notification not sent: You do not have a valid FCM token.')
                                        ->success()
                                        ->send();
                                }
                            } catch (\Exception $e) {
                                \Illuminate\Support\Facades\Log::error('Transfer Payment: FCM notification failed', [
                                    'error' => $e->getMessage(),
                                    'trace' => $e->getTraceAsString(),
                                    'user_id' => $customerUser ? $customerUser->id : null,
                                ]);

                                Notification::make()
                                    ->title('Transfer Payment Confirmed')
                                    ->body('Bank transfer payment confirmed but FCM notification failed: ' . $e->getMessage())
                                    ->success()
                                    ->send();
                            }
                            return;
                        }

                        // Create payment record for cash
                        $payment = Payment::create([
                            'wash_transaction_id' => $record->id,
                            'user_id' => Auth::user()->id,
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

                        // Update transaction status to completed and payment method
                        $record->update([
                            'status' => WashTransaction::STATUS_COMPLETED,
                            'payment_method' => 'cash'
                        ]);

                        // Send FCM notification to device (1 device for multiple users approach)
                        try {
                            $fcmService = app(\App\Services\FCMService::class);
                            $fcmResult = $fcmService->sendPaymentNotificationToDevice($payment);

                            if ($fcmResult['success']) {
                                Notification::make()
                                    ->title('Payment Processed Successfully')
                                    ->body('Cash payment completed. FCM notification sent to device successfully.')
                                    ->success()
                                    ->send();
                            } else {
                                Notification::make()
                                    ->title('Payment Processed Successfully')
                                    ->body('Cash payment completed. FCM notification not sent: ' . $fcmResult['message'])
                                    ->warning()
                                    ->send();
                            }
                        } catch (\Exception $e) {
                            \Illuminate\Support\Facades\Log::error('Cash Payment: FCM notification failed', [
                                'error' => $e->getMessage(),
                                'trace' => $e->getTraceAsString(),
                            ]);

                            Notification::make()
                                ->title('Payment Processed Successfully')
                                ->body('Cash payment completed but FCM notification failed: ' . $e->getMessage())
                                ->warning()
                                ->send();
                        }
                    })
                    ->modalHeading(fn(WashTransaction $record) => "Process Payment - {$record->transaction_number}")
                    ->modalSubmitActionLabel(function (array $data) {
                        return match ($data['payment_method'] ?? '') {
                            'cash' => 'Process Cash Payment',
                            'transfer' => 'Confirm Transfer Received',
                            'qris' => 'QRIS Not Available',
                            default => 'Process Payment'
                        };
                    })
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
