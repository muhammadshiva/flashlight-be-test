<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PriceMatrixResource\Pages;
use App\Models\PriceMatrix;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Table;
use Filament\Tables;

class PriceMatrixResource extends Resource
{
    protected static ?string $model = PriceMatrix::class;
    protected static ?string $navigationIcon = 'heroicon-o-adjustments';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Select::make('service_item_id')->relationship('serviceItem', 'name')->required(),
            Forms\Components\Select::make('engine_class_id')->relationship('engineClass', 'name')->searchable(),
            Forms\Components\Select::make('helmet_type_id')->relationship('helmetType', 'name')->searchable(),
            Forms\Components\Select::make('car_size_id')->relationship('carSize', 'name')->searchable(),
            Forms\Components\Select::make('apparel_type_id')->relationship('apparelType', 'name')->searchable(),
            Forms\Components\TextInput::make('price')->numeric()->required(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            Tables\Columns\TextColumn::make('serviceItem.name')->label('Service'),
            Tables\Columns\TextColumn::make('engineClass.name')->label('Engine'),
            Tables\Columns\TextColumn::make('helmetType.name')->label('Helmet'),
            Tables\Columns\TextColumn::make('carSize.name')->label('Car Size'),
            Tables\Columns\TextColumn::make('apparelType.name')->label('Apparel'),
            Tables\Columns\TextColumn::make('price')->money('idr'),
        ])->actions([
            Tables\Actions\EditAction::make(),
        ])->bulkActions([
            Tables\Actions\DeleteBulkAction::make(),
        ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManagePriceMatrix::route('/'),
        ];
    }
}
