<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WorkOrder extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'queue_no',
        'queue_date',
        'branch_id',
        'customer_id',
        'vehicle_id',
        'status',
        'membership_hint_status',
        'membership_hint_type',
        'membership_hint_fetched_at',
        'special_request_note',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'queue_date' => 'date',
        'membership_hint_fetched_at' => 'datetime',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function services(): HasMany
    {
        return $this->hasMany(WorkOrderService::class);
    }

    public function fds(): HasMany
    {
        return $this->hasMany(WorkOrderFd::class);
    }

    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }
}
