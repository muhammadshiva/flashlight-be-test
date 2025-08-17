<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WorkOrderFd extends Model
{
    use HasFactory;

    protected $table = 'wo_fds';

    protected $fillable = [
        'work_order_id',
        'fd_item_id',
        'qty',
        'unit_price',
    ];

    public function workOrder(): BelongsTo
    {
        return $this->belongsTo(WorkOrder::class);
    }

    public function fdItem(): BelongsTo
    {
        return $this->belongsTo(FdItem::class);
    }
}
