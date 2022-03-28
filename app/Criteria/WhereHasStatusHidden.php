<?php

namespace App\Criteria;

use Heseya\Searchable\Criteria\Criterion;
use Illuminate\Database\Eloquent\Builder;

class WhereHasStatusHidden extends Criterion
{
    public function query(Builder $query): Builder
    {
        return $query->where(function (Builder $query): void {
            $query->whereHas('status', function (Builder $query) {
                return $query->where('hidden', $this->value);
            });

            if (!$this->value) {
                $query->orWhereDoesntHave('status');
            }
        });
    }
}
