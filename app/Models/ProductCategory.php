<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProductCategory extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'product_categories';

    protected $fillable = [
        'name',
        'icon_image',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function products(): HasMany
    {
        return $this->hasMany(Product::class, 'category_id');
    }

    public function getIconImageUrlAttribute(): ?string
    {
        if (!$this->icon_image) return null;
        if (str_starts_with($this->icon_image, 'http')) return $this->icon_image;
        return asset('storage/' . $this->icon_image);
    }
}
