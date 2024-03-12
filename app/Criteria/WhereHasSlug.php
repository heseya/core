<?php

namespace App\Criteria;

use Domain\ProductSet\ProductSet;
use Heseya\Searchable\Criteria\Criterion;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Gate;

class WhereHasSlug extends Criterion
{
    public function query(Builder $query): Builder
    {
        $children = Gate::denies('product_sets.show_hidden') ? 'childrenPublic' : 'children';

        $setsIds = ProductSet::with([$children])
            ->whereIn('slug', $this->value)
            ->get()
            ->map(fn ($set) => $set->allChildrenIds($children))
            ->collapse();

        return $query->whereHas($this->key, function (Builder $query) use ($setsIds) {
            $query->whereIn('id', $setsIds);
            if (is_array($this->value)) {
                return $query
                    ->orWhereIn('slug', $this->value);
            }

            return $query->orWhere('slug', $this->value);
        });
    }
}
