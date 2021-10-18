<?php

namespace Heseya\Sortable;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Validator;

trait Sortable
{
    public function scopeSort(Builder $query, ?string $sortString = null): Builder
    {
        if ($sortString !== null) {
            $sort = explode(',', $sortString);

            foreach ($sort as $option) {
                $option = explode(':', $option);

                Validator::make($option, [
                    '0' => ['required', 'in:' . implode(',', $this->getSortable())],
                    '1' => ['in:asc,desc'],
                ])->validate();

                $order = count($option) > 1 ? $option[1] : 'asc';
                $query->orderBy($option[0], $order);
            }
        }

        return $query->orderBy(
            $this->getDefaultSortBy(),
            $this->getDefaultSortDirection(),
        );
    }

    private function getSortable(): array
    {
        return $this->sortable ?? [];
    }

    private function getDefaultSortBy(): string
    {
        return $this->defaultSortBy ?? 'created_at';
    }

    private function getDefaultSortDirection(): string
    {
        return $this->defaultSortDirection ?? 'asc';
    }
}
