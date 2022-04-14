<?php

declare(strict_types=1);

namespace App\Repositories\Elastic;

use App\Dtos\ProductSearchDto;
use App\Models\Product;
use App\Repositories\Contracts\ProductRepositoryContract;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use JeroenG\Explorer\Domain\Syntax\Matching;
use JeroenG\Explorer\Domain\Syntax\Range;
use JeroenG\Explorer\Domain\Syntax\Term;
use JeroenG\Explorer\Domain\Syntax\Terms;
use Laravel\Scout\Builder;

class ProductRepository implements ProductRepositoryContract
{
    private const CRITERIA = [
        'ids' => 'filterIds',
        'slug' => 'must',
        'name' => 'must',
        'public' => 'filter',
        'available' => 'filter',
        'sets' => 'filterSlug',
        'tags' => 'filterId',
        'metadata' => 'filterMeta',
        'metadata_private' => 'filterMeta',
        'price_min' => 'filterPriceMin',
        'price_max' => 'filterPriceMax',
    ];

    public function search(ProductSearchDto $dto): LengthAwarePaginator
    {
        $query = Product::search($dto->getSearch())
            ->sort($dto->getSort());

        $hide_on_index = true;

        foreach ($dto->toArray() as $key => $value) {
            if (array_key_exists($key, self::CRITERIA)) {
                $query = $this->{self::CRITERIA[$key]}($query, $key, $value);
                $hide_on_index = false;
            }
        }

        if (Gate::denies('products.show_hidden')) {
            $query->filter(new Term('public', true));

            if ($hide_on_index && $dto->getSearch() === null) {
                $query->filter(new Term('hide_on_index', false));
            }
        }

        return $query->paginate(Config::get('pagination.per_page'));
    }

    private function must(Builder $query, string $key, string|int|float|bool $value): Builder
    {
        return $query->must(new Matching($key, $value));
    }

    private function filter(Builder $query, string $key, string|int|float|bool $value): Builder
    {
        return $query->filter(new Term($key, $value));
    }

    private function filterSlug(Builder $query, string $key, array $slugs): Builder
    {
        return $query->filter(new Terms("{$key}.slug", $slugs));
    }

    private function filterId(Builder $query, string $key, array $ids): Builder
    {
        return $query->filter(new Terms("{$key}.id", $ids));
    }

    private function filterIds(Builder $query, string $key, string $ids): Builder
    {
        $ids = Str::of($ids)->explode(',');

        $query->filter(new Terms('id', $ids->toArray()));

        return $query;
    }

    private function filterMeta(Builder $query, string $key, array $meta): Builder
    {
        $query->filter(new Terms("${key}.name", array_keys($meta)));
        $query->filter(new Terms("${key}.value", array_values($meta)));

        return $query;
    }

    private function filterPriceMin(Builder $query, string $key, float $value): Builder
    {
        return $query->filter(new Range('price_min', ['gte' => $value]));
    }

    private function filterPriceMax(Builder $query, string $key, float $value): Builder
    {
        return $query->filter(new Range('price_max', ['lte' => $value]));
    }
}
