<?php

namespace App\Criteria;

use Closure;
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

    protected Stringable $searchString;
    /** @var SupportCollection<array-key,string> */
    protected SupportCollection $quotes;
    /** @var SupportCollection<array-key,string> */
    protected SupportCollection $words;
    /** @var SupportCollection<array-key,string> */
    protected SupportCollection $wordsJson;

    protected string $naturalSearch;

    public function __construct(string $key, ?string $value = null)
    {
        parent::__construct($key, trim($value ?? ''));

        $this->searchString = new Stringable($this->value);

        $this->quotes = $this->searchString->contains('"')
            ? $this->searchString->matchAll('|\".*?\"|')
            : collect();

        $this->words = $this->searchString->remove(self::BOOLEAN_SEARCH_OPERATORS)->trim()->replaceMatches('/\s+/', ' ')->explode(' ');
        $this->wordsJson = $this->words->map(fn (string $word) => trim(json_encode($word) ?: '', '"'));

        $this->naturalSearch = $this->quotes->isNotEmpty()
            ? (new Stringable((string) $this->quotes->first()))->remove('"')->trim()->replaceMatches('/\s+/', ' ')
            : $this->wordsJson->implode(' ');
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
        $contentSearch = $this->words->map(fn (string $word) => match (mb_strlen($word)) {
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
        $naturalSearch = $this->naturalSearch;
        $titleSearch = '"' . $naturalSearch . '"';
        $contentSearch = $this->wordsJson->map(fn (string $word) => match (mb_strlen($word)) {
            0 => null,
            1, 2 => $word,
            default => '+' . $word . '*',
        })->filter()->implode(' ');

        $matchingOptions = $this->findAttributeOptionsFulltext();

        return $query->when(
            Config::get('search.search_in_descriptions'),
            fn (Builder $subquery) => $subquery->selectRaw(
                '`products`.*,
                ((LENGTH(`products`.`name`) - LENGTH(replace(`products`.`name`, \' \', \'\')) + 1) = ?) AS title_word_equal_count,
                (LENGTH(`products`.`name`)) AS title_length,
                MATCH(`products`.`name`) AGAINST (? IN BOOLEAN MODE) as title_relevancy,
                MATCH(`products`.`name`) AGAINST (?) AS title_natural_relevancy,
                MATCH(`products`.`name`) AGAINST (? IN BOOLEAN MODE) as title_words_relevancy,
                MATCH(`products`.`name`, `products`.`name`, `products`.`description_html`, `products`.`description_short`, `products`.`search_values`) AGAINST (?) AS content_natural_relevancy,
                MATCH(`products`.`name`, `products`.`name`, `products`.`description_html`, `products`.`description_short`, `products`.`search_values`) AGAINST (? IN BOOLEAN MODE) as content_relevancy',

                [
                    $this->words->count(),
                    $titleSearch,
                    $naturalSearch,
                    $contentSearch,
                    $naturalSearch,
                    $contentSearch,
                ],
            ),
            fn (Builder $subquery) => $subquery->selectRaw(
                '`products`.*,
                ((LENGTH(`products`.`name`) - LENGTH(replace(`products`.`name`, \' \', \'\')) + 1) = ?) AS title_word_equal_count,
                (LENGTH(`products`.`name`)) AS title_length,
                MATCH(`products`.`name`) AGAINST (? IN BOOLEAN MODE) as title_relevancy,
                MATCH(`products`.`name`) AGAINST (?) AS title_natural_relevancy,
                MATCH(`products`.`name`) AGAINST (? IN BOOLEAN MODE) as title_words_relevancy',
                [
                    $this->words->count(),
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
                ->when($matchingOptions->isNotEmpty(), fn (Builder $subsubquery) => $subsubquery->orWhereHas('productAttributes', fn (Builder $productAttributesQuery) => $productAttributesQuery->whereHas('options', fn (Builder $optionsQuery) => $optionsQuery->whereIn('attribute_option_id', $matchingOptions->pluck('id'))))),
        )->orderBy('title_word_equal_count', 'desc')
            ->orderBy('title_relevancy', 'desc')
            ->orderBy('title_natural_relevancy', 'desc')
            ->orderBy('title_words_relevancy', 'desc')
            ->when(
                Config::get('search.search_in_descriptions'),
                fn (Builder $subquery) => $subquery->orderBy('content_natural_relevancy', 'desc')
                    ->orderBy('content_relevancy', 'desc'),
            )
            ->orderBy('title_length', 'asc');
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
                ->when($matchingOptions->isNotEmpty(), fn (Builder $subsubquery) => $subsubquery->orWhereHas('productAttributes', fn (Builder $productAttributesQuery) => $productAttributesQuery->whereHas('options', fn (Builder $optionsQuery) => $optionsQuery->whereIn('attribute_option_id', $matchingOptions->pluck('id'))))),
        );
    }

    protected function useDatabaseQuery(Builder $query): Builder
    {
        $matchingOptions = $this->findAttributeOptions();

        return $query->where(
            fn (Builder $subquery) => $subquery->where('products.id', 'LIKE', $this->naturalSearch . '%')
                ->orWhere('products.slug', 'LIKE', $this->naturalSearch . '%')
                ->orWhere($this->likeAllWords('products.name'))
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

        $naturalSearch = $this->naturalSearch;
        $titleSearch = '"' . $naturalSearch . '"';
        $contentSearch = $this->words->map(fn (string $word) => match (mb_strlen($word)) {
            0 => null,
            1, 2 => $word,
            default => '+' . $word . '*',
        })->filter()->implode(' ');

        return $query->when(
            Config::get('search.search_in_descriptions'),
            fn (Builder $subquery) => $subquery->selectRaw(
                '`products`.*,
                ((LENGTH(`products`.`name`) - LENGTH(replace(`products`.`name`, \' \', \'\')) + 1) = ?) AS title_word_equal_count,
                (LENGTH(`products`.`name`)) AS title_length,
                MATCH(`products`.`name`) AGAINST (? IN BOOLEAN MODE) as title_relevancy,
                MATCH(`products`.`name`) AGAINST (?) AS title_natural_relevancy,
                MATCH(`products`.`name`) AGAINST (? IN BOOLEAN MODE) as title_words_relevancy,
                MATCH(`products`.`name`, `products`.`name`, `products`.`description_html`, `products`.`description_short`, `products`.`search_values`) AGAINST (?) AS content_natural_relevancy,
                MATCH(`products`.`name`, `products`.`name`, `products`.`description_html`, `products`.`description_short`, `products`.`search_values`) AGAINST (? IN BOOLEAN MODE) as content_relevancy',
                [
                    $this->words->count(),
                    $titleSearch,
                    $naturalSearch,
                    $contentSearch,
                    $naturalSearch,
                    $contentSearch,
                ],
            ),
            fn (Builder $subquery) => $subquery->selectRaw(
                '`products`.*,
                ((LENGTH(`products`.`name`) - LENGTH(replace(`products`.`name`, \' \', \'\')) + 1) = ?) AS title_word_equal_count,
                (LENGTH(`products`.`name`)) AS title_length,
                MATCH(`products`.`name`) AGAINST (? IN BOOLEAN MODE) as title_relevancy,
                MATCH(`products`.`name`) AGAINST (?) AS title_natural_relevancy,
                MATCH(`products`.`name`) AGAINST (? IN BOOLEAN MODE) as title_words_relevancy',
                [
                    $this->words->count(),
                    $titleSearch,
                    $naturalSearch,
                    $contentSearch,
                ],
            ),
        )->where(
            fn (Builder $subquery) => $subquery->where('products.id', 'LIKE', $naturalSearch . '%')
                ->orWhere('products.slug', 'LIKE', $naturalSearch . '%')
                ->orWhere($this->likeAllWordsJson('products.name'))
                ->when(
                    Config::get('search.search_in_descriptions'),
                    fn (Builder $subsubquery) => $subsubquery->orWhere($this->likeAllWords('products.description_html'))
                        ->orWhere($this->likeAllWords('products.description_short'))
                        ->orWhere($this->likeAllWords('products.search_values')),
                )
                ->when($matchingOptions->isNotEmpty(), fn (Builder $subsubquery) => $subsubquery->orWhereHas('productAttributes', fn (Builder $productAttributesQuery) => $productAttributesQuery->whereHas('options', fn (Builder $optionsQuery) => $optionsQuery->whereIn('attribute_option_id', $matchingOptions->pluck('id'))))),
        )->orderBy('title_word_equal_count', 'desc')
            ->orderBy('title_relevancy', 'desc')
            ->orderBy('title_natural_relevancy', 'desc')
            ->orderBy('title_words_relevancy', 'desc')
            ->when(
                Config::get('search.search_in_descriptions'),
                fn (Builder $subquery) => $subquery->orderBy('content_natural_relevancy', 'desc')
                    ->orderBy('content_relevancy', 'desc'),
            )
            ->orderBy('title_length', 'asc');
    }

    private function likeAllWords(string $column): Closure
    {
        return function (Builder $subquery) use ($column): Builder {
            foreach ($this->words as $word) {
                $subquery = $subquery->where($column, 'LIKE', '%' . $word . '%');
            }

            return $subquery;
        };
    }

    private function likeAllWordsJson(string $column): Closure
    {
        return function (Builder $subquery) use ($column): Builder {
            foreach ($this->wordsJson as $word) {
                $subquery = $subquery->where($column, 'LIKE', '%' . $word . '%');
            }

            return $subquery;
        };
    }
}
