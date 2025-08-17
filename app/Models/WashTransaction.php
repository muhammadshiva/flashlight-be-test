<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class WashTransaction extends Model
{
    use HasFactory, SoftDeletes;

    const STATUS_PENDING = 'pending';
    const STATUS_IN_PROGRESS = 'in_progress';
    const STATUS_COMPLETED = 'completed';
    const STATUS_CANCELLED = 'cancelled';
    const PAYMENT_METHOD_CASH = 'cash';
    const PAYMENT_METHOD_CASHLESS = 'cashless';
    const PAYMENT_METHOD_TRANSFER = 'transfer';

    protected $fillable = [
        'customer_id',
        'customer_vehicle_id',
        'main_service_item_id',
        'user_id',
        'work_order_id',
        'shift_id',
        'total_price',
        'payment_method',
        'wash_date',
        'status',
        'notes',
        'transaction_number',
    ];

    protected $casts = [
        'wash_date' => 'datetime',
        'total_price' => 'float',
    ];

    protected static function booted()
    {
        static::creating(function ($transaction) {
            if (!isset($transaction->total_price)) {
                $transaction->total_price = 0;
            }
        });

        static::created(function ($transaction) {
            $transaction->customer?->updateTransactionCounts();
        });

        static::updated(function ($transaction) {
            $transaction->customer?->updateTransactionCounts();
        });

        static::deleted(function ($transaction) {
            $transaction->customer?->updateTransactionCounts();
        });

        static::restored(function ($transaction) {
            $transaction->customer?->updateTransactionCounts();
        });
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function customerVehicle(): BelongsTo
    {
        return $this->belongsTo(CustomerVehicle::class);
    }

    public function mainServiceItem(): BelongsTo
    {
        return $this->belongsTo(ServiceItem::class, 'main_service_item_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function shift(): BelongsTo
    {
        return $this->belongsTo(Shift::class);
    }

    public function services(): BelongsToMany
    {
        return $this->belongsToMany(ServiceItem::class, 'wash_transaction_services')
            ->withPivot(['quantity', 'price', 'subtotal'])
            ->withTimestamps();
    }

    public function fds(): BelongsToMany
    {
        return $this->belongsToMany(FdItem::class, 'wash_transaction_fds')
            ->withPivot(['quantity', 'price', 'subtotal'])
            ->withTimestamps();
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function workOrder(): BelongsTo
    {
        return $this->belongsTo(WorkOrder::class);
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function isInProgress(): bool
    {
        return $this->status === self::STATUS_IN_PROGRESS;
    }

    public function isCompleted(): bool
    {
        return $this->status === self::STATUS_COMPLETED;
    }

    public function isCancelled(): bool
    {
        return $this->status === self::STATUS_CANCELLED;
    }

    public function isCashPayment(): bool
    {
        return $this->payment_method === self::PAYMENT_METHOD_CASH;
    }

    public function isCashlessPayment(): bool
    {
        return $this->payment_method === self::PAYMENT_METHOD_CASHLESS;
    }

    public function isTransferPayment(): bool
    {
        return $this->payment_method === self::PAYMENT_METHOD_TRANSFER;
    }

    public static function getStatusOptions(): array
    {
        return [
            self::STATUS_PENDING => 'Pending',
            self::STATUS_IN_PROGRESS => 'In Progress',
            self::STATUS_COMPLETED => 'Completed',
            self::STATUS_CANCELLED => 'Cancelled',
        ];
    }

    public static function getPaymentMethodOptions(): array
    {
        return [
            self::PAYMENT_METHOD_CASH => 'Cash',
            self::PAYMENT_METHOD_CASHLESS => 'Cashless',
            self::PAYMENT_METHOD_TRANSFER => 'Transfer',
        ];
    }
}
