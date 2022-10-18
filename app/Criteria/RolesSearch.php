<?php

namespace App\Criteria;

use Heseya\Searchable\Criteria\Criterion;
use Illuminate\Database\Eloquent\Builder;

class RolesSearch extends Criterion
{
    public function query(Builder $query): Builder
    {
        if (!is_array($this->value)) {
            $this->value = [$this->value];
        }

        return $query->whereHas('roles', fn ($query) => $query->whereIn('id', $this->value));
    }
}
