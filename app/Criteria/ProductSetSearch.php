<?php

namespace App\Criteria;

use Heseya\Searchable\Criteria\Criterion;
use Illuminate\Database\Eloquent\Builder;

class ProductSetSearch extends Criterion
{
    public function query(Builder $query): Builder
    {
        return $query->where(function (Builder $query): void {
            $query->where('id', 'LIKE', '%' . $this->value . '%')
                ->orWhere('name', 'LIKE', '%' . $this->value . '%')
                ->orWhere('slug', 'LIKE', '%' . $this->value . '%')
                ->orWhereHas('parent', function (Builder $query): void {
                    $query
                        ->where('name', 'LIKE', '%' . $this->value . '%')
                        ->orWhere('slug', 'LIKE', '%' . $this->value . '%');
                })
                ->orWhereHas('children', function (Builder $query): void {
                    $query
                        ->where('name', 'LIKE', '%' . $this->value . '%')
                        ->orWhere('slug', 'LIKE', '%' . $this->value . '%');
                });
        });
    }
}
