<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\PosTransactionProduct;

class POSTransaction extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'pos_transactions';

    const STATUS_PENDING = 'pending';
    const STATUS_COMPLETED = 'completed';
    const STATUS_CANCELLED = 'cancelled';
    const STATUS_REFUNDED = 'refunded';

    const PAYMENT_METHOD_CASH = 'cash';
    const PAYMENT_METHOD_QRIS = 'qris';
    const PAYMENT_METHOD_TRANSFER = 'transfer';
    const PAYMENT_METHOD_E_WALLET = 'e_wallet';

    protected $fillable = [
        'transaction_number',
        'work_order_id',
        'wash_transaction_id',
        'customer_id',
        'customer_vehicle_id',
        'user_id',
        'shift_id',
        'subtotal',
        'tax_amount',
        'discount_amount',
        'total_amount',
        'payment_method',
        'amount_paid',
        'change_amount',
        'transaction_date',
        'status',
        'notes',
        'receipt_data',
        'completed_at',
        'payment_started_at',
        'payment_verified_at',
        'reference_number',
    ];

    protected $casts = [
        'subtotal' => 'float',
        'tax_amount' => 'float',
        'discount_amount' => 'float',
        'total_amount' => 'float',
        'amount_paid' => 'float',
        'change_amount' => 'float',
        'transaction_date' => 'datetime',
        'receipt_data' => 'array',
        'completed_at' => 'datetime',
        'payment_started_at' => 'datetime',
        'payment_verified_at' => 'datetime',
    ];

    protected static function booted()
    {
        static::creating(function ($transaction) {
            if (!isset($transaction->subtotal)) {
                $transaction->subtotal = 0;
            }
            if (!isset($transaction->total_amount)) {
                $transaction->total_amount = 0;
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

    public function washTransaction(): BelongsTo
    {
        return $this->belongsTo(WashTransaction::class);
    }

    public function workOrder(): BelongsTo
    {
        // Get work order through wash transaction relationship
        return $this->belongsTo(WorkOrder::class, 'work_order_id');
    }

    public function getWorkOrderAttribute()
    {
        // Get work order through wash transaction if work_order_id is null
        if ($this->work_order_id) {
            return $this->belongsTo(WorkOrder::class, 'work_order_id')->first();
        }

        return $this->washTransaction?->workOrder;
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function customerVehicle(): BelongsTo
    {
        return $this->belongsTo(CustomerVehicle::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function shift(): BelongsTo
    {
        return $this->belongsTo(Shift::class);
    }

    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'pos_transaction_products')
            ->using(PosTransactionProduct::class)
            ->withPivot(['quantity', 'price', 'subtotal'])
            ->withTimestamps();
    }

    // Status check methods
    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function isCompleted(): bool
    {
        return $this->status === self::STATUS_COMPLETED;
    }

    public function isCancelled(): bool
    {
        return $this->status === self::STATUS_CANCELLED;
    }

    public function isRefunded(): bool
    {
        return $this->status === self::STATUS_REFUNDED;
    }

    // Payment method check methods
    public function isCashPayment(): bool
    {
        return $this->payment_method === self::PAYMENT_METHOD_CASH;
    }

    public function isQrisPayment(): bool
    {
        return $this->payment_method === self::PAYMENT_METHOD_QRIS;
    }

    public function isTransferPayment(): bool
    {
        return $this->payment_method === self::PAYMENT_METHOD_TRANSFER;
    }

    public function isEWalletPayment(): bool
    {
        return $this->payment_method === self::PAYMENT_METHOD_E_WALLET;
    }

    public function isFromWorkOrder(): bool
    {
        return $this->washTransaction && $this->washTransaction->work_order_id !== null;
    }

    public function isFromWashTransaction(): bool
    {
        return $this->wash_transaction_id !== null;
    }

    public static function getStatusOptions(): array
    {
        return [
            self::STATUS_PENDING => 'Pending',
            self::STATUS_COMPLETED => 'Completed',
            self::STATUS_CANCELLED => 'Cancelled',
            self::STATUS_REFUNDED => 'Refunded',
        ];
    }

    public static function getPaymentMethodOptions(): array
    {
        return [
            self::PAYMENT_METHOD_CASH => 'Cash',
            self::PAYMENT_METHOD_QRIS => 'QRIS',
            self::PAYMENT_METHOD_TRANSFER => 'Transfer',
            self::PAYMENT_METHOD_E_WALLET => 'E-Wallet',
        ];
    }

    public static function generateTransactionNumber(): string
    {
        $today = now()->format('Ymd');

        $lastTransaction = static::whereDate('created_at', now())
            ->orderBy('created_at', 'desc')
            ->first();

        $sequence = 1;
        if ($lastTransaction) {
            if (preg_match('/POS-\d{8}-(\d{3})/', $lastTransaction->transaction_number, $matches)) {
                $sequence = (int)$matches[1] + 1;
            }
        }

        return sprintf('POS-%s-%03d', $today, $sequence);
    }

    public static function createFromWashTransaction(WashTransaction $washTransaction, array $paymentData): static
    {
        $transaction = static::create([
            'transaction_number' => static::generateTransactionNumber(),
            'wash_transaction_id' => $washTransaction->id,
            'work_order_id' => $washTransaction->work_order_id, // For backward compatibility
            'customer_id' => $washTransaction->customer_id,
            'customer_vehicle_id' => $washTransaction->customer_vehicle_id,
            'user_id' => $paymentData['user_id'],
            'shift_id' => $paymentData['shift_id'] ?? null,
            'subtotal' => $washTransaction->total_price,
            'tax_amount' => $paymentData['tax_amount'] ?? 0,
            'discount_amount' => $paymentData['discount_amount'] ?? 0,
            'total_amount' => $washTransaction->total_price + ($paymentData['tax_amount'] ?? 0) - ($paymentData['discount_amount'] ?? 0),
            'payment_method' => $paymentData['payment_method'],
            'amount_paid' => $paymentData['amount_paid'],
            'change_amount' => max(0, $paymentData['amount_paid'] - ($washTransaction->total_price + ($paymentData['tax_amount'] ?? 0) - ($paymentData['discount_amount'] ?? 0))),
            'transaction_date' => now(),
            'status' => static::STATUS_COMPLETED,
            'notes' => $paymentData['notes'] ?? null,
            'completed_at' => now(),
            'payment_started_at' => now(),
            'payment_verified_at' => now(),
            'reference_number' => $paymentData['reference_number'] ?? null,
        ]);

        // Copy products from wash transaction to POS transaction
        foreach ($washTransaction->products as $product) {
            $transaction->products()->attach($product->id, [
                'quantity' => $product->pivot->quantity,
                'price' => $product->pivot->price,
                'subtotal' => $product->pivot->subtotal,
            ]);
        }

        // Update wash transaction status
        $washTransaction->update([
            'status' => WashTransaction::STATUS_COMPLETED,
        ]);

        return $transaction;
    }

    public function getSourceType(): string
    {
        if ($this->isFromWorkOrder()) {
            return 'Work Order';
        } elseif ($this->isFromWashTransaction()) {
            return 'Direct Wash Transaction';
        } else {
            return 'Direct Sale';
        }
    }
}
