<?php

namespace App\Models;

use App\Casts\MetadataValue;
use App\Enums\MetadataType;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class MetadataPersonal extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'value',
        'value_type',
    ];

    protected $casts = [
        'value' => MetadataValue::class,
        'value_type' => MetadataType::class,
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
}
