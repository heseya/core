<?php declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @mixin IdeHelperProductAvailability
 */
class ProductAvailability extends Model
{
    public $timestamps = null;

    protected $fillable = [
        'product_id',
        'quantity',
        'shipping_time',
        'shipping_date',
    ];

    protected $dates = [
        'shipping_date',
    ];

    protected $casts = [
        'quantity' => 'float',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
