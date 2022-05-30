<?php

namespace App\Criteria;

use Heseya\Searchable\Criteria\Criterion;
use Illuminate\Database\Eloquent\Builder;

class MediaWhereHasRelations extends Criterion
{
    public function query(Builder $query): Builder
    {
        if ($this->value) {
            $query->whereHas('products')
                ->orWhereHas('documents')
                ->orWhereHas('bannerMedia');
        }

        return $query;
    }
}
