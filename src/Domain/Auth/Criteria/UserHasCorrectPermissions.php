<?php

declare(strict_types=1);

namespace Domain\Auth\Criteria;

use App\Models\App;
use App\Models\Model;
use App\Models\User;
use Heseya\Searchable\Criteria\Criterion;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

final class UserHasCorrectPermissions extends Criterion
{
    /**
     * @param Builder<Model> $query
     *
     * @return Builder<Model>
     */
    public function query(Builder $query): Builder
    {
        /** @var App|User|null $authUser */
        $authUser = Auth::user();

        $permissions = $authUser?->getAllPermissions()->map(fn ($perm) => $perm->getKey())->toArray() ?? [];

        if ($this->value === true) {
            $query->whereDoesntHave(
                'permissions',
                fn (Builder $subquery) => $subquery->whereNotIn('id', $permissions),
            );
        } elseif ($this->value === false) {
            $query->whereHas(
                'permissions',
                fn (Builder $subquery) => $subquery->whereNotIn('id', $permissions),
            );
        }

        return $query;
    }
}
