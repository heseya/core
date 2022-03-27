<?php

namespace App\Criteria;

use Heseya\Searchable\Criteria\Criterion;
use Illuminate\Database\Eloquent\Builder;

class UserSearch extends Criterion
{
    public function query(Builder $query): Builder
    {
        return $query->where(function (Builder $query): void {
            $query->where('name', 'LIKE', '%' . $this->value . '%')
                ->orWhere('email', 'LIKE', '%' . $this->value . '%');
        });
    }
}
