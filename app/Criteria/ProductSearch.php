<?php

namespace App\Criteria;

use Heseya\Searchable\Criteria\Criterion;
use Illuminate\Database\Eloquent\Builder;

class ProductSearch extends Criterion
{
    public function query(Builder $query): Builder
    {
        if ($this->value === null) {
            return $query;
        }

        return $query->where(function (Builder $query): void {
            $query->where('id', 'LIKE', '%' . $this->value . '%')
                ->orWhere('slug', 'LIKE', '%' . $this->value . '%')
                ->orWhere('name', 'LIKE', '%' . $this->value . '%')
                ->orWhere('description_html', 'LIKE', '%' . $this->value . '%');
        });
    }
}
