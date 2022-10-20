<?php

namespace App\Criteria;

use Heseya\Searchable\Criteria\Criterion;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

class WhereCreatedBefore extends Criterion
{
    public function query(Builder $query): Builder
    {
        if (!Str::contains($this->value, ':')) {
            $this->value = Str::before($this->value, 'T') . 'T23:59:59';
        }
        return $query->where('created_at', '<=', $this->value);
    }
}
