<?php

namespace App\Models\Contracts;

use Illuminate\Database\Eloquent\Builder;

interface SortableContract
{
    public function scopeSort(Builder $query, ?string $sortString = null): Builder|\Laravel\Scout\Builder;

    public function getSortable(): array;

    public function getDefaultSortBy(): string;

    public function getDefaultSortDirection(): string;
}
