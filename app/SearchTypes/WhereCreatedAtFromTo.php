<?php

namespace App\SearchTypes;

use App\Enums\DateSearchType;
use Heseya\Searchable\Searches\Search;
use Illuminate\Database\Eloquent\Builder;

class WhereCreatedAtFromTo extends Search
{
    public function query(Builder $query): Builder
    {
        if (DateSearchType::hasValue($this->key)) {
            $operator = match ($this->key) {
                DateSearchType::FROM => '>=',
                DateSearchType::TO => '<=',
            };
            return $query->where('created_at', $operator, $this->value);
        }
        return $query;
    }
}
