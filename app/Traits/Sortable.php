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

        if (($sortBy = $this->getDefaultSortBy()) !== null) {
            return $query->orderBy(
                $sortBy,
                $this->getDefaultSortDirection(),
            );
        }

        return $query;
    }

    public function getSortable(): array
    {
        // @phpstan-ignore-next-line
        return $this->sortable ?? [];
    }

    public function getDefaultSortBy(): ?string
    {
        // @phpstan-ignore-next-line
        return $this->defaultSortBy ?? null;
    }

    public function getDefaultSortDirection(): string
    {
        // @phpstan-ignore-next-line
        return $this->defaultSortDirection ?? 'asc';
    }
}
