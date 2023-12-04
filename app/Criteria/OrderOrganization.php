<?php

namespace App\Criteria;

use Heseya\Searchable\Criteria\Criterion;
use Illuminate\Database\Eloquent\Builder;

class OrderOrganization extends Criterion
{
    public function query(Builder $query): Builder
    {
        return $query->whereIn('organization_id', (array) $this->value);
    }
}
