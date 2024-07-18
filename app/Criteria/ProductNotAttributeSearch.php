<?php

namespace App\Criteria;

use Heseya\Searchable\Criteria\Criterion;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Support\Arr;

class ProductNotAttributeSearch extends Criterion
{
    public function query(Builder $query): Builder
    {
        if ($this->value === null) {
            return $query;
        }

        foreach ($this->value as $key => $value) {
            $query->whereHas('attributes', function (Builder $query) use ($key, $value): void {
                $query
                    ->where('slug', '=', $key)
                    ->whereExists(function ($query) use ($value): void {
                        $query
                            ->from('product_attribute_attribute_option')
                            ->whereRaw(
                                '`product_attribute`.`pivot_id` = `product_attribute_attribute_option`.`product_attribute_id`',
                            );

                        if (is_array($value)) {
                            $query->join(
                                'attribute_options',
                                'product_attribute_attribute_option.attribute_option_id',
                                '=',
                                'attribute_options.id',
                            );

                            $checkValue = array_key_exists('value', $value) && is_array($value['value']) ? $value['value'] : $value;

                            $key = 'attribute_options.value_number';
                            if (!is_numeric(Arr::first($checkValue))) {
                                $key = strtotime(Arr::first($checkValue)) ? 'attribute_options.value_date' : 'attribute_options.name';
                            }

                            if (Arr::has($value, ['min', 'max'])) {
                                $query->whereNotBetween($key, [$value['min'], $value['max']]);
                            } elseif (Arr::has($value, 'min')) {
                                $query->whereNot($key, '>=', $value['min']);
                            } elseif (Arr::has($value, 'max')) {
                                $query->whereNot($key, '<=', $value['max']);
                            } elseif (Arr::has($value, 'value')) {
                                if ($key === 'attribute_options.name') {
                                    if (is_array($value['value'])) {
                                        $query->where(function (QueryBuilder $q) use ($value): void {
                                            foreach ($value['value'] as $search) {
                                                $q->whereNot('name', 'like', "%{$search}%");
                                            }
                                        });
                                    } else {
                                        $query->whereNot($key, 'like', "%{$value['value']}%");
                                    }
                                } elseif (is_array($value['value'])) {
                                    $query->whereNotIn($key, $value['value']);
                                } else {
                                    $query->whereNot($key, $value['value']);
                                }
                            }
                        } else {
                            $query->whereNot('product_attribute_attribute_option.attribute_option_id', '=', $value);
                        }
                    });
            });
        }

        return $query;
    }
}
