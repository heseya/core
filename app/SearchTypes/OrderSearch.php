<?php

namespace App\SearchTypes;

use Heseya\Searchable\Searches\Search;
use Illuminate\Database\Eloquent\Builder;

class OrderSearch extends Search
{
    public function query(Builder $query): Builder
    {
        return $query->where(function (Builder $query) {
            $query->where('id', 'LIKE', '%' . $this->value . '%')
                ->orWhere('code', 'LIKE', '%' . $this->value . '%')
                ->orWhere('email', 'LIKE', '%' . $this->value . '%')
                ->orWhere('shipping_number', 'LIKE', '%' . $this->value . '%')
                ->orWhereHas('status', function (Builder $query) {
                    $query
                        ->where('name', 'LIKE', '%' . $this->value . '%')
                        ->orWhere('description', 'LIKE', '%' . $this->value . '%');
                })
                ->orWhereHas('shippingMethod', function (Builder $query) {
                    $query
                        ->where('id', 'LIKE', '%' . $this->value . '%')
                        ->orWhere('name', 'LIKE', '%' . $this->value . '%');
                })
                ->orWhereHas('deliveryAddress', function (Builder $query) {
                    $query
                        ->where('id', 'LIKE', '%' . $this->value . '%')
                        ->orWhere('name', 'LIKE', '%' . $this->value . '%')
                        ->orWhere('phone', 'LIKE', '%' . $this->value . '%')
                        ->orWhere('address', 'LIKE', '%' . $this->value . '%')
                        ->orWhere('vat', 'LIKE', '%' . $this->value . '%')
                        ->orWhere('zip', 'LIKE', '%' . $this->value . '%')
                        ->orWhere('city', 'LIKE', '%' . $this->value . '%')
                        ->orWhere('country', 'LIKE', '%' . $this->value . '%');
                })
                ->orWhereHas('invoiceAddress', function (Builder $query) {
                    $query
                        ->where('id', 'LIKE', '%' . $this->value . '%')
                        ->orWhere('name', 'LIKE', '%' . $this->value . '%')
                        ->orWhere('phone', 'LIKE', '%' . $this->value . '%')
                        ->orWhere('address', 'LIKE', '%' . $this->value . '%')
                        ->orWhere('vat', 'LIKE', '%' . $this->value . '%')
                        ->orWhere('zip', 'LIKE', '%' . $this->value . '%')
                        ->orWhere('city', 'LIKE', '%' . $this->value . '%')
                        ->orWhere('country', 'LIKE', '%' . $this->value . '%');
                });
        });
    }
}
