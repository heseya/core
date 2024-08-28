<?php

namespace App\Criteria;

use Heseya\Searchable\Criteria\Criterion;
use Illuminate\Database\Eloquent\Builder;

class WhereHasOrganization extends Criterion
{
    public function query(Builder $query): Builder
    {
        return $query->whereHas(
            'order',
            fn (Builder $query) => $query
                ->whereDoesntHave(
                    'status',
                    fn (Builder $query) => $query->where('cancel', '!=', false),
                )
                ->whereHas(
                    'organization',
                    fn (Builder $query) => $query->where('id', $this->value),
                ),
        );
    }
}
