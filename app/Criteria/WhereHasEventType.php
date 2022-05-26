<?php

namespace App\Criteria;

use Heseya\Searchable\Criteria\Criterion;
use Illuminate\Database\Eloquent\Builder;

class WhereHasEventType extends Criterion
{
    public function query(Builder $query): Builder
    {
        return $query->whereHas('webHook', function (Builder $webhooks): void {
            $webhooks->where('events', 'like', '%' . $this->value . '%');
        });
    }
}
