<?php

namespace App\Filament\Resources;

use App\Filament\Resources\MembershipTypeResource\Pages;
use App\Filament\Resources\MembershipTypeResource\RelationManagers;
use App\Models\MembershipType;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class MembershipTypeResource extends Resource
{
    protected static ?string $model = MembershipType::class;

    protected static ?string $navigationIcon = 'heroicon-o-credit-card';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('type_name')
                    ->required()
                    ->maxLength(255)
                    ->label('Membership Type Name'),
                Forms\Components\TextInput::make('price')
                    ->required()
                    ->numeric()
                    ->prefix('$')
                    ->label('Price'),
                Forms\Components\TextInput::make('duration_in_months')
                    ->required()
                    ->numeric()
                    ->minValue(1)
                    ->label('Duration (Months)'),
                Forms\Components\TextInput::make('max_wash_per_month')
                    ->required()
                    ->numeric()
                    ->minValue(1)
                    ->label('Maximum Washes Per Month'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('type_name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('price')
                    ->money('USD')
                    ->sortable(),
                Tables\Columns\TextColumn::make('duration_in_months')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('max_wash_per_month')
                    ->numeric()
                    ->sortable(),
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
            'index' => Pages\ListMembershipTypes::route('/'),
            'create' => Pages\CreateMembershipType::route('/create'),
            'edit' => Pages\EditMembershipType::route('/{record}/edit'),
        ];
    }
}
