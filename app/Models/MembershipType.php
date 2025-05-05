<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class MembershipType extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'benefits',
        'is_active',
    ];

    protected $casts = [
        'benefits' => 'array',
        'is_active' => 'boolean',
    ];

    public function customers(): HasMany
    {
        return $this->hasMany(Customer::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    // Accessor for readable benefits string
    public function getBenefitsTextAttribute(): string
    {
        if (!is_array($this->benefits)) {
            return '';
        }

        return implode(', ', array_column($this->benefits, 'benefit'));
    }
}
