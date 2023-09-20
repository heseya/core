<?php

namespace App\Services\Contracts;

use Illuminate\Database\Eloquent\Builder;

interface SortServiceContract
{
    public function sort(Builder $query, string $sortString, array $sortable): Builder;
}
