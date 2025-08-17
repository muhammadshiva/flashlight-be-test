<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;

class PosTransactionProduct extends Pivot
{
    protected $table = 'pos_transaction_products';

    protected $fillable = [
        'pos_transaction_id',
        'product_id',
        'quantity',
        'price',
        'subtotal',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'price' => 'float',
        'subtotal' => 'float',
    ];

    public function posTransaction()
    {
        return $this->belongsTo(POSTransaction::class, 'pos_transaction_id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
