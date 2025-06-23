<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class Payment extends Model
{
    use HasFactory;

    const METHOD_CASH = 'cash';
    const METHOD_QRIS = 'qris';
    const METHOD_TRANSFER = 'transfer';
    const METHOD_E_WALLET = 'e_wallet';

    const STATUS_PENDING = 'pending';
    const STATUS_COMPLETED = 'completed';
    const STATUS_FAILED = 'failed';

    protected $fillable = [
        'wash_transaction_id',
        'user_id',
        'payment_number',
        'method',
        'amount_paid',
        'change_amount',
        'qris_transaction_id',
        'status',
        'receipt_data',
        'paid_at',
    ];

    protected $casts = [
        'amount_paid' => 'decimal:2',
        'change_amount' => 'decimal:2',
        'receipt_data' => 'array',
        'paid_at' => 'datetime',
    ];

    protected static function booted()
    {
        static::creating(function ($payment) {
            if (!$payment->payment_number) {
                $payment->payment_number = 'PAY-' . date('Ymd') . '-' . strtoupper(Str::random(6));
            }
        });
    }

    public function washTransaction(): BelongsTo
    {
        return $this->belongsTo(WashTransaction::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isCash(): bool
    {
        return $this->method === self::METHOD_CASH;
    }

    public function isQris(): bool
    {
        return $this->method === self::METHOD_QRIS;
    }

    public function isTransfer(): bool
    {
        return $this->method === self::METHOD_TRANSFER;
    }

    public function isEWallet(): bool
    {
        return $this->method === self::METHOD_E_WALLET;
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function isCompleted(): bool
    {
        return $this->status === self::STATUS_COMPLETED;
    }

    public function isFailed(): bool
    {
        return $this->status === self::STATUS_FAILED;
    }

    public static function getMethodOptions(): array
    {
        return [
            self::METHOD_CASH => 'Cash',
            self::METHOD_QRIS => 'QRIS',
            self::METHOD_TRANSFER => 'Transfer',
            self::METHOD_E_WALLET => 'E-Wallet',
        ];
    }

    public static function getStatusOptions(): array
    {
        return [
            self::STATUS_PENDING => 'Pending',
            self::STATUS_COMPLETED => 'Completed',
            self::STATUS_FAILED => 'Failed',
        ];
    }
}
