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
                $query->where('is_blocklist', false);

                foreach ($this->value as $productId) {
                    $query->where(function (Builder $innerQuery) use ($productId): void {
                        $innerQuery->whereHas('products', function (Builder $query) use ($productId): void {
                            $query->where('products.id', $productId);
                        })
                            ->orWhereHas('productSets', function (Builder $query) use ($productId): void {
                                $query->whereHas('products', function (Builder $innerQuery) use ($productId): void {
                                    $innerQuery->where('products.id', $productId);
                                });
                            });
                    });
                }
            })
            ->orWhere(function (Builder $query): void {
                // Blocklist
                $query->where('is_blocklist', true)
                    ->whereDoesntHave('products', function (Builder $query): void {
                        $query->whereIn('products.id', $this->value);
                    })
                    ->whereDoesntHave('productSets', function (Builder $query): void {
                        $query->whereHas('products', function (Builder $innerQuery): void {
                            $innerQuery->whereIn('products.id', $this->value);
                        });
                    });
            });
    }
}
