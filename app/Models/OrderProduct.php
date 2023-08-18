<?php

namespace App\Models;

use App\Criteria\WhereHasBuyer;
use App\Criteria\WhereOrderProductPaid;
use App\Traits\HasOrderDiscount;
use App\Traits\Sortable;
use Brick\Money\Money;
use Domain\Currency\Currency;
use Heseya\Searchable\Traits\HasCriteria;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @mixin IdeHelperOrderProduct
 */
class OrderProduct extends Model
{
    use HasCriteria;
    use HasFactory;
    use HasOrderDiscount;
    use Sortable;

    protected $fillable = [
        'quantity',
        'order_id',
        'product_id',
        'name',
        'vat_rate',
        'shipping_digital',
        'is_delivered',

        'currency',
        'base_price_initial',
        'base_price',
        'price_initial',
        'price',
    ];

    protected $casts = [
        'vat_rate' => 'float',
        'shipping_digital' => 'boolean',
        'is_delivered' => 'boolean',
        'currency' => Currency::class,
    ];

    protected array $criteria = [
        'shipping_digital',
        'user' => WhereHasBuyer::class,
        'app' => WhereHasBuyer::class,
        'product_id',
        'paid' => WhereOrderProductPaid::class,
    ];

    protected array $sortable = [
        'created_at',
    ];

    public function base_price_initial(): Attribute
    {
        return self::priceAttributeTemplate('base_price_initial');
    }

    public function base_price(): Attribute
    {
        return self::priceAttributeTemplate('base_price');
    }

    public function price_initial(): Attribute
    {
        return self::priceAttributeTemplate('price_initial');
    }

    public function price(): Attribute
    {
        return self::priceAttributeTemplate('price');
    }

    public function schemas(): HasMany
    {
        return $this->hasMany(OrderSchema::class);
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class)->withTrashed();
    }

    public function deposits(): HasMany
    {
        return $this->hasMany(Deposit::class);
    }

    public function urls(): HasMany
    {
        return $this->hasMany(OrderProductUrl::class);
    }

    private static function priceAttributeTemplate(string $fieldName): Attribute
    {
        return Attribute::make(
            get: fn (mixed $value, array $attributes): Money => Money::ofMinor(
                $attributes[$fieldName],
                $attributes['currency'],
            ),
            set: fn (int|Money|string $value): array => match (true) {
                $value instanceof Money => [
                    $fieldName => $value->getMinorAmount(),
                    'currency' => $value->getCurrency()->getCurrencyCode(),
                ],
                default => [
                    $fieldName => $value,
                ]
            }
        );
    }
}
