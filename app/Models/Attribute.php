<?php

namespace App\Models;

use App\Enums\AttributeType;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Attribute extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'type',
        'global',
    ];

    protected $casts = [
        'type' => AttributeType::class,
        'global' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
}
