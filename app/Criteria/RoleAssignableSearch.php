<?php

namespace App\Criteria;

use App\Enums\RoleType;
use Heseya\Searchable\Criteria\Criterion;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class RoleAssignableSearch extends Criterion
{
    public function query(Builder $query): Builder
    {
        $permissions = Auth::user()?->getAllPermissions()
            ->map(fn ($perm) => $perm->getKey())->toArray();

        if ($this->value === true) {
            $query
                ->whereNotIn('type', [RoleType::UNAUTHENTICATED->value, RoleType::AUTHENTICATED->value])
                ->whereDoesntHave(
                    'permissions',
                    fn (Builder $sub) => $sub->whereNotIn('id', $permissions),
                );
        } elseif ($this->value === false) {
            $query
                ->whereIn('type', [RoleType::UNAUTHENTICATED->value, RoleType::AUTHENTICATED->value])
                ->orWhereHas(
                    'permissions',
                    fn (Builder $sub) => $sub->whereNotIn('id', $permissions),
                );
        }

        return $query;
    }
}
