<?php

namespace App\Filament\Cashier\Resources;

use App\Filament\Cashier\Resources\WashStatusResource\Pages;
use App\Models\WashTransaction;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Filament\Notifications\Notification;

class WashStatusResource extends Resource
{
    protected static ?string $model = WashTransaction::class;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-check';

    protected static ?string $navigationLabel = 'Wash Status';

    protected static ?string $pluralModelLabel = 'Wash Status Management';

    protected static ?string $modelLabel = 'Wash Status';

    protected static ?int $navigationSort = 20;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Transaction Details')
                    ->schema([
                        Forms\Components\TextInput::make('transaction_number')
                            ->label('Transaction Number')
                            ->disabled()
                            ->columnSpan(1),

                        Forms\Components\TextInput::make('customer.user.name')
                            ->label('Customer Name')
                            ->disabled()
                            ->columnSpan(1),

                        Forms\Components\TextInput::make('customerVehicle.license_plate')
                            ->label('License Plate')
                            ->disabled()
                            ->columnSpan(1),

                        Forms\Components\TextInput::make('customerVehicle.vehicle.name')
                            ->label('Vehicle Type')
                            ->disabled()
                            ->columnSpan(1),

                        Forms\Components\TextInput::make('primaryProduct.name')
                            ->label('Primary Service')
                            ->disabled()
                            ->columnSpan(1),

                        Forms\Components\TextInput::make('total_price')
                            ->label('Total Price')
                            ->disabled()
                            ->prefix('Rp.')
                            ->formatStateUsing(fn($state) => number_format($state, 0, ',', '.'))
                            ->columnSpan(1),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Status Management')
                    ->schema([
                        Forms\Components\Select::make('status')
                            ->label('Wash Status')
                            ->options([
                                WashTransaction::STATUS_PENDING => 'Pending',
                                WashTransaction::STATUS_IN_PROGRESS => 'In Progress',
                                WashTransaction::STATUS_COMPLETED => 'Completed',
                            ])
                            ->required()
                            ->live()
                            ->afterStateUpdated(function ($state, $record) {
                                if ($record && $state) {
                                    $record->update(['status' => $state]);

                                    $statusLabel = match ($state) {
                                        WashTransaction::STATUS_PENDING => 'Pending',
                                        WashTransaction::STATUS_IN_PROGRESS => 'In Progress',
                                        WashTransaction::STATUS_COMPLETED => 'Completed',
                                        default => $state
                                    };

                                    Notification::make()
                                        ->title('Status Updated')
                                        ->body("Wash status has been updated to: {$statusLabel}")
                                        ->success()
                                        ->send();
                                }
                            }),

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
                    ->label('Transaction #')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('customer.user.name')
                    ->label('Customer')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('customerVehicle.license_plate')
                    ->label('License Plate')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('customerVehicle.vehicle.name')
                    ->label('Vehicle')
                    ->sortable(),

                Tables\Columns\TextColumn::make('primaryProduct.name')
                    ->label('Service')
                    ->sortable(),

                Tables\Columns\TextColumn::make('total_price')
                    ->label('Total')
                    ->money('IDR')
                    ->sortable(),

                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        WashTransaction::STATUS_PENDING => 'warning',
                        WashTransaction::STATUS_IN_PROGRESS => 'primary',
                        WashTransaction::STATUS_COMPLETED => 'success',
                        WashTransaction::STATUS_CANCELLED => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn(string $state): string => match ($state) {
                        WashTransaction::STATUS_PENDING => 'Pending',
                        WashTransaction::STATUS_IN_PROGRESS => 'In Progress',
                        WashTransaction::STATUS_COMPLETED => 'Completed',
                        WashTransaction::STATUS_CANCELLED => 'Cancelled',
                        default => $state,
                    })
                    ->sortable(),

                Tables\Columns\TextColumn::make('wash_date')
                    ->label('Wash Date')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        WashTransaction::STATUS_PENDING => 'Pending',
                        WashTransaction::STATUS_IN_PROGRESS => 'In Progress',
                        WashTransaction::STATUS_COMPLETED => 'Completed',
                        WashTransaction::STATUS_CANCELLED => 'Cancelled',
                    ]),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->label('Update Status'),
            ])
            ->bulkActions([])
            ->defaultSort('wash_date', 'desc')
            ->modifyQueryUsing(function (Builder $query) {
                return $query->with([
                    'customer.user',
                    'customerVehicle.vehicle',
                    'primaryProduct',
                ])
                    ->whereNotIn('status', [WashTransaction::STATUS_CANCELLED]);
            });
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
            'index' => Pages\ListWashStatuses::route('/'),
            'edit' => Pages\EditWashStatus::route('/{record}/edit'),
        ];
    }
}
