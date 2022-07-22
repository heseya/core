<?php

namespace App\Criteria;

use Heseya\Searchable\Criteria\Criterion;
use Illuminate\Database\Eloquent\Builder;

class WebHookSearch extends Criterion
{
    public function query(Builder $query): Builder
    {
        return $query->where(function (Builder $query): void {
            $query->where('name', 'LIKE', '%' . $this->value . '%')
                ->orWhere('url', 'LIKE', '%' . $this->value . '%')
                ->orWhere('events', 'LIKE', '%' . $this->value . '%');
        });
    }
}
