<?php

namespace App\Filament\Resources;

use App\Filament\Resources\WashTransactionResource\Pages;
use App\Filament\Resources\WashTransactionResource\RelationManagers;
use App\Models\WashTransaction;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class WashTransactionResource extends Resource
{
    protected static ?string $model = WashTransaction::class;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-list';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('user_id')
                    ->relationship('user', 'name')
                    ->searchable()
                    ->preload()
                    ->required()
                    ->label('Customer'),
                Forms\Components\Select::make('vehicle_id')
                    ->relationship('vehicle', 'license_plate')
                    ->searchable()
                    ->preload()
                    ->required()
                    ->label('Vehicle'),
                Forms\Components\Select::make('service_type_id')
                    ->relationship('serviceType', 'name')
                    ->searchable()
                    ->preload()
                    ->required()
                    ->label('Service Type'),
                Forms\Components\Select::make('staff_id')
                    ->relationship('staff', 'name')
                    ->searchable()
                    ->preload()
                    ->nullable()
                    ->label('Staff'),
                Forms\Components\DateTimePicker::make('wash_date')
                    ->required()
                    ->label('Wash Date'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('user.name')
                    ->searchable()
                    ->sortable()
                    ->label('Customer'),
                Tables\Columns\TextColumn::make('vehicle.license_plate')
                    ->searchable()
                    ->sortable()
                    ->label('Vehicle'),
                Tables\Columns\TextColumn::make('serviceType.name')
                    ->searchable()
                    ->sortable()
                    ->label('Service Type'),
                Tables\Columns\TextColumn::make('staff.name')
                    ->searchable()
                    ->sortable()
                    ->label('Staff'),
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
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
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
}
