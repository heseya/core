<?php

namespace App\Criteria;

use App\Models\User;
use Heseya\Searchable\Criteria\Criterion;
use Illuminate\Database\Eloquent\Builder;

class WhereHasUser extends Criterion
{
    public function query(Builder $query): Builder
    {
        return $query->whereHas(
            'order',
            fn (Builder $query) => $query
                ->whereHasMorph(
                    'buyer',
                    [User::class],
                    fn (Builder $query) => $query->where('id', $this->value),
                )
        );
    }
}
