<?php

declare(strict_types=1);

namespace Domain\Manufacturer\Criteria;

use Domain\Manufacturer\Models\Manufacturer;
use Heseya\Searchable\Criteria\Criterion;
use Illuminate\Database\Eloquent\Builder;

final class ManufacturerSearch extends Criterion
{
    /**
     * @param Builder<Manufacturer> $query
     *
     * @return Builder<Manufacturer>
     */
    public function query(Builder $query): Builder
    {
        return $query->where(
            fn (Builder $query) => $query
                ->where('name', 'LIKE', '%' . $this->value . '%')
                ->orWhere('first_name', 'LIKE', '%' . $this->value . '%')
                ->orWhere('last_name', 'LIKE', '%' . $this->value . '%')
                ->orWhereHas(
                    'address',
                    fn (Builder $query) => $query
                        ->orWhere('name', 'LIKE', '%' . $this->value . '%')
                        ->orWhere('phone', 'LIKE', '%' . $this->value . '%')
                        ->orWhere('address', 'LIKE', '%' . $this->value . '%')
                        ->orWhere('vat', 'LIKE', '%' . $this->value . '%')
                        ->orWhere('zip', 'LIKE', '%' . $this->value . '%')
                        ->orWhere('city', 'LIKE', '%' . $this->value . '%')
                        ->orWhere('country', 'LIKE', '%' . $this->value . '%'),
                ),
        );
    }
}
