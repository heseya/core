<?php

namespace App\Services;

use App\Enums\ExceptionsEnums\Exceptions;
use App\Exceptions\ClientException;
use App\Models\Contracts\SortableContract;
use App\Rules\WhereIn;
use App\Services\Contracts\SortServiceContract;
use Exception;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Laravel\Scout\Builder as ScoutBuilder;

class SortService implements SortServiceContract
{
    /**
     * @throws Exception
     */
    public function sortScout(ScoutBuilder $query, ?string $sortString): Builder|ScoutBuilder
    {
        if ($query->model instanceof SortableContract && $sortString !== null) {
            return $this->sort($query, $sortString, $query->model->getSortable());
        }
        throw new ClientException(Exceptions::CLIENT_MODEL_NOT_SORTABLE);
    }

    public function sort(Builder|ScoutBuilder $query, string $sortString, array $sortable): Builder|ScoutBuilder
    {
        $sort = explode(',', $sortString);

        foreach ($sort as $option) {
            $option = explode(':', $option);
            $this->validate($option, $sortable);
            $this->addOrder(
                $query,
                $option[0],
                count($option) > 1 ? $option[1] : 'asc',
            );
        }

        return $query;
    }

    private function validate(array $field, array $sortable): void
    {
        Validator::make(
            $field,
            [
                '0' => ['required', new WhereIn($sortable)],
                '1' => ['in:asc,desc'],
            ],
            [
                'required' => 'You must specify sort field.',
                '1.in' => "Only asc|desc sorting directions are allowed on field {$field[0]}.",
            ],
        )->validate();
    }

    // TODO: refactor this
    private function addOrder(Builder|ScoutBuilder $query, string $field, string $order): void
    {
        if ($query instanceof Builder) {
            if (Str::startsWith($field, 'set.')) {
                $this->addSetOrder($query, $field, $order);

                return;
            } elseif (Str::startsWith($field, 'attribute.')) {
                $this->addAttributeOrder($query, $field, $order);

                return;
            }
        }

        $query->orderBy($field, $order);
    }

    private function addSetOrder(Builder $query, string $field, string $order): void
    {
        $query->leftJoin('product_set_product', function (JoinClause $join) use ($field): void {
            $join->on('product_set_product.product_id', 'products.id')
                ->join('product_sets', function (JoinClause $join) use ($field): void {
                    $join->on('product_sets.id', 'product_set_product.product_set_id')
                        ->where('product_sets.slug', Str::after($field, 'set.'));
                });
        })
            ->addSelect('products.*')
            ->addSelect('product_set_product.order AS set_order')
            ->orderBy('set_order', $order);
    }

    private function addAttributeOrder(Builder $query, string $field, string $order): void
    {
        $query->leftJoin('product_attribute', function (JoinClause $join) use ($field): void {
            $join
                ->on('product_attribute.product_id', 'products.id')
                ->where('product_attribute.attribute_id', Str::after($field, 'attribute.'))
                ->join('product_attribute_attribute_option', function (JoinClause $join): void {
                    $join
                        ->on('product_attribute_attribute_option.product_attribute_id', 'product_attribute.id')
                        ->join('attribute_options', function (JoinClause $join): void {
                            $join->on('product_attribute_attribute_option.attribute_option_id', 'attribute_options.id');
                        });
                });
        })
            ->addSelect('products.*')
            ->addSelect('attribute_options.name AS attribute_order')
            ->orderBy('attribute_order', $order);
    }
}
