<?php

namespace App\Services;

use App\Rules\WhereIn;
use App\Services\Contracts\SortServiceContract;
use App\SortColumnTypes\SortableColumn;
use App\SortColumnTypes\TranslatedColumn;
use Domain\ProductAttribute\Repositories\AttributeRepository;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class SortService implements SortServiceContract
{
    public function __construct(private AttributeRepository $attributeRepository) {}

    /**
     * @throws ValidationException
     */
    public function sort(Builder $query, string $sortString, array $sortable): Builder
    {
        $sort = explode(',', $sortString);

        foreach ($sort as $option) {
            $option = explode(':', $option);
            $this->validate($option, $sortable);

            if (isset($sortable[$option[0]]) && $sortable[$option[0]] === TranslatedColumn::class) {
                $option[0] = TranslatedColumn::getColumnName($option[0]);
            }

            if (count($option) === 3) {
                $this->addOrder(
                    $query,
                    $option[0],
                    $option[2],
                );
            } else {
                $this->addOrder(
                    $query,
                    $option[0],
                    count($option) > 1 ? $option[1] : 'asc',
                );
            }
        }

        return $query;
    }

    private function getSortableColumnNames(array $sortable): array
    {
        return Arr::map($sortable, fn ($value, $key) => is_a($value, SortableColumn::class, true) ? $value::getColumnName($key) : $value);
    }

    private function getSortableColumnSettingsValidation(string $key, array $sortable): array
    {
        if (array_key_exists($key, $sortable) && is_a($sortable[$key], SortableColumn::class, true)) {
            return $sortable[$key]::getValidationRules($key);
        }

        return [];
    }

    /**
     * @throws ValidationException
     */
    private function validate(array $field, array $sortable): void
    {
        if (count($field) === 3) {
            [$sort_by, $sort_settings, $sort_direction] = $field;
        } elseif (count($field) == 2) {
            $sort_settings = null;
            [$sort_by, $sort_direction] = $field;
        } else {
            $sort_settings = null;
            $sort_direction = 'asc';
            [$sort_by] = $field;
        }
        Validator::make(
            [
                'sort_by' => $sort_by,
                'sort_settings' => $sort_settings,
                'sort_direction' => $sort_direction,
            ],
            [
                'sort_by' => ['required', new WhereIn($this->getSortableColumnNames($sortable))],
                'sort_settings' => $this->getSortableColumnSettingsValidation($sort_by, $sortable),
                'sort_direction' => ['in:asc,desc'],
            ],
            [
                'sort_by.required' => 'You must specify sort field.',
                'sort_direction.in' => "Only asc|desc sorting directions are allowed on field {$field[0]}.",
            ],
        )->validate();
    }

    // TODO: refactor this
    private function addOrder(Builder $query, string $field, string $order): void
    {
        if (Str::startsWith($field, 'set.')) {
            $this->addSetOrder($query, $field, $order);

            return;
        } elseif (Str::startsWith($field, 'attribute.')) {
            $this->addAttributeOrder($query, $field, $order);

            return;
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
        $attribute = $this->attributeRepository->getOne(Str::after($field, 'attribute.'));
        $sortField = $attribute->type->getOptionFieldByType();

        $query->leftJoin('product_attribute', function (JoinClause $join) use ($attribute): void {
            $join
                ->on('product_attribute.product_id', 'products.id')
                ->where('product_attribute.attribute_id', $attribute->getKey())
                ->join('product_attribute_attribute_option', function (JoinClause $join): void {
                    $join
                        ->on('product_attribute_attribute_option.product_attribute_id', 'product_attribute.id')
                        ->join('attribute_options', function (JoinClause $join): void {
                            $join->on('product_attribute_attribute_option.attribute_option_id', 'attribute_options.id');
                        });
                });
        })
            ->addSelect('products.*')
            ->addSelect("attribute_options.{$sortField} AS attribute_order")
            ->orderBy('attribute_order', $order);
    }
}
