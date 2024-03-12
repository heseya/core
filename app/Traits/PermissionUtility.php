<?php

namespace App\Traits;

use App\Models\Discount;
use Domain\ProductAttribute\Models\AttributeOption;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Request;

trait PermissionUtility
{
    protected function allowsAbilityByModel(string $ability, Model $model): bool
    {
        return Gate::allows("{$this->getPermissionPrefix($model)}.{$ability}");
    }

    protected function deniesAbilityByModel(string $ability, Model $model): bool
    {
        return !$this->allowsAbilityByModel($ability, $model);
    }

    /**
     * Allows to change returned prefix to desired model if is different to table name.
     */
    protected function getPermissionPrefix(Model $model): string
    {
        $segments = Request::segments();

        return match ($model::class) {
            AttributeOption::class => 'attributes',
            Discount::class => $segments[0],
            default => $model->getTable(),
        };
    }
}
