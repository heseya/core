<?php

namespace App\Criteria;

use Heseya\Searchable\Criteria\Criterion;
use Illuminate\Database\Eloquent\Builder;

class MediaWhereHasRelations extends Criterion
{
    public function query(Builder $query): Builder
    {
        if ($this->value) {
            $query->where(function (Builder $subquery): void {
                $subquery->whereHas('products')
                    ->orWhereHas('documents')
                    ->orWhereHas('bannerMedia');
            });
        } else {
            $query->where(function (Builder $subquery): void {
                $subquery->whereDoesntHave('products')
                    ->whereDoesntHave('documents')
                    ->whereDoesntHave('bannerMedia');
            });
        }

        return $query;
    }
}
