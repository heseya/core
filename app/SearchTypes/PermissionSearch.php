<?php

namespace App\SearchTypes;

use Heseya\Searchable\Searches\Search;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class PermissionSearch extends Search
{
    public function query(Builder $query): Builder
    {
        $permissions = Auth::user()->getAllPermissions()
            ->map(fn ($perm) => $perm->getKey())->toArray();

        if ($this->value === true) {
            $query->whereIn('id', $permissions);
        } elseif ($this->value === false) {
            $query->whereNotIn('id', $permissions);
        }

        return $query;
    }
}
