<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Staff extends Model
{
    use HasFactory, SoftDeletes;

    const POSITION_RED = 'red';
    const POSITION_GREY = 'grey';
    const POSITION_BLACK = 'black';

    protected $fillable = [
        'user_id',
        'position',
        'salary',
        'hire_date',
        'is_active',
        'notes',
    ];

    protected $casts = [
        'hire_date' => 'date',
        'salary' => 'float',
        'is_active' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }



    public static function getPositionOptions(): array
    {
        return [
            self::POSITION_RED => 'Red (Highest Level)',
            self::POSITION_GREY => 'Grey (Middle Level)',
            self::POSITION_BLACK => 'Black (Lowest Level)',
        ];
    }

    public function isRedPosition(): bool
    {
        return $this->position === self::POSITION_RED;
    }

    public function isGreyPosition(): bool
    {
        return $this->position === self::POSITION_GREY;
    }

    public function isBlackPosition(): bool
    {
        return $this->position === self::POSITION_BLACK;
    }
}
