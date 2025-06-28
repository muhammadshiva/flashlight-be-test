<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ShiftResource\Pages;
use App\Filament\Resources\ShiftResource\RelationManagers;
use App\Models\Shift;
use App\Models\User;
use App\Traits\HasResourcePermissions;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Forms\Components\Section;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\Filter;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Textarea;
use Illuminate\Support\Facades\Auth;

class ShiftResource extends Resource
{
    use HasResourcePermissions;

    protected static ?string $model = Shift::class;

    protected static ?string $navigationIcon = 'heroicon-o-clock';

    protected static ?string $navigationGroup = 'Transaction Management';

    protected static ?string $navigationLabel = 'Shifts';

    protected static ?string $modelLabel = 'Shift';

    protected static ?string $pluralModelLabel = 'Shifts';

    protected static ?int $navigationSort = 1;

    public static function canAccess(): bool
    {
        $user = Auth::user();
        return $user && ($user->type === User::TYPE_OWNER || $user->type === User::TYPE_ADMIN);
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Shift Information')
                    ->schema([
                        Select::make('user_id')
                            ->label('Cashier')
                            ->relationship(
                                name: 'user',
                                titleAttribute: 'name',
                                modifyQueryUsing: fn(Builder $query) => $query->where('type', User::TYPE_CASHIER)
                            )
                            ->required()
                            ->searchable()
                            ->preload(),

                        DateTimePicker::make('start_time')
                            ->label('Start Time')
                            ->required()
                            ->default(now()),

                        DateTimePicker::make('end_time')
                            ->label('End Time')
                            ->nullable(),

                        Select::make('status')
                            ->label('Status')
                            ->options(Shift::getStatusOptions())
                            ->required()
                            ->default(Shift::STATUS_ACTIVE),
                    ])
                    ->columns(2),

                Section::make('Cash Information')
                    ->schema([
                        TextInput::make('initial_cash')
                            ->label('Initial Cash')
                            ->numeric()
                            ->required()
                            ->prefix('Rp')
                            ->step('0.01'),

                        TextInput::make('received_from')
                            ->label('Received From')
                            ->required()
                            ->maxLength(255),

                        TextInput::make('final_cash')
                            ->label('Final Cash')
                            ->numeric()
                            ->nullable()
                            ->prefix('Rp')
                            ->step('0.01'),

                        TextInput::make('total_sales')
                            ->label('Total Sales')
                            ->numeric()
                            ->nullable()
                            ->prefix('Rp')
                            ->step('0.01')
                            ->disabled(),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('user.name')
                    ->label('Cashier')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('start_time')
                    ->label('Start Time')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),

                TextColumn::make('end_time')
                    ->label('End Time')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->placeholder('Active'),

                TextColumn::make('initial_cash')
                    ->label('Initial Cash')
                    ->money('IDR')
                    ->sortable(),

                TextColumn::make('final_cash')
                    ->label('Final Cash')
                    ->money('IDR')
                    ->sortable()
                    ->placeholder('-'),

                TextColumn::make('total_sales')
                    ->label('Total Sales')
                    ->money('IDR')
                    ->sortable(),

                TextColumn::make('received_from')
                    ->label('Received From')
                    ->searchable()
                    ->limit(20),

                BadgeColumn::make('status')
                    ->label('Status')
                    ->colors([
                        'success' => Shift::STATUS_ACTIVE,
                        'primary' => Shift::STATUS_CLOSED,
                        'danger' => Shift::STATUS_CANCELED,
                    ])
                    ->icons([
                        'heroicon-o-clock' => Shift::STATUS_ACTIVE,
                        'heroicon-o-check-circle' => Shift::STATUS_CLOSED,
                        'heroicon-o-x-circle' => Shift::STATUS_CANCELED,
                    ]),

                TextColumn::make('washTransactions_count')
                    ->label('Transactions')
                    ->counts('washTransactions')
                    ->sortable(),

                TextColumn::make('difference')
                    ->label('Cash Difference')
                    ->getStateUsing(function (Shift $record) {
                        if (!$record->final_cash) return '-';
                        $difference = $record->calculateCashDifference();
                        return 'Rp ' . number_format($difference, 0, ',', '.');
                    })
                    ->color(fn(Shift $record) => $record->calculateCashDifference() >= 0 ? 'success' : 'danger'),

                TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('user')
                    ->relationship('user', 'name')
                    ->label('Cashier')
                    ->searchable()
                    ->preload(),

                SelectFilter::make('status')
                    ->options(Shift::getStatusOptions())
                    ->label('Status'),

                Filter::make('date_range')
                    ->form([
                        DateTimePicker::make('start_date')
                            ->label('Start Date'),
                        DateTimePicker::make('end_date')
                            ->label('End Date'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['start_date'],
                                fn(Builder $query, $date): Builder => $query->whereDate('start_time', '>=', $date),
                            )
                            ->when(
                                $data['end_date'],
                                fn(Builder $query, $date): Builder => $query->whereDate('start_time', '<=', $date),
                            );
                    }),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make()
                    ->visible(fn(Shift $record) => !$record->isClosed()),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->visible(fn() => Auth::user()?->type === User::TYPE_OWNER),
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

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListShifts::route('/'),
            'create' => Pages\CreateShift::route('/create'),
            'edit' => Pages\EditShift::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery();
    }
}
