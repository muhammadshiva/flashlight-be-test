<?php

namespace App\Filament\Resources;

use App\Filament\Resources\WorkOrderResource\Pages;
use App\Filament\Resources\WorkOrderResource\RelationManagers;
use App\Models\WorkOrder;
use App\Models\Customer;
use App\Models\CustomerVehicle;
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

class WorkOrderResource extends Resource
{
    protected static ?string $model = WorkOrder::class;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-list';

    protected static ?string $navigationLabel = 'Work Orders';

    protected static ?string $modelLabel = 'Work Order';

    protected static ?string $pluralModelLabel = 'Work Orders';

    protected static ?string $navigationGroup = 'Order Management';

    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Order Information')
                    ->schema([
                        Forms\Components\TextInput::make('order_number')
                            ->label('Order Number')
                            ->disabled()
                            ->dehydrated(false)
                            ->visible(fn($record) => $record !== null),

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

                        Forms\Components\DateTimePicker::make('order_date')
                            ->label('Order Date')
                            ->required()
                            ->default(now()),

                        Forms\Components\Select::make('status')
                            ->label('Status')
                            ->options(WorkOrder::getStatusOptions())
                            ->required()
                            ->default(WorkOrder::STATUS_PENDING),
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
                                    }),

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
                            ),
                    ]),

                Forms\Components\Section::make('Additional Information')
                    ->schema([
                        Forms\Components\Textarea::make('notes')
                            ->label('Notes')
                            ->rows(3)
                            ->columnSpanFull(),

                        Forms\Components\Textarea::make('special_instructions')
                            ->label('Special Instructions')
                            ->rows(3)
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('order_number')
                    ->label('Order Number')
                    ->searchable()
                    ->sortable()
                    ->copyable(),

                Tables\Columns\TextColumn::make('customer.user.name')
                    ->label('Customer')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('customerVehicle.license_plate')
                    ->label('Vehicle')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('total_price')
                    ->label('Total Price')
                    ->money('IDR')
                    ->sortable(),

                Tables\Columns\BadgeColumn::make('status')
                    ->label('Status')
                    ->colors([
                        'secondary' => WorkOrder::STATUS_PENDING,
                        'primary' => WorkOrder::STATUS_CONFIRMED,
                        'warning' => WorkOrder::STATUS_IN_PROGRESS,
                        'info' => WorkOrder::STATUS_READY_FOR_PICKUP,
                        'success' => WorkOrder::STATUS_COMPLETED,
                        'danger' => WorkOrder::STATUS_CANCELLED,
                    ]),

                Tables\Columns\TextColumn::make('queue_number')
                    ->label('Queue #')
                    ->sortable()
                    ->alignCenter(),

                Tables\Columns\TextColumn::make('order_date')
                    ->label('Order Date')
                    ->dateTime()
                    ->sortable(),

                Tables\Columns\IconColumn::make('hasPayment')
                    ->label('Paid')
                    ->boolean()
                    ->getStateUsing(fn($record) => $record->hasPayment()),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Status')
                    ->options(WorkOrder::getStatusOptions()),

                Tables\Filters\Filter::make('order_date')
                    ->form([
                        Forms\Components\DatePicker::make('order_from')
                            ->label('Order Date From'),
                        Forms\Components\DatePicker::make('order_until')
                            ->label('Order Date Until'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['order_from'],
                                fn(Builder $query, $date): Builder => $query->whereDate('order_date', '>=', $date),
                            )
                            ->when(
                                $data['order_until'],
                                fn(Builder $query, $date): Builder => $query->whereDate('order_date', '<=', $date),
                            );
                    }),

                Tables\Filters\TernaryFilter::make('has_payment')
                    ->label('Payment Status')
                    ->placeholder('All orders')
                    ->trueLabel('Paid orders')
                    ->falseLabel('Unpaid orders')
                    ->queries(
                        true: fn(Builder $query) => $query->whereHas('posTransaction'),
                        false: fn(Builder $query) => $query->whereDoesntHave('posTransaction'),
                    ),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('confirm')
                    ->icon('heroicon-o-check')
                    ->color('primary')
                    ->visible(fn($record) => $record->status === WorkOrder::STATUS_PENDING)
                    ->action(fn($record) => $record->update(['status' => WorkOrder::STATUS_CONFIRMED, 'confirmed_at' => now()]))
                    ->requiresConfirmation(),
                Tables\Actions\Action::make('cancel')
                    ->icon('heroicon-o-x-mark')
                    ->color('danger')
                    ->visible(fn($record) => !$record->isCompleted() && !$record->isCancelled() && !$record->hasPayment())
                    ->action(fn($record) => $record->update(['status' => WorkOrder::STATUS_CANCELLED]))
                    ->requiresConfirmation(),
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
                Infolists\Components\Section::make('Order Information')
                    ->schema([
                        Infolists\Components\TextEntry::make('order_number')
                            ->label('Order Number')
                            ->weight(FontWeight::Bold),
                        Infolists\Components\TextEntry::make('status')
                            ->label('Status')
                            ->badge()
                            ->color(fn(string $state): string => match ($state) {
                                WorkOrder::STATUS_PENDING => 'gray',
                                WorkOrder::STATUS_CONFIRMED => 'primary',
                                WorkOrder::STATUS_IN_PROGRESS => 'warning',
                                WorkOrder::STATUS_READY_FOR_PICKUP => 'info',
                                WorkOrder::STATUS_COMPLETED => 'success',
                                WorkOrder::STATUS_CANCELLED => 'danger',
                            }),
                        Infolists\Components\TextEntry::make('queue_number')
                            ->label('Queue Number'),
                        Infolists\Components\TextEntry::make('order_date')
                            ->label('Order Date')
                            ->dateTime(),
                        Infolists\Components\TextEntry::make('total_price')
                            ->label('Total Price')
                            ->money('IDR'),
                        Infolists\Components\IconEntry::make('hasPayment')
                            ->label('Payment Status')
                            ->boolean()
                            ->getStateUsing(fn($record) => $record->hasPayment()),
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

                Infolists\Components\Section::make('Additional Information')
                    ->schema([
                        Infolists\Components\TextEntry::make('notes')
                            ->label('Notes')
                            ->columnSpanFull(),
                        Infolists\Components\TextEntry::make('special_instructions')
                            ->label('Special Instructions')
                            ->columnSpanFull(),
                    ])
                    ->columns(1),

                Infolists\Components\Section::make('Timestamps')
                    ->schema([
                        Infolists\Components\TextEntry::make('created_at')
                            ->label('Created At')
                            ->dateTime(),
                        Infolists\Components\TextEntry::make('confirmed_at')
                            ->label('Confirmed At')
                            ->dateTime(),
                        Infolists\Components\TextEntry::make('started_at')
                            ->label('Started At')
                            ->dateTime(),
                        Infolists\Components\TextEntry::make('completed_at')
                            ->label('Completed At')
                            ->dateTime(),
                    ])
                    ->columns(2)
                    ->collapsible(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListWorkOrders::route('/'),
            'create' => Pages\CreateWorkOrder::route('/create'),
            'edit' => Pages\EditWorkOrder::route('/{record}/edit'),
        ];
    }
}
