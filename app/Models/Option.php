<?php

namespace App\Models;

use App\Traits\HasMetadata;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * @mixin IdeHelperOption
 */
class Option extends Model
{
    use HasFactory;
    use HasMetadata;

    protected $fillable = [
        'name',
        'price',
        'disabled',
        'schema_id',
        'order',
        'available',
        'shipping_time',
        'shipping_date',
    ];

    protected $casts = [
        'price' => 'float',
        'disabled' => 'bool',
        'available' => 'bool',
    ];

    public function items(): BelongsToMany
    {
        return $this
            ->belongsToMany(Item::class, 'option_items')
            ->withPivot('required_quantity');
    }

    public function schema(): BelongsTo
    {
        return $this->belongsTo(Schema::class);
    }
}
