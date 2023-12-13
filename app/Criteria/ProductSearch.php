<?php

namespace App\Criteria;

use Domain\ProductAttribute\Models\AttributeOption;
use Heseya\Searchable\Criteria\Criterion;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Collection as SupportCollection;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Stringable;

class ProductSearch extends Criterion
{
    public const BOOLEAN_SEARCH_OPERATORS = ['-', '~', '@', '<', '>', '(', ')', '"'];

    public function __construct(string $key, ?string $value = null)
    {
        parent::__construct($key, trim($value ?? ''));
    }

    public function query(Builder $query): Builder
    {
        return match (true) {
            Config::get('search.use_full_text_query') && Config::get('search.full_text_mode') === 'boolean' => $this->useBooleanSearch($query),
            Config::get('search.use_full_text_query') => $this->useNaturalSearch($query),
            default => $this->useDatabaseQuery($query),
        };
    }

    /**
     * @return Collection<array-key,AttributeOption>
     */
    protected function findAttributeOptions(): Collection
    {
        return AttributeOption::query()
            ->whereHas('attribute', fn (Builder $attributeQuery) => $attributeQuery->textSearchable()) // @phpstan-ignore-line
            ->where(
                function (Builder $subquery): void {
                    $subquery->where('attribute_options.name', 'LIKE', '%' . $this->value . '%')
                        ->orWhere('attribute_options.id', 'LIKE', '%' . $this->value . '%')
                        ->orWhere('attribute_options.value_date', 'LIKE', '%' . $this->value . '%');
                },
            )->get();
    }

    /**
     * @return Collection<array-key,AttributeOption>
     */
    protected function findAttributeOptionsFulltext(): Collection
    {
        $searchString = new Stringable($this->value);
        $words = $searchString->remove(self::BOOLEAN_SEARCH_OPERATORS)->replace('  ', ' ')->explode(' ');

        $contentSearch = '+' . $words->implode('* +') . '*';

        return AttributeOption::query()
            ->whereHas('attribute', fn (Builder $attributeQuery): Builder => $attributeQuery->textSearchable()) // @phpstan-ignore-line
            ->where(
                function (Builder $subquery) use ($contentSearch): void {
                    $subquery->whereFullText([
                        'attribute_options.name',
                    ], $contentSearch, ['mode' => 'boolean'])
                        ->orWhere('attribute_options.name', 'LIKE', '%' . $this->value . '%')
                        ->orWhere('attribute_options.id', 'LIKE', '%' . $this->value . '%')
                        ->orWhere('attribute_options.value_date', 'LIKE', '%' . $this->value . '%');
                },
            )->get();
    }

    protected function useBooleanSearch(Builder $query): Builder
    {
        $searchString = new Stringable($this->value);

        /** @var SupportCollection<array-key,string> $quotes */
        $quotes = $searchString->contains('"')
            ? $searchString->matchAll('|\".*?\"|')
            : collect();

        $words = $searchString->remove(self::BOOLEAN_SEARCH_OPERATORS)->replace('  ', ' ')->explode(' ');

        $naturalSearch = $quotes->isNotEmpty() ? str_replace('"', '', (string) $quotes->first()) : $words->implode(' ');
        $contentSearch = '+' . $words->implode('* +') . '*';
        $titleSearch = '"' . $naturalSearch . '"';

        $matchingOptions = $this->findAttributeOptionsFulltext();

        return $query
            ->selectRaw(
                '`products`.*,
                MATCH(`products`.`name`) AGAINST (? IN BOOLEAN MODE) as title_relevancy,
                MATCH(`products`.`name`) AGAINST (?) AS natural_title_relevancy,
                MATCH(`products`.`name`, `products`.`name`, `products`.`description_html`, `products`.`description_short`, `products`.`search_values`) AGAINST (? IN BOOLEAN MODE) as content_relevancy,
                MATCH(`products`.`name`, `products`.`name`, `products`.`description_html`, `products`.`description_short`, `products`.`search_values`) AGAINST (?) AS natural_content_relevancy',
                [
                    $titleSearch,
                    $naturalSearch,
                    $contentSearch,
                    $naturalSearch,
                ],
            )
            ->where(
                fn (Builder $subquery) => $subquery->where(
                    fn (Builder $subsubquery) => $subsubquery->whereFullText([
                        'products.name',
                        'products.description_html',
                        'products.description_short',
                        'products.search_values',
                    ], $contentSearch, ['mode' => 'boolean']),
                )->orWhere('products.id', 'LIKE', '%' . $this->value . '%')
                    ->orWhere('products.slug', 'LIKE', '%' . $this->value . '%')
                    ->orWhereHas('productAttributes', fn (Builder $productAttributesQuery) => $productAttributesQuery->whereHas('options', fn (Builder $optionsQuery) => $optionsQuery->whereIn('attribute_option_id', $matchingOptions->pluck('id')))),
            )
            ->orderBy('title_relevancy', 'desc')
            ->orderBy('natural_title_relevancy', 'desc')
            ->orderBy('natural_content_relevancy', 'desc')
            ->orderBy('content_relevancy', 'desc');
    }

    protected function useNaturalSearch(Builder $query): Builder
    {
        $matchingOptions = $this->findAttributeOptionsFulltext();

        return $query
            ->where(
                fn (Builder $subquery) => $subquery->where(
                    fn (Builder $subsubquery) => $subsubquery->whereFullText([
                        'products.name',
                        'products.description_html',
                        'products.description_short',
                        'products.search_values',
                    ], $this->value),
                )->orWhere('products.id', 'LIKE', '%' . $this->value . '%')
                    ->orWhere('products.slug', 'LIKE', '%' . $this->value . '%')
                    ->orWhereHas('productAttributes', fn (Builder $productAttributesQuery) => $productAttributesQuery->whereHas('options', fn (Builder $optionsQuery) => $optionsQuery->whereIn('attribute_option_id', $matchingOptions->pluck('id')))),
            );
    }

    protected function useDatabaseQuery(Builder $query): Builder
    {
        $matchingOptions = $this->findAttributeOptions();

        return $query->where(
            fn (Builder $subquery) => $subquery->where('products.id', 'LIKE', '%' . $this->value . '%')
                ->orWhere('products.slug', 'LIKE', '%' . $this->value . '%')
                ->orWhere('products.name', 'LIKE', '%' . $this->value . '%')
                ->orWhere('products.description_html', 'LIKE', '%' . $this->value . '%')
                ->orWhere('products.description_short', 'LIKE', '%' . $this->value . '%')
                ->orWhere('products.search_values', 'LIKE', '%' . $this->value . '%')
                ->orWhereHas('productAttributes', fn (Builder $productAttributesQuery) => $productAttributesQuery->whereHas('options', fn (Builder $optionsQuery) => $optionsQuery->whereIn('attribute_option_id', $matchingOptions->pluck('id')))),
        );
    }
}
