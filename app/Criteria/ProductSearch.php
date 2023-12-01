<?php

namespace App\Criteria;

use Domain\ProductAttribute\Models\AttributeOption;
use Heseya\Searchable\Criteria\Criterion;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Config;

class ProductSearch extends Criterion
{
    public function query(Builder $query): Builder
    {
        if (Config::get('search.full_text_mode') === 'boolean') {
            $fulltextString = '+' . implode('* +', explode(' ', $this->value)) . '*';
        } else {
            $fulltextString = $this->value;
        }

        $matchingOptionsQuery = AttributeOption::query()
            ->whereHas('attribute', fn (Builder $attributeQuery) => $attributeQuery->textSearchable()) // @phpstan-ignore-line
            ->where(
                fn (Builder $subquery) => (match (true) {
                    Config::get('search.use_full_text_query') => $subquery->whereFullText(['attribute_options.name'], $fulltextString, ['mode' => Config::get('search.full_text_mode')]),
                    default => $subquery->where('attribute_options.name', 'LIKE', '%' . $this->value . '%'),
                })->orWhere('attribute_options.id', 'LIKE', '%' . $this->value . '%')
                    ->orWhere('attribute_options.value_date', 'LIKE', '%' . $this->value . '%'),
            );

        $matchingOptions = $matchingOptionsQuery->get();

        return match (true) {
            Config::get('search.use_full_text_query') => $query->where(
                fn (Builder $subquery) => $subquery->whereFullText([
                    'products.name',
                    'products.description_html',
                    'products.description_short',
                    'products.search_values',
                ], $fulltextString, ['mode' => Config::get('search.full_text_mode')])
                    ->orWhere('products.id', 'LIKE', '%' . $this->value . '%')
                    ->orWhere('products.slug', 'LIKE', '%' . $this->value . '%')
                    ->orWhereHas('productAttributes', fn (Builder $productAttributesQuery) => $productAttributesQuery->whereHas('options', fn (Builder $optionsQuery) => $optionsQuery->whereIn('attribute_option_id', $matchingOptions->pluck('id')))),
            ),
            default => $query->where(
                fn (Builder $subquery) => $subquery->where('products.id', 'LIKE', '%' . $this->value . '%')
                    ->orWhere('products.slug', 'LIKE', '%' . $this->value . '%')
                    ->orWhere('products.name', 'LIKE', '%' . $this->value . '%')
                    ->orWhere('products.description_html', 'LIKE', '%' . $this->value . '%')
                    ->orWhere('products.description_short', 'LIKE', '%' . $this->value . '%')
                    ->orWhere('products.search_values', 'LIKE', '%' . $this->value . '%')
                    ->orWhereHas('productAttributes', fn (Builder $productAttributesQuery) => $productAttributesQuery->whereHas('options', fn (Builder $optionsQuery) => $optionsQuery->whereIn('attribute_option_id', $matchingOptions->pluck('id')))),
            ),
        };
    }
}
