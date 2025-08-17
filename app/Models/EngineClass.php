<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EngineClass extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
    ];
}
