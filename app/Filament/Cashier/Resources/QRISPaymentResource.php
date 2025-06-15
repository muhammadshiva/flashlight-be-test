<?php

namespace App\Filament\Cashier\Resources;

use App\Filament\Cashier\Resources\QRISPaymentResource\Pages;
use App\Models\Payment;
use App\Services\QRISService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Filament\Notifications\Notification;
use Filament\Tables\Actions\Action;

class QRISPaymentResource extends Resource
{
    protected static ?string $model = Payment::class;

    protected static ?string $navigationIcon = 'heroicon-o-qr-code';

    protected static ?string $navigationLabel = 'QRIS Payments';

    protected static ?string $recordTitleAttribute = 'payment_number';

    protected static ?string $label = 'QRIS Payment';

    protected static ?string $pluralLabel = 'QRIS Payments';

    public static function form(Form $form): Form
    {
        return $form->schema([]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->query(
                Payment::query()
                    ->where('method', 'qris')
                    ->with(['washTransaction.customer.user', 'staff.user'])
                    ->orderBy('created_at', 'desc')
            )
            ->columns([
                Tables\Columns\TextColumn::make('payment_number')
                    ->label('Payment Number')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('washTransaction.customer.user.name')
                    ->label('Customer')
                    ->searchable(),

                Tables\Columns\TextColumn::make('amount_paid')
                    ->label('Amount')
                    ->money('IDR')
                    ->sortable(),

                Tables\Columns\TextColumn::make('qris_transaction_id')
                    ->label('QRIS Transaction ID')
                    ->searchable()
                    ->toggleable(),

                Tables\Columns\BadgeColumn::make('status')
                    ->label('Status')
                    ->colors([
                        'warning' => 'pending',
                        'success' => 'completed',
                        'danger' => 'failed',
                    ])
                    ->sortable(),

                Tables\Columns\TextColumn::make('paid_at')
                    ->label('Paid At')
                    ->dateTime('d/m/Y H:i:s')
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime('d/m/Y H:i:s')
                    ->sortable(),
            ])
            ->actions([
                Action::make('check_status')
                    ->label('Check Status')
                    ->icon('heroicon-o-arrow-path')
                    ->color('info')
                    ->visible(fn(Payment $record) => $record->status === Payment::STATUS_PENDING)
                    ->action(function (Payment $record) {
                        $qrisService = new QRISService();
                        $status = $qrisService->checkPaymentStatus($record->qris_transaction_id);

                        if ($status['status'] === 'completed') {
                            $qrisService->processPaymentCompletion($record, $record->qris_transaction_id);

                            Notification::make()
                                ->title('Payment Completed')
                                ->body('QRIS payment has been successfully completed')
                                ->success()
                                ->send();
                        } elseif ($status['status'] === 'failed') {
                            $record->update(['status' => Payment::STATUS_FAILED]);

                            Notification::make()
                                ->title('Payment Failed')
                                ->body('QRIS payment has failed or expired')
                                ->danger()
                                ->send();
                        } else {
                            Notification::make()
                                ->title('Payment Still Pending')
                                ->body('Customer has not completed the payment yet')
                                ->warning()
                                ->send();
                        }
                    }),

                Action::make('view_qr')
                    ->label('View QR')
                    ->icon('heroicon-o-qr-code')
                    ->color('gray')
                    ->visible(fn(Payment $record) => $record->status === Payment::STATUS_PENDING)
                    ->form([
                        Forms\Components\ViewField::make('qris_display')
                            ->label('QRIS Code')
                            ->view('filament.qris-display')
                            ->viewData([]),
                    ])
                    ->modalHeading('QRIS Payment Code')
                    ->modalWidth('md')
                    ->action(function () {
                        // No action needed, just display
                    }),
            ])
            ->bulkActions([])
            ->poll('30s');
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListQRISPayments::route('/'),
        ];
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit($record): bool
    {
        return false;
    }

    public static function canDelete($record): bool
    {
        return false;
    }

    public static function canView($record): bool
    {
        return false;
    }
}
