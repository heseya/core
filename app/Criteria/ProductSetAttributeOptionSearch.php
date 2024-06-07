<?php

namespace App\Criteria;

use Domain\ProductSet\ProductSet;
use Heseya\Searchable\Criteria\Criterion;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Gate;

class ProductSetAttributeOptionSearch extends Criterion
{
    public function query(Builder $query): Builder
    {
        $children = Gate::denies('product_sets.show_hidden') ? 'childrenPublic' : 'children';

        $setsIds = ProductSet::with([$children])
            ->where('slug', '=', $this->value)
            ->get()
            ->map(fn ($set) => $set->allChildrenIds($children))
            ->collapse();

        return $query->whereHas('productAttributes', function (Builder $query) use ($setsIds): void {
            $query->whereHas('product', function (Builder $query) use ($setsIds): void {
                if (Gate::denies('products.show_hidden')) {
                    $query->where('public', '=', true);
                }
                $query->whereHas('sets', function (Builder $query) use ($setsIds): void {
                    $query
                        ->whereIn('id', $setsIds)
                        ->orWhere('slug', '=', $this->value);
                });
            });
        });
    }
}
