<?php

namespace App\SearchTypes;

use App\Enums\RoleType;
use Heseya\Searchable\Searches\Search;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class RoleAssignableSearch extends Search
{
    public function query(Builder $query): Builder
    {
        $permissions = Auth::user()->getAllPermissions()
            ->map(fn ($perm) => $perm->getKey())->toArray();

        if ($this->value === true) {
            $query
                ->where('type', '!=', RoleType::UNAUTHENTICATED)
                ->where('type', '!=', RoleType::AUTHENTICATED)
                ->whereDoesntHave(
                    'permissions',
                    fn (Builder $sub) => $sub->whereNotIn('id', $permissions),
                );
        } elseif ($this->value === false) {
            $query
                ->where('type', '=', RoleType::UNAUTHENTICATED)
                ->orWhere('type', '=', RoleType::AUTHENTICATED)
                ->orWhereHas(
                    'permissions',
                    fn (Builder $sub) => $sub->whereNotIn('id', $permissions),
                );
        }

        return $query;
    }
}
