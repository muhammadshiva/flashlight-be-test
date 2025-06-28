<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Shift extends Model
{
    use HasFactory, SoftDeletes;

    const STATUS_ACTIVE = 'active';
    const STATUS_CLOSED = 'closed';
    const STATUS_CANCELED = 'canceled';

    protected $fillable = [
        'user_id',
        'start_time',
        'end_time',
        'initial_cash',
        'received_from',
        'final_cash',
        'total_sales',
        'status',
    ];

    protected $casts = [
        'start_time' => 'datetime',
        'end_time' => 'datetime',
        'initial_cash' => 'decimal:2',
        'final_cash' => 'decimal:2',
        'total_sales' => 'decimal:2',
    ];

    /**
     * Relationship with User model
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Relationship with WashTransaction model
     */
    public function washTransactions(): HasMany
    {
        return $this->hasMany(WashTransaction::class);
    }

    /**
     * Status checking methods
     */
    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    public function isClosed(): bool
    {
        return $this->status === self::STATUS_CLOSED;
    }

    public function isCanceled(): bool
    {
        return $this->status === self::STATUS_CANCELED;
    }

    /**
     * Get all status options
     */
    public static function getStatusOptions(): array
    {
        return [
            self::STATUS_ACTIVE => 'Active',
            self::STATUS_CLOSED => 'Closed',
            self::STATUS_CANCELED => 'Canceled',
        ];
    }

    /**
     * Calculate total sales from transactions
     */
    public function calculateTotalSales(): float
    {
        return $this->washTransactions()
            ->whereIn('status', ['completed'])
            ->sum('total_price');
    }

    /**
     * Calculate cash difference (final_cash - expected_cash)
     */
    public function calculateCashDifference(): float
    {
        if (!$this->final_cash) {
            return 0;
        }

        $expectedCash = $this->initial_cash + $this->total_sales;
        return $this->final_cash - $expectedCash;
    }

    /**
     * Close the shift with final calculations
     */
    public function close(float $finalCash): bool
    {
        $this->final_cash = $finalCash;
        $this->total_sales = $this->calculateTotalSales();
        $this->end_time = now();
        $this->status = self::STATUS_CLOSED;

        return $this->save();
    }

    /**
     * Check if user has active shift
     */
    public static function hasActiveShift(int $userId): bool
    {
        return self::where('user_id', $userId)
            ->where('status', self::STATUS_ACTIVE)
            ->exists();
    }

    /**
     * Get active shift for user
     */
    public static function getActiveShift(int $userId): ?self
    {
        return self::where('user_id', $userId)
            ->where('status', self::STATUS_ACTIVE)
            ->first();
    }

    /**
     * Scope for active shifts
     */
    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    /**
     * Scope for closed shifts
     */
    public function scopeClosed($query)
    {
        return $query->where('status', self::STATUS_CLOSED);
    }
}
