<?php

namespace App\Services;

use App\Rules\WhereIn;
use App\Services\Contracts\SortServiceContract;
use App\SortColumnTypes\SortableColumn;
use App\SortColumnTypes\TranslatedColumn;
use Domain\ProductAttribute\Models\AttributeOption;
use Domain\ProductAttribute\Repositories\AttributeRepository;
use Domain\ProductSet\ProductSet;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

readonly class SortService implements SortServiceContract
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

            if (isset($sortable[$option[0]]) && is_a($sortable[$option[0]], SortableColumn::class, true)) {
                $this->addOrder(
                    $query,
                    $sortable[$option[0]]::getColumnName($option[0]),
                    match (count($option)) {
                        3 => $option[2],
                        2 => $option[1],
                        default => 'asc',
                    },
                    $sortable[$option[0]]::useRawOrderBy(),
                );
            } else {
                $this->addOrder(
                    $query,
                    $option[0],
                    match (count($option)) {
                        3 => $option[2],
                        2 => $option[1],
                        default => 'asc',
                    },
                );
            }
        }

        return $query;
    }

    private function getSortableColumnNames(array $sortable): array
    {
        return Arr::map(
            $sortable,
            fn ($value, $key) => is_a($value, SortableColumn::class, true) ? $value::getColumnName($key) : $value,
        );
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

    private function addOrder(Builder $query, string $field, string $order, bool $raw = false): void
    {
        if (Str::startsWith($field, 'set.')) {
            $this->addSetOrder($query, $field, $order);

            return;
        } elseif (Str::startsWith($field, 'attribute.')) {
            $this->addAttributeOrder($query, $field, $order);

            return;
        }

        if ($raw) {
            $query->orderByRaw($field . ' ' . $order);
        } else {
            $query->orderBy($field, $order);
        }
    }

    private function addSetOrder(Builder $query, string $field, string $order): void
    {
        /** @var ProductSet $set */
        $set = ProductSet::query()->where('slug', '=', Str::after($field, 'set.'))->select('id')->first();
        $searchedProductSetsIds = $set->allChildrenIds('children')->push($set->getKey());

        $query->leftJoin('product_set_product_descendant', function (JoinClause $join) use ($searchedProductSetsIds): void {
            $join->on('product_set_product_descendant.product_id', 'products.id')
                ->whereIn('product_set_product_descendant.product_set_id', $searchedProductSetsIds)
                ->leftJoin('product_sets', function (JoinClause $join): void {
                    $join->on('product_set_product_descendant.product_set_id', '=', 'product_sets.id');
                });
        })
            ->addSelect('products.*')
            ->selectRaw('MIN(product_set_product_descendant.order) AS product_order')
            ->selectRaw('MAX(product_set_product_descendant.product_set_id = ?) AS is_main_set', [$set->getKey()])
            ->selectRaw('MIN(product_sets.order) as set_order')
            ->selectRaw('MIN(IF(product_set_product_descendant.product_set_id = ?, product_set_product_descendant.`order`, NULL)) AS main_set_order', [$set->getKey()])
            ->groupBy('products.id')
            ->orderBy('is_main_set', 'desc')
            ->orderByRaw('main_set_order IS NULL ' . $order)
            ->orderBy('main_set_order', $order)
            ->orderBy('set_order', $order)
            ->orderBy('product_order', $order);
    }

    private function addAttributeOrder(Builder $query, string $field, string $order): void
    {
        $attribute = $this->attributeRepository->getOne(
            Str::after($field, 'attribute.'),
        );

        $sortField = $attribute->type->getOptionFieldByType();

        $collate = false;
        if (
            array_key_exists($sortField, (new AttributeOption())->getSortable())
            && (new AttributeOption())->getSortable()[$sortField] === TranslatedColumn::class
        ) {
            $collate = true;
            $sortField = TranslatedColumn::getColumnName($sortField);
        }

        $query->leftJoin('product_attribute', function (JoinClause $join) use ($attribute): void {
            $join
                ->on('product_attribute.product_id', 'products.id')
                ->where('product_attribute.attribute_id', $attribute->getKey())
                ->join('product_attribute_attribute_option', function (JoinClause $join): void {
                    $join
                        ->on('product_attribute_attribute_option.product_attribute_id', 'product_attribute.pivot_id')
                        ->join('attribute_options', function (JoinClause $join): void {
                            $join->on(
                                'product_attribute_attribute_option.attribute_option_id',
                                'attribute_options.id',
                            );
                        });
                });
        })->addSelect('products.*')
            ->addSelect("attribute_options.{$sortField} AS attribute_order")
            ->when(
                $collate,
                fn (Builder $subquery) => $subquery->orderByRaw('(attribute_order COLLATE utf8mb4_0900_ai_ci) ' . $order),
                fn (Builder $subquery) => $subquery->orderBy('attribute_order', $order),
            );
    }
}
