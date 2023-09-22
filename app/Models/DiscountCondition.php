<?php

namespace App\Models;

use App\Enums\ConditionType;
use Carbon\Carbon;
use Domain\Price\Dtos\PriceDto;
use Domain\Price\Enums\DiscountConditionPriceType;
use Domain\ProductSet\ProductSet;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphToMany;

/**
 * @property ConditionType $type
 *
 * @mixin IdeHelperDiscountCondition
 */
class DiscountCondition extends Model
{
    protected $fillable = [
        'type',
        'value',
        'condition_group_id',
    ];

    protected $casts = [
        'type' => ConditionType::class,
        'value' => 'array',
    ];

    public function getValueAttribute(string $value): array
    {
        $value = json_decode($value, true);

        if ($this->type->is(ConditionType::WEEKDAY_IN)) {
            $value['weekday'] = array_map(
                fn (string $key) => (bool) $key,
                mb_str_split(sprintf('%07d', decbin($value['weekday']))),
            );
        }

        if ($this->type->is(ConditionType::DATE_BETWEEN)) {
            if (array_key_exists('start_at', $value)) {
                $value['start_at'] = Carbon::parse($value['start_at'])->toISOString();
            }
            if (array_key_exists('end_at', $value)) {
                $value['end_at'] = Carbon::parse($value['end_at'])->toISOString();
            }
        }

        if ($this->type->is(ConditionType::ORDER_VALUE)) {
            $value['min_values'] = $this->pricesMin->map(fn (Price $price) => PriceDto::from($price))->all();
            $value['max_values'] = $this->pricesMax->map(fn (Price $price) => PriceDto::from($price))->all();
        }

        return $value;
    }

    public function setValueAttribute(mixed $value): void
    {
        if ($this->type->is(ConditionType::WEEKDAY_IN)) {
            $tmp = '';
            foreach ($value['weekday'] as $item) {
                $tmp .= $item ? '1' : '0';
            }

            $value['weekday'] = bindec($tmp);
        }

        $this->attributes['value'] = json_encode($value);
    }

    public function products(): MorphToMany
    {
        return $this->morphedByMany(
            Product::class,
            'model',
            'model_has_discount_conditions',
        );
    }

    public function productSets(): MorphToMany
    {
        return $this->morphedByMany(
            ProductSet::class,
            'model',
            'model_has_discount_conditions',
        );
    }

    public function users(): MorphToMany
    {
        return $this->morphedByMany(
            User::class,
            'model',
            'model_has_discount_conditions',
        );
    }

    public function roles(): MorphToMany
    {
        return $this->morphedByMany(
            Role::class,
            'model',
            'model_has_discount_conditions',
        );
    }

    public function conditionGroup(): BelongsTo
    {
        return $this->belongsTo(ConditionGroup::class);
    }

    public function pricesMin(): MorphMany
    {
        return $this->morphMany(Price::class, 'model')
            ->where('price_type', DiscountConditionPriceType::PRICE_MIN->value);
    }

    public function pricesMax(): MorphMany
    {
        return $this->morphMany(Price::class, 'model')
            ->where('price_type', DiscountConditionPriceType::PRICE_MAX->value);
    }
}
