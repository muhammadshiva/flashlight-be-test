<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CustomerResource\Pages;
use App\Models\Customer;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Models\MembershipType;

class CustomerResource extends Resource
{
    protected static ?string $model = Customer::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';

    protected static ?string $navigationLabel = 'Customers';

    protected static ?string $modelLabel = 'Customer';

    protected static ?string $pluralModelLabel = 'Customers';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('user_id')
                    ->relationship('user', 'name')
                    ->searchable()
                    ->preload()
                    ->required()
                    ->label('User'),
                Forms\Components\Textarea::make('address')
                    ->required()
                    ->maxLength(65535)
                    ->columnSpanFull(),
                Forms\Components\Select::make('membership_type_id')
                    ->relationship('membershipType', 'name')
                    ->searchable()
                    ->preload()
                    ->required()
                    ->label('Membership Type')
                    ->live(),
                Forms\Components\Select::make('membership_status')
                    ->options([
                        Customer::MEMBERSHIP_STATUS_PENDING => 'Pending',
                        Customer::MEMBERSHIP_STATUS_APPROVED => 'Approved',
                        Customer::MEMBERSHIP_STATUS_REJECTED => 'Rejected',
                    ])
                    ->required()
                    ->default(Customer::MEMBERSHIP_STATUS_PENDING)
                    ->visible(fn(Forms\Get $get) => !empty($get('membership_type_id'))),
                Forms\Components\DateTimePicker::make('membership_expires_at')
                    ->label('Membership Expires At')
                    ->visible(fn(Forms\Get $get) => !empty($get('membership_type_id'))),
                Forms\Components\Toggle::make('is_active')
                    ->label('Active')
                    ->default(true),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('user.name')
                    ->searchable()
                    ->sortable()
                    ->label('Name'),
                Tables\Columns\TextColumn::make('membershipType.name')
                    ->searchable()
                    ->sortable()
                    ->badge()
                    ->color('success'),
                Tables\Columns\TextColumn::make('membership_status')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        Customer::MEMBERSHIP_STATUS_PENDING => 'warning',
                        Customer::MEMBERSHIP_STATUS_APPROVED => 'success',
                        Customer::MEMBERSHIP_STATUS_REJECTED => 'danger',
                        default => 'gray',
                    })
                    ->visible(fn($record) => !empty($record?->membership_type_id)),
                Tables\Columns\TextColumn::make('membership_expires_at')
                    ->dateTime()
                    ->sortable()
                    ->visible(fn($record) => !empty($record?->membership_type_id)),
                Tables\Columns\IconColumn::make('is_active')
                    ->boolean(),
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
                Tables\Filters\TrashedFilter::make(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
                Tables\Actions\ForceDeleteAction::make(),
                Tables\Actions\RestoreAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\ForceDeleteBulkAction::make(),
                    Tables\Actions\RestoreBulkAction::make(),
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
            'index' => Pages\ListCustomers::route('/'),
            'create' => Pages\CreateCustomer::route('/create'),
            'edit' => Pages\EditCustomer::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }
}
