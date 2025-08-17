<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WorkOrderService extends Model
{
    use HasFactory;

    protected $table = 'wo_services';

    protected $fillable = [
        'work_order_id',
        'service_item_id',
        'qty',
        'unit_price',
        'is_custom',
        'custom_label',
        'is_premium_snapshot',
    ];

    public function workOrder(): BelongsTo
    {
        return $this->belongsTo(WorkOrder::class);
    }

    public function serviceItem(): BelongsTo
    {
        return $this->belongsTo(ServiceItem::class);
    }
}
