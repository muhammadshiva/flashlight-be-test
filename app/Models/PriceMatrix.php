<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PriceMatrix extends Model
{
    use HasFactory;

    protected $table = 'price_matrix';

    protected $fillable = [
        'service_item_id',
        'engine_class_id',
        'helmet_type_id',
        'car_size_id',
        'apparel_type_id',
        'price',
    ];

    public function serviceItem(): BelongsTo
    {
        return $this->belongsTo(ServiceItem::class);
    }

    public function engineClass(): BelongsTo
    {
        return $this->belongsTo(EngineClass::class);
    }

    public function helmetType(): BelongsTo
    {
        return $this->belongsTo(HelmetType::class);
    }

    public function carSize(): BelongsTo
    {
        return $this->belongsTo(CarSize::class);
    }

    public function apparelType(): BelongsTo
    {
        return $this->belongsTo(ApparelType::class);
    }
}
