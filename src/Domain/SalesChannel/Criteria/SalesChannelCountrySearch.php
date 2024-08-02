<?php

declare(strict_types=1);

namespace Domain\SalesChannel\Criteria;

use Domain\SalesChannel\Models\SalesChannel;
use Heseya\Searchable\Criteria\Criterion;
use Illuminate\Database\Eloquent\Builder;

final class SalesChannelCountrySearch extends Criterion
{
    /**
     * @param Builder<SalesChannel> $query
     *
     * @return Builder<SalesChannel>
     */
    public function query(Builder $query): Builder
    {
        return $query->whereHas('shippingMethods', function (Builder $query): void {
            $query->whereHas('countries', function (Builder $query): void {
                $query->where('code', '=', $this->value);
            });
        });
    }
}
