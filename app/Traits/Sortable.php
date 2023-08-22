<?php

namespace App\Traits;

use App\Services\Contracts\SortServiceContract;
use Illuminate\Database\Eloquent\Builder;

trait Sortable
{
    public function scopeSort(Builder $query, ?string $sortString = null): Builder
    {
        if ($sortString !== null) {
            $query = app(SortServiceContract::class)
                ->sort($query, $sortString, $this->getSortable());
        }

        return $query->orderBy(
            $this->getDefaultSortBy(),
            $this->getDefaultSortDirection(),
        );
    }

    public function getSortable(): array
    {
        // @phpstan-ignore-next-line
        return $this->sortable ?? [];
    }

    public function getDefaultSortBy(): string
    {
        // @phpstan-ignore-next-line
        return $this->defaultSortBy ?? 'created_at';
    }

    public function getDefaultSortDirection(): string
    {
        // @phpstan-ignore-next-line
        return $this->defaultSortDirection ?? 'asc';
    }
}
