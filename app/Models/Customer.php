<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

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
    ];

    protected $casts = [
        'membership_expires_at' => 'datetime',
        'last_login_at' => 'datetime',
        'is_active' => 'boolean',
    ];

    protected static function booted()
    {
        static::saving(function ($customer) {
            // Check if membership has expired
            if ($customer->membership_expires_at && $customer->membership_expires_at->isPast()) {
                $customer->membership_type_id = null;
                $customer->membership_status = null;
                $customer->membership_expires_at = null;
            }
        });
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
}
