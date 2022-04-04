<?php

namespace App\Services;

use App\Services\Contracts\SortServiceContract;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Validator;
use Laravel\Scout\Builder as ScoutBuilder;

class SortService implements SortServiceContract
{
    public function sortScout(ScoutBuilder $query, ?string $sortString): ScoutBuilder
    {
        return $this->sort($query, $sortString, $query->model->getSortable());
    }

    public function sort(Builder|ScoutBuilder $query, string $sortString, array $sortable): Builder|ScoutBuilder
    {
        $sort = explode(',', $sortString);

        foreach ($sort as $option) {
            $option = explode(':', $option);

            $field = $option[0];
            Validator::make(
                $option,
                [
                    '0' => ['required', 'in:' . implode(',', $sortable)],
                    '1' => ['in:asc,desc'],
                ],
                [
                    'required' => 'You must specify sort field.',
                    '0.in' => "You can't sort by ${field} field.",
                    '1.in' => "Only asc|desc sorting directions are allowed on field ${field}.",
                ]
            )->validate();

            $order = count($option) > 1 ? $option[1] : 'asc';
            $query->orderBy($field, $order);
        }

        return $query;
    }
}
