<?php

namespace App\Services\Contracts;

use Illuminate\Database\Eloquent\Builder;
use Laravel\Scout\Builder as ScoutBuilder;

interface SortServiceContract
{
    public function sortScout(ScoutBuilder $query, ?string $sortString): ScoutBuilder;

    public function sort(Builder|ScoutBuilder $query, string $sortString, array $sortable): Builder|ScoutBuilder;
}
