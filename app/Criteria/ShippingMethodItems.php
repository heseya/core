<?php

namespace App\Criteria;

use Heseya\Searchable\Criteria\Criterion;
use Illuminate\Database\Eloquent\Builder;

class ShippingMethodItems extends Criterion
{
    public function query(Builder $query): Builder
    {
        if (empty($this->value)) {
            return $query;
        }

        return $query
            ->where(function (Builder $query): void {
                // Allowlist
                $query->where('is_block_list_products', false);

                foreach ($this->value as $productId) {
                    $query->where(function (Builder $innerQuery) use ($productId): void {
                        $innerQuery->whereHas('products', function (Builder $subquery) use ($productId): void {
                            $subquery->where('products.id', $productId);
                        })
                            ->orWhereHas('productSets', function (Builder $innerQuery) use ($productId): void {
                                $innerQuery->whereHas('products', function (Builder $subquery) use ($productId): void {
                                    $subquery->where('products.id', $productId);
                                });
                            });
                    });
                }
            })
            ->orWhere(function (Builder $query): void {
                // Blocklist
                $query->where('is_block_list_products', true)
                    ->whereDoesntHave('products', function (Builder $innerQuery): void {
                        $innerQuery->whereIn('products.id', $this->value);
                    })
                    ->whereDoesntHave('productSets', function (Builder $innerQuery): void {
                        $innerQuery->whereHas('products', function (Builder $subquery): void {
                            $subquery->whereIn('products.id', $this->value);
                        });
                    });
            });
    }
}
