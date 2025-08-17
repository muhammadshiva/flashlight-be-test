<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class WorkOrder extends Model
{
    use HasFactory, SoftDeletes;

    const STATUS_PENDING = 'pending';
    const STATUS_CONFIRMED = 'confirmed';
    const STATUS_IN_PROGRESS = 'in_progress';
    const STATUS_READY_FOR_PICKUP = 'ready_for_pickup';
    const STATUS_COMPLETED = 'completed';
    const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'order_number',
        'customer_id',
        'customer_vehicle_id',
        'total_price',
        'order_date',
        'status',
        'notes',
        'special_instructions',
        'queue_number',
        'confirmed_at',
        'started_at',
        'completed_at',
    ];

    protected $casts = [
        'order_date' => 'datetime',
        'total_price' => 'float',
        'confirmed_at' => 'datetime',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    protected static function booted()
    {
        static::creating(function ($workOrder) {
            if (!isset($workOrder->total_price)) {
                $workOrder->total_price = 0;
            }
        });

        static::created(function ($workOrder) {
            $workOrder->customer?->updateTransactionCounts();
        });

        static::updated(function ($workOrder) {
            $workOrder->customer?->updateTransactionCounts();
        });

        static::deleted(function ($workOrder) {
            $workOrder->customer?->updateTransactionCounts();
        });

        static::restored(function ($workOrder) {
            $workOrder->customer?->updateTransactionCounts();
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

    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'work_order_products')
            ->withPivot(['quantity', 'price', 'subtotal'])
            ->withTimestamps();
    }

    public function washTransactions(): HasMany
    {
        return $this->hasMany(WashTransaction::class);
    }

    public function posTransaction()
    {
        // Get POS transaction through wash transaction
        return $this->hasOneThrough(
            POSTransaction::class,
            WashTransaction::class,
            'work_order_id', // Foreign key on wash_transactions table
            'wash_transaction_id', // Foreign key on pos_transactions table
            'id', // Local key on work_orders table
            'id' // Local key on wash_transactions table
        );
    }

    // Status check methods
    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function isConfirmed(): bool
    {
        return $this->status === self::STATUS_CONFIRMED;
    }

    public function isInProgress(): bool
    {
        return $this->status === self::STATUS_IN_PROGRESS;
    }

    public function isReadyForPickup(): bool
    {
        return $this->status === self::STATUS_READY_FOR_PICKUP;
    }

    public function isCompleted(): bool
    {
        return $this->status === self::STATUS_COMPLETED;
    }

    public function isCancelled(): bool
    {
        return $this->status === self::STATUS_CANCELLED;
    }

    public function hasPayment(): bool
    {
        return $this->posTransaction !== null;
    }

    public function hasWashTransaction(): bool
    {
        return $this->washTransactions()->exists();
    }

    public function getActiveWashTransaction()
    {
        return $this->washTransactions()
            ->whereNotIn('status', [WashTransaction::STATUS_CANCELLED])
            ->first();
    }

    public function createWashTransaction(array $data = []): WashTransaction
    {
        $defaultData = [
            'transaction_number' => $this->generateWashTransactionNumber(),
            'customer_id' => $this->customer_id,
            'customer_vehicle_id' => $this->customer_vehicle_id,
            'total_price' => $this->total_price,
            'wash_date' => $this->order_date,
            'status' => WashTransaction::STATUS_PENDING,
            'service_status' => WashTransaction::SERVICE_STATUS_WAITING,
            'queue_number' => WashTransaction::generateQueueNumber(),
            'notes' => $this->notes,
        ];

        $washTransaction = $this->washTransactions()->create(array_merge($defaultData, $data));

        // Copy products from work order to wash transaction
        foreach ($this->products as $product) {
            $washTransaction->products()->attach($product->id, [
                'quantity' => $product->pivot->quantity,
                'price' => $product->pivot->price,
                'subtotal' => $product->pivot->subtotal,
            ]);
        }

        return $washTransaction;
    }

    private function generateWashTransactionNumber(): string
    {
        $today = now()->format('Ymd');

        $lastTransaction = WashTransaction::whereDate('created_at', now())
            ->orderBy('created_at', 'desc')
            ->first();

        $sequence = 1;
        if ($lastTransaction) {
            if (preg_match('/TRX-\d{8}-(\d{3})/', $lastTransaction->transaction_number, $matches)) {
                $sequence = (int)$matches[1] + 1;
            }
        }

        return sprintf('TRX-%s-%03d', $today, $sequence);
    }

    public static function getStatusOptions(): array
    {
        return [
            self::STATUS_PENDING => 'Pending',
            self::STATUS_CONFIRMED => 'Confirmed',
            self::STATUS_IN_PROGRESS => 'In Progress',
            self::STATUS_READY_FOR_PICKUP => 'Ready for Pickup',
            self::STATUS_COMPLETED => 'Completed',
            self::STATUS_CANCELLED => 'Cancelled',
        ];
    }

    public static function generateOrderNumber(): string
    {
        $today = now()->format('Ymd');

        $lastOrder = static::whereDate('created_at', now())
            ->orderBy('created_at', 'desc')
            ->first();

        $sequence = 1;
        if ($lastOrder) {
            if (preg_match('/WO-\d{8}-(\d{3})/', $lastOrder->order_number, $matches)) {
                $sequence = (int)$matches[1] + 1;
            }
        }

        return sprintf('WO-%s-%03d', $today, $sequence);
    }

    public static function generateQueueNumber(): int
    {
        $today = now()->format('Y-m-d');

        $lastQueue = static::whereDate('order_date', $today)
            ->whereNotNull('queue_number')
            ->orderBy('queue_number', 'desc')
            ->first();

        return $lastQueue ? $lastQueue->queue_number + 1 : 1;
    }

    public function confirmAndCreateWashTransaction(): WashTransaction
    {
        $this->update([
            'status' => self::STATUS_CONFIRMED,
            'confirmed_at' => now(),
        ]);

        return $this->createWashTransaction();
    }
}
