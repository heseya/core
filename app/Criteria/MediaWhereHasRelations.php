<?php

namespace App\Criteria;

use Heseya\Searchable\Criteria\Criterion;
use Illuminate\Database\Eloquent\Builder;

class MediaWhereHasRelations extends Criterion
{
    public function query(Builder $query): Builder
    {
        if (filter_var($this->value, FILTER_VALIDATE_BOOLEAN)) {
            $query->whereHas('products')
                ->orWhereHas('documents')
                ->orWhereHas('bannerMedia');
        } else {
            $query->whereDoesntHave('products')
                ->whereDoesntHave('documents')
                ->whereDoesntHave('bannerMedia');
        }

        return $query;
    }
}
