<?php

namespace App\Criteria;

use Closure;
use Domain\ProductAttribute\Models\AttributeOption;
use Heseya\Searchable\Criteria\Criterion;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Collection as SupportCollection;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Str;
use Illuminate\Support\Stringable;

class ProductSearch extends Criterion
{
    public const BOOLEAN_SEARCH_OPERATORS = ['+', '-', '~', '@', '<', '>', '(', ')', '"'];

    protected Stringable $searchString;

    /** @var SupportCollection<array-key,string> */
    protected SupportCollection $quotes;
    /** @var SupportCollection<array-key,string> */
    protected SupportCollection $words;
    /** @var SupportCollection<array-key,string> */
    protected SupportCollection $wordsBoolean;

    protected string $naturalSearch;
    protected string $contentSearch;
    protected string $titleSearch;

    public function __construct(string $key, ?string $value = null)
    {
        $this->searchString = Str::of($value ?? '')->replaceMatches('/\s+/', ' ')->trim();

        parent::__construct($key, (string) $this->searchString);

        $this->quotes = $this->searchString->contains('"')
            ? $this->searchString->matchAll('|\".*?\"|')
            : collect();

        $this->words = $this->searchString->remove('"')->replace('%', '\%')->explode(' ');
        $this->wordsBoolean = $this->searchString->remove(self::BOOLEAN_SEARCH_OPERATORS)->explode(' ');

        $this->naturalSearch = $this->quotes->isNotEmpty()
            ? Str::of((string) $this->quotes->first())->remove('"')->trim()->replaceMatches('/\s+/', ' ')
            : $this->wordsBoolean->implode(' ');

        $this->titleSearch = '"' . $this->naturalSearch . '"';

        $this->contentSearch = $this->wordsBoolean->map(fn (string $word) => match (mb_strlen($word)) {
            0 => null,
            1, 2 => $word,
            default => '+' . $word . '*',
        })->filter()->implode(' ');
    }

    public function query(Builder $query): Builder
    {
        return match (true) {
            Config::get('search.use_full_text_query') && Config::get('search.full_text_mode') === 'boolean' => $this->useBooleanSearch($query),
            Config::get('search.use_full_text_query') => $this->useNaturalSearch($query),
            Config::get('search.use_full_text_relevancy') => $this->useDatabaseQueryWithRelevancy($query),
            default => $this->useDatabaseQuery($query),
        };
    }

    /**
     * @return Collection<array-key,AttributeOption>
     */
    protected function findAttributeOptions(): Collection
    {
        return AttributeOption::query()
            ->where(function (Builder $matchAllQuery): void {
                $matchAllQuery->whereHas('attribute', fn (Builder $attributeQuery): Builder => $attributeQuery->textSearchable()->matchAll()) // @phpstan-ignore-line\
                    ->where(
                        function (Builder $subquery): void {
                            $subquery->where($this->likeAllWords('attribute_options.searchable_name'))
                                ->orWhere('attribute_options.id', 'LIKE', $this->value . '%')
                                ->orWhere('attribute_options.value_date', 'LIKE', $this->value . '%');
                        },
                    );
            })
            ->orWhere(function (Builder $matchAnyQuery): void {
                $matchAnyQuery->whereHas('attribute', fn (Builder $attributeQuery): Builder => $attributeQuery->textSearchable()->matchAny()) // @phpstan-ignore-line
                    ->where(
                        function (Builder $subquery): void {
                            $subquery->where($this->likeAnyWord('attribute_options.searchable_name'))
                                ->orWhere($this->likeAnyWord('attribute_options.id'))
                                ->orWhere($this->likeAnyWord('attribute_options.value_date'));
                        },
                    );
            })->get();
    }

    /**
     * @return Collection<array-key,AttributeOption>
     */
    protected function findAttributeOptionsFulltext(): Collection
    {
        return AttributeOption::query()
            ->where(function (Builder $matchAllQuery): void {
                $matchAllQuery->whereHas('attribute', fn (Builder $attributeQuery): Builder => $attributeQuery->textSearchable()->matchAll()) // @phpstan-ignore-line\
                    ->where(
                        function (Builder $subquery): void {
                            $subquery->whereFullText([
                                'attribute_options.searchable_name',
                            ], $this->contentSearch, ['mode' => 'boolean'])
                                ->orWhere('attribute_options.searchable_name', 'LIKE', '%' . $this->value . '%')
                                ->orWhere('attribute_options.id', 'LIKE', $this->value . '%')
                                ->orWhere('attribute_options.value_date', 'LIKE', $this->value . '%');
                        },
                    );
            })
            ->orWhere(function (Builder $matchAnyQuery): void {
                $matchAnyQuery->whereHas('attribute', fn (Builder $attributeQuery): Builder => $attributeQuery->textSearchable()->matchAny()) // @phpstan-ignore-line
                    ->where(
                        function (Builder $subquery): void {
                            $subquery->where($this->likeAnyWord('attribute_options.searchable_name'))
                                ->orWhere($this->likeAnyWord('attribute_options.id'))
                                ->orWhere($this->likeAnyWord('attribute_options.value_date'));
                        },
                    );
            })->get();
    }

    protected function useBooleanSearch(Builder $query): Builder
    {
        $matchingOptions = $this->findAttributeOptionsFulltext();

        return $this->relevancyQuery($query)
            ->where(
                fn (Builder $subquery) => $subquery->when(
                    Config::get('search.search_in_descriptions'),
                    fn (Builder $subsubquery) => $subsubquery->whereFullText(
                        ['products.searchable_name', 'products.description_html', 'products.description_short', 'products.search_values'],
                        $this->contentSearch,
                        ['mode' => 'boolean'],
                    ),
                    fn (Builder $subsubquery) => $subsubquery->whereFullText(
                        ['products.searchable_name'],
                        $this->contentSearch,
                        ['mode' => 'boolean'],
                    ),
                )
                    ->orWhereFullText(['products.searchable_name'], $this->naturalSearch)
                    ->when($this->searchString->contains(self::BOOLEAN_SEARCH_OPERATORS), fn (Builder $subsubquery) => $subsubquery->orWhere('products.searchable_name', 'LIKE', '%' . $this->value . '%'))
                    ->orWhere('products.id', 'LIKE', $this->value . '%')
                    ->orWhere('products.slug', 'LIKE', Str::slug($this->value) . '%')
                    ->when($matchingOptions->isNotEmpty(), fn (Builder $subsubquery) => $subsubquery->orWhereHas('productAttributes', fn (Builder $productAttributesQuery) => $productAttributesQuery->whereHas('options', fn (Builder $optionsQuery) => $optionsQuery->whereIn('attribute_option_id', $matchingOptions->pluck('id'))))),
            )
            ->orderBy('title_relevancy', 'desc')
            ->orderBy('title_natural_relevancy', 'desc')
            ->orderBy('title_words_relevancy', 'desc')
            ->orderBy('title_position', 'asc')
            ->when(
                Config::get('search.search_in_descriptions'),
                fn (Builder $subquery) => $subquery->orderBy('content_natural_relevancy', 'desc')
                    ->orderBy('content_relevancy', 'desc'),
            )
            ->orderBy('slug_length', 'asc');
    }

    protected function useNaturalSearch(Builder $query): Builder
    {
        $matchingOptions = $this->findAttributeOptionsFulltext();

        return $query->where(
            fn (Builder $subquery) => $subquery->when(
                Config::get('search.search_in_descriptions'),
                fn (Builder $subsubquery) => $subsubquery->whereFullText(['products.searchable_name', 'products.description_html', 'products.description_short', 'products.search_values'], $this->value),
                fn (Builder $subsubquery) => $subsubquery->whereFullText(['products.searchable_name'], $this->value),
            )->when($this->searchString->contains(self::BOOLEAN_SEARCH_OPERATORS), fn (Builder $subsubquery) => $subsubquery->orWhere('products.searchable_name', 'LIKE', '%' . $this->value . '%'))
                ->orWhere('products.id', 'LIKE', $this->value . '%')
                ->orWhere('products.slug', 'LIKE', Str::slug($this->value) . '%')
                ->when($matchingOptions->isNotEmpty(), fn (Builder $subsubquery) => $subsubquery->orWhereHas('productAttributes', fn (Builder $productAttributesQuery) => $productAttributesQuery->whereHas('options', fn (Builder $optionsQuery) => $optionsQuery->whereIn('attribute_option_id', $matchingOptions->pluck('id'))))),
        );
    }

    protected function useDatabaseQuery(Builder $query): Builder
    {
        $matchingOptions = $this->findAttributeOptions();

        return $query->where(
            fn (Builder $subquery) => $subquery->where('products.id', 'LIKE', $this->value . '%')
                ->orWhere('products.slug', 'LIKE', Str::slug($this->value) . '%')
                ->orWhere($this->likeAllWords('products.searchable_name'))
                ->when(
                    Config::get('search.search_in_descriptions'),
                    fn (Builder $subsubquery) => $subsubquery->orWhere($this->likeAllWords('products.description_html'))
                        ->orWhere($this->likeAllWords('products.description_short'))
                        ->orWhere($this->likeAllWords('products.search_values')),
                )
                ->when($matchingOptions->isNotEmpty(), fn (Builder $subsubquery) => $subsubquery->orWhereHas('productAttributes', fn (Builder $productAttributesQuery) => $productAttributesQuery->whereHas('options', fn (Builder $optionsQuery) => $optionsQuery->whereIn('attribute_option_id', $matchingOptions->pluck('id'))))),
        );
    }

    protected function useDatabaseQueryWithRelevancy(Builder $query): Builder
    {
        $matchingOptions = $this->findAttributeOptions();

        return $this->relevancyQuery($query)
            ->where(
                fn (Builder $subquery) => $subquery->where('products.id', 'LIKE', $this->value . '%')
                    ->orWhere('products.slug', 'LIKE', Str::slug($this->value) . '%')
                    ->orWhere($this->likeAllWords('products.searchable_name'))
                    ->when(
                        Config::get('search.search_in_descriptions'),
                        fn (Builder $subsubquery) => $subsubquery->orWhere($this->likeAllWords('products.description_html'))
                            ->orWhere($this->likeAllWords('products.description_short'))
                            ->orWhere($this->likeAllWords('products.search_values')),
                    )
                    ->when($matchingOptions->isNotEmpty(), fn (Builder $subsubquery) => $subsubquery->orWhereHas('productAttributes', fn (Builder $productAttributesQuery) => $productAttributesQuery->whereHas('options', fn (Builder $optionsQuery) => $optionsQuery->whereIn('attribute_option_id', $matchingOptions->pluck('id'))))),
            )
            ->orderBy('title_relevancy', 'desc')
            ->orderBy('title_natural_relevancy', 'desc')
            ->orderBy('title_words_relevancy', 'desc')
            ->orderBy('title_position', 'asc')
            ->when(
                Config::get('search.search_in_descriptions'),
                fn (Builder $subquery) => $subquery->orderBy('content_natural_relevancy', 'desc')
                    ->orderBy('content_relevancy', 'desc'),
            )
            ->orderBy('slug_length', 'asc');
    }

    private function likeAllWords(string $column): Closure
    {
        return function (Builder $subquery) use ($column): Builder {
            foreach ($this->words as $word) {
                if (is_numeric($word)) {
                    $subquery = $subquery->where($column, 'LIKE', '% ' . $word . '%');
                } else {
                    $subquery = $subquery->where($column, 'LIKE', '%' . $word . '%');
                }
            }

            return $subquery;
        };
    }

    private function likeAnyWord(string $column): Closure
    {
        return function (Builder $subquery) use ($column): Builder {
            foreach ($this->words as $word) {
                if (mb_strlen($word) >= 3) {
                    $subquery = $subquery->orWhere($column, 'LIKE', '%' . $word . '%');
                } else {
                    $subquery = $subquery->orWhere($column, 'LIKE', $word . '%');
                }
            }

            return $subquery;
        };
    }

    private function relevancyQuery(Builder $query): Builder
    {
        return $query->when(
            Config::get('search.search_in_descriptions'),
            fn (Builder $subquery) => $subquery->selectRaw(
                '`products`.*,
                (LENGTH(`products`.`slug`)) AS slug_length,
                IF(POSITION(? IN `products`.`searchable_name`) > 0, POSITION(? IN `products`.`searchable_name`), 9999) AS title_position,
                MATCH(`products`.`searchable_name`) AGAINST (? IN BOOLEAN MODE) as title_relevancy,
                MATCH(`products`.`searchable_name`) AGAINST (?) AS title_natural_relevancy,
                MATCH(`products`.`searchable_name`) AGAINST (? IN BOOLEAN MODE) as title_words_relevancy,
                MATCH(`products`.`searchable_name`, `products`.`description_html`, `products`.`description_short`, `products`.`search_values`) AGAINST (?) AS content_natural_relevancy,
                MATCH(`products`.`searchable_name`, `products`.`description_html`, `products`.`description_short`, `products`.`search_values`) AGAINST (? IN BOOLEAN MODE) as content_relevancy',
                [
                    $this->value,
                    $this->value,
                    $this->titleSearch,
                    $this->naturalSearch,
                    $this->contentSearch,
                    $this->naturalSearch,
                    $this->contentSearch,
                ],
            ),
            fn (Builder $subquery) => $subquery->selectRaw(
                '`products`.*,
                (LENGTH(`products`.`slug`)) AS slug_length,
                IF(POSITION(? IN `products`.`searchable_name`) > 0, POSITION(? IN `products`.`searchable_name`), 9999) AS title_position,
                MATCH(`products`.`searchable_name`) AGAINST (? IN BOOLEAN MODE) as title_relevancy,
                MATCH(`products`.`searchable_name`) AGAINST (?) AS title_natural_relevancy,
                MATCH(`products`.`searchable_name`) AGAINST (? IN BOOLEAN MODE) as title_words_relevancy',
                [
                    $this->value,
                    $this->value,
                    $this->titleSearch,
                    $this->naturalSearch,
                    $this->contentSearch,
                ],
            ),
        );
    }
}
