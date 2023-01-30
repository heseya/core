<?php

namespace App\Criteria;

use Heseya\Searchable\Criteria\Criterion;
use Illuminate\Database\Eloquent\Builder;

class MetadataSearch extends Criterion
{
    public function makeQuery(Builder $query, string $relation): Builder
    {
        foreach ($this->value as $key => $value) {
            $query->whereHas($relation, function (Builder $query) use ($key, $value): void {
                $query->where('name', '=', $key)
                    ->where('value', '=', $value);
            });
        }

        return $query;
    }

    public function query(Builder $query): Builder
    {
        return $this->makeQuery($query, 'metadata');
    }
}
