<?php

namespace App\Criteria;

use App\Models\App;
use App\Models\User;
use Heseya\Searchable\Criteria\Criterion;
use Illuminate\Database\Eloquent\Builder;

class WhereHasBuyer extends Criterion
{
    public function query(Builder $query): Builder
    {
        return $query->whereHas(
            'order',
            fn (Builder $query) => $query
                ->whereHasMorph(
                    'buyer',
                    $this->key === 'user' ? [User::class] : [App::class],
                    fn (Builder $query) => $query->where('id', $this->value),
                )
        );
    }
}
