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
    public const BOOLEAN_SEARCH_OPERATORS = ['+', '-', '~', '@', '<', '>', '(', ')', '"'];

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
        $words = (new Stringable($this->value))->remove(self::BOOLEAN_SEARCH_OPERATORS)->trim()->replaceMatches('/\s+/', ' ')->explode(' ');

        $contentSearch = $words->map(fn (string $word) => match (mb_strlen($word)) {
            0 => null,
            1, 2 => $word,
            default => '+' . $word . '*',
        })->filter()->implode(' ');

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

        $words = $searchString->remove(self::BOOLEAN_SEARCH_OPERATORS)->trim()->replaceMatches('/\s+/', ' ')->explode(' ');

        $naturalSearch = $quotes->isNotEmpty()
            ? (new Stringable((string) $quotes->first()))->remove('"')->trim()->replaceMatches('/\s+/', ' ')
            : $words->implode(' ');
        $titleSearch = '"' . $naturalSearch . '"';
        $contentSearch = $words->map(fn (string $word) => match (mb_strlen($word)) {
            0 => null,
            1, 2 => $word,
            default => '+' . $word . '*',
        })->filter()->implode(' ');

        $matchingOptions = $this->findAttributeOptionsFulltext();

        return $query->when(
            Config::get('search.search_in_descriptions'),
            fn (Builder $subquery) => $subquery->selectRaw(
                '`products`.*,
                MATCH(`products`.`name`) AGAINST (? IN BOOLEAN MODE) as title_relevancy,
                MATCH(`products`.`name`) AGAINST (?) AS title_natural_relevancy,
                MATCH(`products`.`name`) AGAINST (? IN BOOLEAN MODE) as title_words_relevancy,
                MATCH(`products`.`name`, `products`.`name`, `products`.`description_html`, `products`.`description_short`, `products`.`search_values`) AGAINST (?) AS content_natural_relevancy,
                MATCH(`products`.`name`, `products`.`name`, `products`.`description_html`, `products`.`description_short`, `products`.`search_values`) AGAINST (? IN BOOLEAN MODE) as content_relevancy',
                [
                    $titleSearch,
                    $naturalSearch,
                    $contentSearch,
                    $naturalSearch,
                    $contentSearch,
                ],
            ),
            fn (Builder $subquery) => $subquery->selectRaw(
                '`products`.*,
                    MATCH(`products`.`name`) AGAINST (? IN BOOLEAN MODE) as title_relevancy,
                    MATCH(`products`.`name`) AGAINST (?) AS title_natural_relevancy,
                    MATCH(`products`.`name`) AGAINST (? IN BOOLEAN MODE) as title_words_relevancy',
                [
                    $titleSearch,
                    $naturalSearch,
                    $contentSearch,
                ],
            ),
        )->where(
            fn (Builder $subquery) => $subquery->when(
                Config::get('search.search_in_descriptions'),
                fn (Builder $subsubquery) => $subsubquery->whereFullText(['products.name', 'products.description_html', 'products.description_short', 'products.search_values'], $contentSearch, ['mode' => 'boolean']),
                fn (Builder $subsubquery) => $subsubquery->whereFullText(['products.name'], $contentSearch, ['mode' => 'boolean']),
            )->orWhereFullText(['products.name'], $naturalSearch)
                ->orWhere('products.name', 'LIKE', $this->value . '%')
                ->orWhere('products.id', 'LIKE', '%' . $this->value . '%')
                ->orWhere('products.slug', 'LIKE', '%' . $this->value . '%')
                ->orWhereHas('productAttributes', fn (Builder $productAttributesQuery) => $productAttributesQuery->whereHas('options', fn (Builder $optionsQuery) => $optionsQuery->whereIn('attribute_option_id', $matchingOptions->pluck('id')))),
        )->orderBy('title_relevancy', 'desc')
            ->orderBy('title_natural_relevancy', 'desc')
            ->orderBy('title_words_relevancy', 'desc')
            ->when(
                Config::get('search.search_in_descriptions'),
                fn (Builder $subquery) => $subquery->orderBy('content_natural_relevancy', 'desc')
                    ->orderBy('content_relevancy', 'desc'),
            );
    }

    protected function useNaturalSearch(Builder $query): Builder
    {
        $matchingOptions = $this->findAttributeOptionsFulltext();

        return $query->where(
            fn (Builder $subquery) => $subquery->when(
                Config::get('search.search_in_descriptions'),
                fn (Builder $subsubquery) => $subsubquery->whereFullText(['products.name', 'products.description_html', 'products.description_short', 'products.search_values'], $this->value),
                fn (Builder $subsubquery) => $subsubquery->whereFullText(['products.name'], $this->value),
            )
                ->orWhere('products.name', 'LIKE', $this->value . '%')
                ->orWhere('products.id', 'LIKE', '%' . $this->value . '%')
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
                ->when(
                    Config::get('search.search_in_descriptions'),
                    fn (Builder $subsubquery) => $subsubquery->orWhere('products.description_html', 'LIKE', '%' . $this->value . '%')
                        ->orWhere('products.description_short', 'LIKE', '%' . $this->value . '%')
                        ->orWhere('products.search_values', 'LIKE', '%' . $this->value . '%'),
                )
                ->orWhereHas('productAttributes', fn (Builder $productAttributesQuery) => $productAttributesQuery->whereHas('options', fn (Builder $optionsQuery) => $optionsQuery->whereIn('attribute_option_id', $matchingOptions->pluck('id')))),
        );
    }
}
