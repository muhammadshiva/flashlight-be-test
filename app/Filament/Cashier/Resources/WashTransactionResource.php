<?php

namespace App\Filament\Cashier\Resources;

use App\Filament\Cashier\Resources\WashTransactionResource\Pages;
use App\Models\WashTransaction;
use App\Models\Product;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class WashTransactionResource extends Resource
{
    protected static ?string $model = WashTransaction::class;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-list';

    protected static ?string $navigationLabel = 'Wash Transactions';

    protected static ?string $recordTitleAttribute = 'id';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Customer Information')
                    ->schema([
                        Forms\Components\TextInput::make('customer.user.name')
                            ->label('Customer Name')
                            ->disabled(),
                        Forms\Components\TextInput::make('customerVehicle.license_plate')
                            ->label('Vehicle')
                            ->disabled(),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Transaction Details')
                    ->schema([
                        Forms\Components\TextInput::make('primaryProduct.name')
                            ->label('Primary Product')
                            ->disabled(),
                        Forms\Components\TextInput::make('user.name')
                            ->label('User')
                            ->disabled(),
                        Forms\Components\TextInput::make('payment_method')
                            ->label('Payment Method')
                            ->disabled(),
                        Forms\Components\TextInput::make('status')
                            ->label('Status')
                            ->disabled(),
                        Forms\Components\DateTimePicker::make('wash_date')
                            ->label('Wash Date')
                            ->disabled(),
                        Forms\Components\TextInput::make('total_price')
                            ->label('Total Price')
                            ->prefix('IDR')
                            ->disabled(),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Additional Information')
                    ->schema([
                        Forms\Components\Textarea::make('notes')
                            ->label('Notes')
                            ->disabled(),
                    ]),
            ])
            ->disabled();
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->searchable()
                    ->sortable()
                    ->label('ID'),
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
                Tables\Columns\TextColumn::make('total_price')
                    ->money('IDR')
                    ->sortable()
                    ->label('Total Price'),
                Tables\Columns\TextColumn::make('payment_method')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'cash' => 'success',
                        'cashless' => 'warning',
                        default => 'gray',
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
                        default => 'gray',
                    })
                    ->sortable()
                    ->label('Status'),
                Tables\Columns\TextColumn::make('wash_date')
                    ->dateTime()
                    ->sortable()
                    ->label('Wash Date'),
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
                Tables\Actions\ViewAction::make(),
            ])
            ->bulkActions([])
            ->defaultSort('id', 'desc');
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListWashTransactions::route('/'),
            'view' => Pages\ViewWashTransaction::route('/{record}'),
        ];
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit(Model $record): bool
    {
        return false;
    }

    public static function canDelete(Model $record): bool
    {
        return false;
    }
}
