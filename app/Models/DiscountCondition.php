<?php

namespace App\Models;

use App\Enums\ConditionType;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphToMany;

/**
 * @property ConditionType $type;
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
                fn (string $key) => boolval($key),
                str_split(sprintf('%07d', decbin($value['weekday'])))
            );
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
            'model_has_discount_conditions'
        );
    }

    public function productSets(): MorphToMany
    {
        return $this->morphedByMany(
            ProductSet::class,
            'model',
            'model_has_discount_conditions'
        );
    }

    public function users(): MorphToMany
    {
        return $this->morphedByMany(
            User::class,
            'model',
            'model_has_discount_conditions'
        );
    }

    public function roles(): MorphToMany
    {
        return $this->morphedByMany(
            Role::class,
            'model',
            'model_has_discount_conditions'
        );
    }

    public function conditionGroup(): BelongsTo
    {
        return $this->belongsTo(ConditionGroup::class);
    }
}
