<?php

namespace App\Criteria;

use Heseya\Searchable\Criteria\Criterion;
use Illuminate\Database\Eloquent\Builder;
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
                                $query->whereNotBetween($key, [$value['min'], $value['max']]);
                            } elseif (Arr::has($value, 'min')) {
                                $query->whereNot($key, '>=', $value['min']);
                            } elseif (Arr::has($value, 'max')) {
                                $query->whereNot($key, '<=', $value['max']);
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
