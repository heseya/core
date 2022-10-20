<?php

namespace App\Criteria;

use Heseya\Searchable\Criteria\Criterion;
use Illuminate\Database\Eloquent\Builder;

class WebhookIsSuccessful extends Criterion
{
    public function query(Builder $query): Builder
    {
        return $this->value ?
            $query->whereBetween('status_code', [200, 299]) :
            $query->whereNotBetween('status_code', [200, 299]);
    }
}
