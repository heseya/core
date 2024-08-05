<?php

namespace App\Criteria;

use Heseya\Searchable\Criteria\Criterion;
use Illuminate\Database\Eloquent\Builder;

class WhereHasSalesChannel extends Criterion
{
    public function query(Builder $query): Builder
    {
        return $query->whereHas('salesChannels', function (Builder $query): void {
            $query->where('id', '=', $this->value);
        });
    }
}
