<?php

namespace App\Filament\Resources;

use App\Filament\Resources\POSTransactionResource\Pages;
use App\Filament\Resources\POSTransactionResource\RelationManagers;
use App\Models\POSTransaction;
use App\Models\WorkOrder;
use App\Models\Customer;
use App\Models\CustomerVehicle;
use App\Models\User;
use App\Models\Shift;
use App\Models\Product;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Support\Enums\FontWeight;

class POSTransactionResource extends Resource
{
    protected static ?string $model = POSTransaction::class;

    protected static ?string $navigationIcon = 'heroicon-o-banknotes';

    protected static ?string $navigationLabel = 'POS Transactions';

    protected static ?string $modelLabel = 'POS Transaction';

    protected static ?string $pluralModelLabel = 'POS Transactions';

    protected static ?string $navigationGroup = 'Transaction Management';

    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Transaction Information')
                    ->schema([
                        Forms\Components\TextInput::make('transaction_number')
                            ->label('Transaction Number')
                            ->disabled()
                            ->dehydrated(false)
                            ->visible(fn($record) => $record !== null),

                        Forms\Components\Select::make('work_order_id')
                            ->label('Work Order')
                            ->options(WorkOrder::pluck('order_number', 'id'))
                            ->searchable()
                            ->preload()
                            ->nullable(),

                        Forms\Components\Select::make('customer_id')
                            ->label('Customer')
                            ->relationship('customer', 'id')
                            ->getOptionLabelFromRecordUsing(fn($record) => $record->user->name ?? 'Unknown')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->reactive()
                            ->afterStateUpdated(fn(callable $set) => $set('customer_vehicle_id', null)),

                        Forms\Components\Select::make('customer_vehicle_id')
                            ->label('Customer Vehicle')
                            ->options(function (callable $get) {
                                $customerId = $get('customer_id');
                                if (!$customerId) {
                                    return [];
                                }
                                return CustomerVehicle::where('customer_id', $customerId)
                                    ->with('vehicle')
                                    ->get()
                                    ->pluck('license_plate', 'id')
                                    ->toArray();
                            })
                            ->searchable()
                            ->required(),

                        Forms\Components\Select::make('user_id')
                            ->label('Cashier')
                            ->relationship('user', 'name')
                            ->searchable()
                            ->preload()
                            ->required(),

                        Forms\Components\DateTimePicker::make('transaction_date')
                            ->label('Transaction Date')
                            ->required()
                            ->default(now()),

                        Forms\Components\Select::make('status')
                            ->label('Status')
                            ->options(POSTransaction::getStatusOptions())
                            ->required()
                            ->default(POSTransaction::STATUS_COMPLETED),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Products')
                    ->schema([
                        Forms\Components\Repeater::make('products')
                            ->schema([
                                Forms\Components\Select::make('product_id')
                                    ->label('Product')
                                    ->options(Product::pluck('name', 'id'))
                                    ->searchable()
                                    ->required()
                                    ->reactive()
                                    ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                        if ($state) {
                                            $product = Product::find($state);
                                            $quantity = $get('quantity') ?: 1;
                                            $price = $product->price;
                                            $set('price', $price);
                                            $set('subtotal', $price * $quantity);
                                        }
                                    })
                                    ->validationMessages([
                                        'required' => 'Please select a product.',
                                    ]),

                                Forms\Components\TextInput::make('quantity')
                                    ->label('Quantity')
                                    ->numeric()
                                    ->default(1)
                                    ->minValue(1)
                                    ->required()
                                    ->reactive()
                                    ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                        $price = $get('price');
                                        if ($price && $state) {
                                            $set('subtotal', $price * $state);
                                        }
                                    }),

                                Forms\Components\TextInput::make('price')
                                    ->label('Price')
                                    ->numeric()
                                    ->prefix('Rp')
                                    ->disabled()
                                    ->dehydrated(),

                                Forms\Components\TextInput::make('subtotal')
                                    ->label('Subtotal')
                                    ->numeric()
                                    ->prefix('Rp')
                                    ->disabled()
                                    ->dehydrated(),
                            ])
                            ->columns(4)
                            ->minItems(1)
                            ->defaultItems(1)
                            ->addActionLabel('Add Product')
                            ->deleteAction(
                                fn(\Filament\Forms\Components\Actions\Action $action) => $action
                                    ->requiresConfirmation()
                            )
                            ->mutateRelationshipDataBeforeFillUsing(function (array $data): array {
                                // Convert relationship data to form data format
                                return collect($data)->map(function ($item) {
                                    return [
                                        'product_id' => $item['id'],
                                        'quantity' => $item['pivot']['quantity'],
                                        'price' => $item['pivot']['price'],
                                        'subtotal' => $item['pivot']['subtotal'],
                                    ];
                                })->toArray();
                            }),
                    ]),

                Forms\Components\Section::make('Payment Information')
                    ->schema([
                        Forms\Components\TextInput::make('subtotal')
                            ->label('Subtotal')
                            ->numeric()
                            ->prefix('Rp')
                            ->required()
                            ->reactive()
                            ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                $taxAmount = $get('tax_amount') ?? 0;
                                $discountAmount = $get('discount_amount') ?? 0;
                                $set('total_amount', $state + $taxAmount - $discountAmount);
                            }),

                        Forms\Components\TextInput::make('discount_amount')
                            ->label('Discount Amount')
                            ->numeric()
                            ->prefix('Rp')
                            ->default(0)
                            ->reactive()
                            ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                $subtotal = $get('subtotal') ?? 0;
                                $taxAmount = $get('tax_amount') ?? 0;
                                $set('total_amount', $subtotal + $taxAmount - $state);
                            }),

                        Forms\Components\TextInput::make('tax_amount')
                            ->label('Tax Amount')
                            ->numeric()
                            ->prefix('Rp')
                            ->default(0)
                            ->reactive()
                            ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                $subtotal = $get('subtotal') ?? 0;
                                $discountAmount = $get('discount_amount') ?? 0;
                                $set('total_amount', $subtotal + $state - $discountAmount);
                            }),

                        Forms\Components\TextInput::make('total_amount')
                            ->label('Total Amount')
                            ->numeric()
                            ->prefix('Rp')
                            ->required(),

                        Forms\Components\Select::make('payment_method')
                            ->label('Payment Method')
                            ->options(POSTransaction::getPaymentMethodOptions())
                            ->required(),

                        Forms\Components\TextInput::make('amount_paid')
                            ->label('Amount Paid')
                            ->numeric()
                            ->prefix('Rp')
                            ->required(),

                        Forms\Components\TextInput::make('change_amount')
                            ->label('Change Amount')
                            ->numeric()
                            ->prefix('Rp')
                            ->default(0),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Additional Information')
                    ->schema([
                        Forms\Components\Textarea::make('notes')
                            ->label('Notes')
                            ->rows(3)
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('transaction_number')
                    ->label('Transaction Number')
                    ->searchable()
                    ->sortable()
                    ->copyable(),

                Tables\Columns\TextColumn::make('washTransaction.workOrder.order_number')
                    ->label('Work Order')
                    ->searchable()
                    ->sortable()
                    ->placeholder('Direct Sale'),

                Tables\Columns\TextColumn::make('customer.user.name')
                    ->label('Customer')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('customerVehicle.license_plate')
                    ->label('Vehicle')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('user.name')
                    ->label('Cashier')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('total_amount')
                    ->label('Total Amount')
                    ->money('IDR')
                    ->sortable(),

                Tables\Columns\BadgeColumn::make('payment_method')
                    ->label('Payment Method')
                    ->colors([
                        'success' => POSTransaction::PAYMENT_METHOD_CASH,
                        'primary' => POSTransaction::PAYMENT_METHOD_QRIS,
                        'warning' => POSTransaction::PAYMENT_METHOD_TRANSFER,
                        'info' => POSTransaction::PAYMENT_METHOD_E_WALLET,
                    ]),

                Tables\Columns\BadgeColumn::make('status')
                    ->label('Status')
                    ->colors([
                        'secondary' => POSTransaction::STATUS_PENDING,
                        'success' => POSTransaction::STATUS_COMPLETED,
                        'danger' => POSTransaction::STATUS_CANCELLED,
                        'warning' => POSTransaction::STATUS_REFUNDED,
                    ]),

                Tables\Columns\TextColumn::make('transaction_date')
                    ->label('Transaction Date')
                    ->dateTime()
                    ->sortable(),

                Tables\Columns\TextColumn::make('shift.id')
                    ->label('Shift')
                    ->sortable()
                    ->placeholder('No Shift'),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Status')
                    ->options(POSTransaction::getStatusOptions()),

                Tables\Filters\SelectFilter::make('payment_method')
                    ->label('Payment Method')
                    ->options(POSTransaction::getPaymentMethodOptions()),

                Tables\Filters\SelectFilter::make('user_id')
                    ->label('Cashier')
                    ->relationship('user', 'name'),

                Tables\Filters\SelectFilter::make('shift_id')
                    ->label('Shift')
                    ->relationship('shift', 'id'),

                Tables\Filters\Filter::make('transaction_date')
                    ->form([
                        Forms\Components\DatePicker::make('transaction_from')
                            ->label('Transaction Date From'),
                        Forms\Components\DatePicker::make('transaction_until')
                            ->label('Transaction Date Until'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['transaction_from'],
                                fn(Builder $query, $date): Builder => $query->whereDate('transaction_date', '>=', $date),
                            )
                            ->when(
                                $data['transaction_until'],
                                fn(Builder $query, $date): Builder => $query->whereDate('transaction_date', '<=', $date),
                            );
                    }),

                Tables\Filters\TernaryFilter::make('from_work_order')
                    ->label('Source')
                    ->placeholder('All transactions')
                    ->trueLabel('From Work Order')
                    ->falseLabel('Direct Sales')
                    ->queries(
                        true: fn(Builder $query) => $query->whereNotNull('work_order_id'),
                        false: fn(Builder $query) => $query->whereNull('work_order_id'),
                    ),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make()
                    ->visible(fn($record) => $record->status === POSTransaction::STATUS_PENDING),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make('Transaction Information')
                    ->schema([
                        Infolists\Components\TextEntry::make('transaction_number')
                            ->label('Transaction Number')
                            ->weight(FontWeight::Bold),
                        Infolists\Components\TextEntry::make('washTransaction.workOrder.order_number')
                            ->label('Work Order')
                            ->placeholder('Direct Sale'),
                        Infolists\Components\TextEntry::make('status')
                            ->label('Status')
                            ->badge()
                            ->color(fn(string $state): string => match ($state) {
                                POSTransaction::STATUS_PENDING => 'gray',
                                POSTransaction::STATUS_COMPLETED => 'success',
                                POSTransaction::STATUS_CANCELLED => 'danger',
                                POSTransaction::STATUS_REFUNDED => 'warning',
                            }),
                        Infolists\Components\TextEntry::make('transaction_date')
                            ->label('Transaction Date')
                            ->dateTime(),
                        Infolists\Components\TextEntry::make('user.name')
                            ->label('Cashier'),
                        Infolists\Components\TextEntry::make('shift.id')
                            ->label('Shift ID')
                            ->placeholder('No Shift'),
                    ])
                    ->columns(2),

                Infolists\Components\Section::make('Customer Information')
                    ->schema([
                        Infolists\Components\TextEntry::make('customer.user.name')
                            ->label('Customer Name'),
                        Infolists\Components\TextEntry::make('customer.user.phone')
                            ->label('Phone Number'),
                        Infolists\Components\TextEntry::make('customerVehicle.license_plate')
                            ->label('License Plate'),
                        Infolists\Components\TextEntry::make('customerVehicle.vehicle.brand')
                            ->label('Vehicle Brand'),
                        Infolists\Components\TextEntry::make('customerVehicle.vehicle.model')
                            ->label('Vehicle Model'),
                        Infolists\Components\TextEntry::make('customerVehicle.vehicle.type')
                            ->label('Vehicle Type'),
                    ])
                    ->columns(2),

                Infolists\Components\Section::make('Products')
                    ->schema([
                        Infolists\Components\RepeatableEntry::make('products')
                            ->schema([
                                Infolists\Components\TextEntry::make('name')
                                    ->label('Product'),
                                Infolists\Components\TextEntry::make('pivot.quantity')
                                    ->label('Quantity'),
                                Infolists\Components\TextEntry::make('pivot.price')
                                    ->label('Price')
                                    ->money('IDR'),
                                Infolists\Components\TextEntry::make('pivot.subtotal')
                                    ->label('Subtotal')
                                    ->money('IDR'),
                            ])
                            ->columns(4),
                    ]),

                Infolists\Components\Section::make('Payment Information')
                    ->schema([
                        Infolists\Components\TextEntry::make('subtotal')
                            ->label('Subtotal')
                            ->money('IDR'),
                        Infolists\Components\TextEntry::make('discount_amount')
                            ->label('Discount')
                            ->money('IDR'),
                        Infolists\Components\TextEntry::make('tax_amount')
                            ->label('Tax')
                            ->money('IDR'),
                        Infolists\Components\TextEntry::make('total_amount')
                            ->label('Total Amount')
                            ->money('IDR')
                            ->weight(FontWeight::Bold),
                        Infolists\Components\TextEntry::make('payment_method')
                            ->label('Payment Method')
                            ->badge()
                            ->color(fn(string $state): string => match ($state) {
                                POSTransaction::PAYMENT_METHOD_CASH => 'success',
                                POSTransaction::PAYMENT_METHOD_QRIS => 'primary',
                                POSTransaction::PAYMENT_METHOD_TRANSFER => 'warning',
                                POSTransaction::PAYMENT_METHOD_E_WALLET => 'info',
                            }),
                        Infolists\Components\TextEntry::make('amount_paid')
                            ->label('Amount Paid')
                            ->money('IDR'),
                        Infolists\Components\TextEntry::make('change_amount')
                            ->label('Change')
                            ->money('IDR'),
                    ])
                    ->columns(2),

                Infolists\Components\Section::make('Additional Information')
                    ->schema([
                        Infolists\Components\TextEntry::make('notes')
                            ->label('Notes')
                            ->columnSpanFull(),
                    ])
                    ->columns(1),

                Infolists\Components\Section::make('Timestamps')
                    ->schema([
                        Infolists\Components\TextEntry::make('created_at')
                            ->label('Created At')
                            ->dateTime(),
                        Infolists\Components\TextEntry::make('completed_at')
                            ->label('Completed At')
                            ->dateTime(),
                        Infolists\Components\TextEntry::make('updated_at')
                            ->label('Updated At')
                            ->dateTime(),
                    ])
                    ->columns(2)
                    ->collapsible(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPOSTransactions::route('/'),
            'create' => Pages\CreatePOSTransaction::route('/create'),
            'edit' => Pages\EditPOSTransaction::route('/{record}/edit'),
        ];
    }
}
