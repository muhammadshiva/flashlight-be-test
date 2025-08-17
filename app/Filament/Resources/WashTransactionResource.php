<?php

namespace App\Filament\Resources;

use App\Filament\Resources\WashTransactionResource\Pages;
use App\Filament\Resources\WashTransactionResource\RelationManagers;
use App\Models\User;
use App\Models\WashTransaction;
use App\Models\WorkOrder;
use App\Models\Product;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class WashTransactionResource extends Resource
{
    protected static ?string $model = WashTransaction::class;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-list';

    protected static ?string $navigationLabel = 'Wash Transactions';

    protected static ?string $navigationGroup = 'Service Management';

    protected static ?int $navigationSort = 3;

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
                            ->relationship('workOrder', 'order_number')
                            ->searchable()
                            ->preload()
                            ->nullable(),

                        Forms\Components\Select::make('customer_id')
                            ->relationship('customer', 'id', fn($query) => $query->with('user'))
                            ->getOptionLabelFromRecordUsing(fn($record) => $record->user->name)
                            ->searchable(['user.name'])
                            ->preload()
                            ->required()
                            ->label('Customer'),

                        Forms\Components\Select::make('customer_vehicle_id')
                            ->relationship('customerVehicle', 'license_plate')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->label('Vehicle'),

                        Forms\Components\Select::make('product_id')
                            ->label('Primary Product')
                            ->options(Product::pluck('name', 'id'))
                            ->searchable()
                            ->preload()
                            ->nullable()
                            ->helperText('Optional: Select a primary product for this transaction'),

                        Forms\Components\Select::make('user_id')
                            ->relationship('user', 'name')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->label('User'),

                        Forms\Components\Select::make('payment_method')
                            ->options(WashTransaction::getPaymentMethodOptions())
                            ->required()
                            ->label('Payment Method'),

                        Forms\Components\DateTimePicker::make('wash_date')
                            ->required()
                            ->label('Wash Date'),

                        Forms\Components\Select::make('status')
                            ->options(WashTransaction::getStatusOptions())
                            ->required()
                            ->default(WashTransaction::STATUS_PENDING)
                            ->label('Status'),

                        Forms\Components\Select::make('service_status')
                            ->options(WashTransaction::getServiceStatusOptions())
                            ->required()
                            ->default(WashTransaction::SERVICE_STATUS_WAITING)
                            ->label('Service Status'),

                        Forms\Components\Textarea::make('notes')
                            ->label('Notes')
                            ->columnSpanFull(),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Products')
                    ->schema([
                        Forms\Components\Repeater::make('products')
                            ->relationship()
                            ->schema([
                                Forms\Components\Select::make('product_id')
                                    ->label('Product')
                                    ->options(Product::pluck('name', 'id'))
                                    ->searchable()
                                    ->preload()
                                    ->required()
                                    ->reactive()
                                    ->afterStateUpdated(function ($state, callable $set) {
                                        if ($state) {
                                            $product = Product::find($state);
                                            $quantity = 1;
                                            $price = $product->price;
                                            $set('price', $price);
                                            $set('subtotal', $price * $quantity);
                                        }
                                    }),

                                Forms\Components\TextInput::make('quantity')
                                    ->numeric()
                                    ->default(1)
                                    ->required()
                                    ->minValue(1)
                                    ->reactive()
                                    ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                        $price = $get('price');
                                        if ($price && $state) {
                                            $set('subtotal', $price * $state);
                                        }
                                    })
                                    ->label('Quantity'),

                                Forms\Components\TextInput::make('price')
                                    ->numeric()
                                    ->required()
                                    ->disabled()
                                    ->dehydrated()
                                    ->label('Price'),

                                Forms\Components\TextInput::make('subtotal')
                                    ->numeric()
                                    ->required()
                                    ->disabled()
                                    ->dehydrated()
                                    ->label('Subtotal'),
                            ])
                            ->columns(4)
                            ->defaultItems(1)
                            ->minItems(1)
                            ->addActionLabel('Add Product')
                            ->deleteAction(
                                fn(\Filament\Forms\Components\Actions\Action $action) => $action
                                    ->requiresConfirmation()
                            ),

                        Forms\Components\TextInput::make('total_price')
                            ->numeric()
                            ->required()
                            ->disabled()
                            ->label('Total Price')
                            ->prefix('IDR'),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('customer.user.name')
                    ->searchable()
                    ->sortable()
                    ->label('Customer'),
                Tables\Columns\TextColumn::make('customerVehicle.license_plate')
                    ->searchable()
                    ->sortable()
                    ->label('Vehicle'),
                Tables\Columns\TextColumn::make('primaryProduct.name')
                    ->searchable()
                    ->sortable()
                    ->label('Primary Product'),
                Tables\Columns\TextColumn::make('user.name')
                    ->searchable()
                    ->sortable()
                    ->label('User'),
                Tables\Columns\TextColumn::make('total_price')
                    ->money('IDR')
                    ->sortable()
                    ->label('Total Price'),
                Tables\Columns\TextColumn::make('payment_method')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'cash' => 'success',
                        'cashless' => 'warning',
                    })
                    ->sortable()
                    ->label('Payment Method'),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'pending' => 'warning',
                        'in_progress' => 'info',
                        'completed' => 'success',
                        'cancelled' => 'danger',
                    })
                    ->sortable()
                    ->label('Status'),
                Tables\Columns\TextColumn::make('wash_date')
                    ->dateTime()
                    ->sortable()
                    ->label('Wash Date'),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options(WashTransaction::getStatusOptions())
                    ->label('Status'),
                Tables\Filters\SelectFilter::make('payment_method')
                    ->options(WashTransaction::getPaymentMethodOptions())
                    ->label('Payment Method'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->url(fn(WashTransaction $record): string => route('filament.admin.resources.wash-transactions.edit', ['record' => $record])),
                Tables\Actions\EditAction::make()
                    ->visible(fn() => Auth::user()->type !== User::TYPE_CASHIER),
                Tables\Actions\DeleteAction::make()
                    ->visible(fn() => Auth::user()->type !== User::TYPE_CASHIER),
                Tables\Actions\Action::make('complete')
                    ->action(fn(WashTransaction $record) => $record->update(['status' => WashTransaction::STATUS_COMPLETED]))
                    ->requiresConfirmation()
                    ->visible(fn(WashTransaction $record) => !$record->isCompleted() && Auth::user()->type !== User::TYPE_CASHIER)
                    ->color('success')
                    ->icon('heroicon-o-check-circle'),
                Tables\Actions\Action::make('cancel')
                    ->action(fn(WashTransaction $record) => $record->update(['status' => WashTransaction::STATUS_CANCELLED]))
                    ->requiresConfirmation()
                    ->visible(fn(WashTransaction $record) => !$record->isCancelled() && !$record->isCompleted() && Auth::user()->type !== User::TYPE_CASHIER)
                    ->color('danger')
                    ->icon('heroicon-o-x-circle'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->visible(fn() => Auth::user()->type !== User::TYPE_CASHIER),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListWashTransactions::route('/'),
            'create' => Pages\CreateWashTransaction::route('/create'),
            'edit' => Pages\EditWashTransaction::route('/{record}/edit'),
        ];
    }

    public static function canCreate(): bool
    {
        return Auth::check() && Auth::user()->type !== User::TYPE_CASHIER;
    }
}
