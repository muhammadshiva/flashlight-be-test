<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;

class Customer extends Model
{
    use HasFactory, SoftDeletes;

    const MEMBERSHIP_STATUS_PENDING = 'pending';
    const MEMBERSHIP_STATUS_APPROVED = 'approved';
    const MEMBERSHIP_STATUS_REJECTED = 'rejected';

    protected $fillable = [
        'user_id',
        'address',
        'membership_type_id',
        'membership_status',
        'membership_expires_at',
        'is_active',
        'last_login_at',
        'total_transactions',
        'total_premium_transactions',
    ];

    protected $casts = [
        'membership_expires_at' => 'datetime',
        'last_login_at' => 'datetime',
        'is_active' => 'boolean',
        'total_transactions' => 'integer',
        'total_premium_transactions' => 'integer',
    ];

    protected static function booted()
    {
        static::saving(function ($customer) {
            if (
                $customer->isDirty('membership_expires_at') ||
                $customer->isDirty('membership_type_id') ||
                $customer->isDirty('membership_status')
            ) {
                // Check if membership has expired
                if ($customer->membership_expires_at && $customer->membership_expires_at->isPast()) {
                    $customer->membership_type_id = null;
                    $customer->membership_status = null;
                    $customer->membership_expires_at = null;
                }
            }
        });

        // Update transaction counts when a wash transaction is created
        static::updated(function ($customer) {
            $customer->updateTransactionCounts();
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
    }

    public function updateTransactionCounts(): void
    {
        $totalTransactions = $this->washTransactions()->count();
        $totalPremiumTransactions = $this->getTotalPremiumTransactionsAttribute();

        DB::table('customers')
            ->where('id', $this->id)
            ->update([
                'total_transactions' => $totalTransactions,
                'total_premium_transactions' => $totalPremiumTransactions
            ]);

        // Refresh the model to get updated values
        $this->refresh();
    }

    public function getTotalSpentAttribute(): float
    {
        return $this->washTransactions()->sum('total_price');
    }

    public function getTotalTransactionsAttribute(): int
    {
        return $this->washTransactions()->count();
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function membershipType(): BelongsTo
    {
        return $this->belongsTo(MembershipType::class);
    }

    public function vehicles(): HasMany
    {
        return $this->hasMany(CustomerVehicle::class);
    }

    public function washTransactions(): HasMany
    {
        return $this->hasMany(WashTransaction::class);
    }

    public function hasActiveMembership(): bool
    {
        return $this->membership_type_id &&
            $this->membership_expires_at &&
            $this->membership_expires_at->isFuture();
    }

    public function getTransactionCountInLastYear(): int
    {
        return $this->washTransactions()
            ->where('created_at', '>=', now()->subYear())
            ->count();
    }

    public function getTotalPremiumTransactionsAttribute(): int
    {
        return $this->washTransactions()
            ->whereHas('products', function ($query) {
                $query->where('is_premium', true);
            })
            ->whereRaw('
                (select count(*) from wash_transaction_products
                 where wash_transaction_products.wash_transaction_id = wash_transactions.id) > 1
            ')
            ->count();
    }
}
