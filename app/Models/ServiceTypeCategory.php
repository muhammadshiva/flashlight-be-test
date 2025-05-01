<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class ServiceTypeCategory extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = ['name', 'image'];

    public function serviceTypes(): HasMany
    {
        return $this->hasMany(ServiceType::class, 'category_id');
    }

    public function getImageUrlAttribute(): ?string
    {
        if (!$this->image) return null;
        if (str_starts_with($this->image, 'http')) return $this->image;
        return asset('storage/' . $this->image);
    }

    protected $appends = ['image_url'];
}
