<?php

namespace App\Criteria;

use Heseya\Searchable\Criteria\Criterion;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Config;

class ProductSearch extends Criterion
{
    public function query(Builder $query): Builder
    {
        if (!Config::get('full-text.full_text_search')) {
            return $query->where(function (Builder $query): void {
                $query->where('id', 'LIKE', '%' . $this->value . '%')
                    ->orWhere('slug', 'LIKE', '%' . $this->value . '%')
                    ->orWhere('name', 'LIKE', '%' . $this->value . '%')
                    ->orWhere('description_html', 'LIKE', '%' . $this->value . '%');
            });
        }

        return $query->whereFullText([
            'products.name',
            'products.description_html',
            'products.description_short',
            'products.search_values',
        ], $this->value);
    }
}
