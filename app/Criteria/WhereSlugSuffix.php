<?php

namespace App\Criteria;

use Heseya\Searchable\Criteria\Criterion;
use Illuminate\Database\Eloquent\Builder;

class WhereSlugSuffix extends Criterion
{
    public function query(Builder $query): Builder
    {
        return $query->where('slug', 'LIKE', '%' . $this->value . '%')->where(function (Builder $query): void {
            $query
                ->whereNull('parent_id')
                ->orWhereDoesntHave('parent', function (Builder $query): void {
                    $query->where('slug', 'LIKE', '%' . $this->value . '%');
                });
        });
    }
}
