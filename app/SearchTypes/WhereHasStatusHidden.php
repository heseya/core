<?php

namespace App\SearchTypes;

use Heseya\Searchable\Searches\Search;
use Illuminate\Database\Eloquent\Builder;

class WhereHasStatusHidden extends Search
{
    public function query(Builder $query): Builder
    {
        $query->whereHas('status', function (Builder $query) {
            return $query->where('hidden', $this->value);
        });

        if (!$this->value) {
            $query->orWhereDoesntHave('status');
        }

        return $query;
    }
}
