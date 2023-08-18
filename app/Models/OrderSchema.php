<?php

namespace App\Models;

use Brick\Money\Money;
use Domain\Currency\Currency;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * @mixin IdeHelperOrderSchema
 */
class OrderSchema extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'value',
        'order_product_id',

        'currency',
        'price_initial',
        'price',
    ];

    protected $casts = [
        'currency' => Currency::class,
    ];

    public function price_initial(): Attribute
    {
        return self::priceAttributeTemplate('price_initial');
    }

    public function price(): Attribute
    {
        return self::priceAttributeTemplate('price');
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
