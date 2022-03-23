<?php

namespace App\SearchTypes;

use Heseya\Searchable\Searches\Search;
use Illuminate\Database\Eloquent\Builder;

class MetadataSearch extends Search
{
    public function query(Builder $query): Builder
    {
        return $query->where(function (Builder $query): void {
            $query->whereHas('metadata', function (Builder $query): void {
                $first = true;
                foreach ($this->value as $key => $value) {
                    if ($first) {
                        $query->where('name', 'LIKE', "%${key}%")
                            ->where('value', 'LIKE', "%${value}%");
                        $first = false;
                    } else {
                        $query->orWhere('name', 'LIKE', "%${key}%")
                            ->where('value', 'LIKE', "%${value}%");
                    }
                }
            });
        });
    }
}
