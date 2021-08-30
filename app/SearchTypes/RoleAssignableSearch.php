<?php

namespace App\SearchTypes;

use Heseya\Searchable\Searches\Search;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class RoleAssignableSearch extends Search
{
    public function query(Builder $query): Builder
    {
        $permissions = Auth::user()->getAllPermissions()
            ->each(fn ($perm) => $perm->getKey())->toArray();

        if ($this->value === true) {
            $query->whereDoesntHave(
                'permissions',
                fn (Builder $query) => $query->whereNotIn('id', $permissions),
            );
        } else if ($this->value === false) {
            $query->whereHas(
                'permissions',
                fn (Builder $query) => $query->whereNotIn('id', $permissions),
            );
        }

        return $query;
    }
}
