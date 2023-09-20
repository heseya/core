<?php

namespace App\Criteria;

use Heseya\Searchable\Criteria\Criterion;
use Illuminate\Database\Eloquent\Builder;

class MediaSearch extends Criterion
{
    public function query(Builder $query): Builder
    {
        if ($this->value) {
            $query->where(function (Builder $query): void {
                $query
                    ->where('url', 'like', "%{$this->value}%")
                    ->orWhere('slug', 'like', "%{$this->value}%")
                    ->orWhere('alt', 'like', "%{$this->value}%")
                    ->orWhere('type', 'like', "%{$this->value}%");
            });
        }

        return $query;
    }
}
