<?php

namespace App\Services;

use App\Enums\ExceptionsEnums\Exceptions;
use App\Exceptions\ClientException;
use App\Models\Contracts\SortableContract;
use App\Rules\WhereIn;
use App\Services\Contracts\SortServiceContract;
use Exception;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Validator;
use Laravel\Scout\Builder as ScoutBuilder;

class SortService implements SortServiceContract
{
    /**
     * @throws Exception
     */
    public function sortScout(ScoutBuilder $query, ?string $sortString): ScoutBuilder
    {
        if ($query->model instanceof SortableContract) {
            return $this->sort($query, $sortString, $query->model->getSortable());
        }
        throw new ClientException(Exceptions::CLIENT_MODEL_NOT_SORTABLE);
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
                    '0' => ['required', new WhereIn($sortable)],
                    '1' => ['in:asc,desc'],
                ],
                [
                    'required' => 'You must specify sort field.',
                    '1.in' => "Only asc|desc sorting directions are allowed on field ${field}.",
                ]
            )->validate();

            $order = count($option) > 1 ? $option[1] : 'asc';
            $query->orderBy($field, $order);
        }

        return $query;
    }
}
