<?php

namespace App\Criteria;

use Heseya\Searchable\Criteria\Criterion;
use Illuminate\Database\Eloquent\Builder;

class OrganizationSearch extends Criterion
{
    public function query(Builder $query): Builder
    {
        return $query->where(function (Builder $query): void {
            $value = "%{$this->value}%";
            $query->where('client_id', 'LIKE', $value)
                ->orWhere('billing_email', 'LIKE', $value)
                ->orWhereHas('address', function (Builder $query) use ($value): void {
                    $query
                        ->where('company_name', 'LIKE', $value)
                        ->orWhere('name', 'LIKE', $value)
                        ->orWhere('addresses.address', 'LIKE', $value);
                });
        });
    }
}
