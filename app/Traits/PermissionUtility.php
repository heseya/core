<?php

namespace App\Traits;

use App\Models\PackageTemplate;
use Illuminate\Support\Facades\Gate;

trait PermissionUtility
{
    protected function allowsAbilityByModel(string $ability, $model): bool
    {
        return Gate::allows("{$this->getPermissionPrefix($model)}.${ability}");
    }

    protected function deniesAbilityByModel(string $ability, $model): bool
    {
        return !$this->allowsAbilityByModel($ability, $model);
    }

    /**
     * Allows to change returned prefix to desired model if is different to table name
     *
     * @param $model
     *
     * @return string
     * */
    protected function getPermissionPrefix($model): string
    {
        return match ($model::class) {
            PackageTemplate::class => 'packages',
            default => $model->getTable(),
        };
    }
}
