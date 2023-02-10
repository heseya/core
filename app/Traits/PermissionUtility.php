<?php

namespace App\Traits;

use App\Models\AttributeOption;
use App\Models\Discount;
use App\Models\PackageTemplate;
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
     * Allows to change returned prefix to desired model if is different to table name
     *
     * @param Model $model
     *
     * @return string
     * */
    protected function getPermissionPrefix(Model $model): string
    {
        $segments = Request::segments();
        return match ($model::class) {
            AttributeOption::class => 'attributes',
            PackageTemplate::class => 'packages',
            Discount::class => $segments[0],
            default => $model->getTable(),
        };
    }
}
