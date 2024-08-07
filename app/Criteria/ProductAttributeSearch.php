<?php

namespace App\Criteria;

use Heseya\Searchable\Criteria\Criterion;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Arr;

class ProductAttributeSearch extends Criterion
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
                                '`product_attribute`.`id` = `product_attribute_attribute_option`.`product_attribute_id`',
                            );

                        if (is_array($value)) {
                            $query->join(
                                'attribute_options',
                                'product_attribute_attribute_option.attribute_option_id',
                                '=',
                                'attribute_options.id',
                            );

                            $key = is_numeric(Arr::first($value)) ?
                                'attribute_options.value_number' : 'attribute_options.value_date';

                            if (Arr::has($value, ['min', 'max'])) {
                                $query->whereBetween($key, [$value['min'], $value['max']]);
                            } elseif (Arr::has($value, 'min')) {
                                $query->where($key, '>=', $value['min']);
                            } elseif (Arr::has($value, 'max')) {
                                $query->where($key, '<=', $value['max']);
                            } else {
                                $query->where(function ($query) use ($value): void {
                                    foreach ($value as $option) {
                                        $query->orWhere(
                                            'product_attribute_attribute_option.attribute_option_id',
                                            $option,
                                        );
                                    }
                                });
                            }
                        } else {
                            $query->where('product_attribute_attribute_option.attribute_option_id', $value);
                        }
                    });
            });
        }

        return $query;
    }
}
