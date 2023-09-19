<?php

namespace App\Traits;

use App\Services\Contracts\SortServiceContract;
use Illuminate\Database\Eloquent\Builder;

trait Sortable
{
    public function scopeSort(Builder $query, ?string $sortString = null): Builder
    {
        if ($sortString === null) {
            return $query;
        }

        return app(SortServiceContract::class)
            ->sort($query, $sortString, $this->getSortable());
    }

    public function getSortable(): array
    {
        // @phpstan-ignore-next-line
        return $this->sortable ?? [];
    }
}
