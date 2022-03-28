<?php

namespace App\Criteria;

use Heseya\Searchable\Criteria\Criterion;
use Illuminate\Database\Eloquent\Builder;

class MetadataSearch extends Criterion
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
