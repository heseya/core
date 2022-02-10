<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class AttributeOption extends Model
{
    use HasFactory;

    protected $fillable = [
        'value_text',
        'value',
        'attribute_id',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
}
