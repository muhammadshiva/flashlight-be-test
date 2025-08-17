<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ServiceItemResource\Pages;
use App\Models\ServiceItem;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Table;
use Filament\Tables;

class ServiceItemResource extends Resource
{
    protected static ?string $model = ServiceItem::class;

    protected static ?string $navigationIcon = 'heroicon-o-collection';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('name')->required(),
            Forms\Components\Textarea::make('description'),
            Forms\Components\Toggle::make('is_main_wash'),
            Forms\Components\Toggle::make('is_premium'),
            Forms\Components\Select::make('applies_to')->options([
                'motor' => 'Motor',
                'car' => 'Car',
                'helmet' => 'Helmet',
                'apparel' => 'Apparel',
                'general' => 'General',
            ])->required(),
            Forms\Components\Toggle::make('is_active')->default(true),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            Tables\Columns\TextColumn::make('name')->searchable()->sortable(),
            Tables\Columns\IconColumn::make('is_main_wash')->boolean(),
            Tables\Columns\IconColumn::make('is_premium')->boolean(),
            Tables\Columns\TextColumn::make('applies_to')->sortable(),
            Tables\Columns\IconColumn::make('is_active')->boolean(),
        ])->filters([])->actions([
            Tables\Actions\EditAction::make(),
        ])->bulkActions([
            Tables\Actions\DeleteBulkAction::make(),
        ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageServiceItems::route('/'),
        ];
    }
}
