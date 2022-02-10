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
        'searchable',
        'options',
    ];

    protected $casts = [
        'type' => AttributeType::class,
        'searchable' => 'boolean',
        'options' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
}
