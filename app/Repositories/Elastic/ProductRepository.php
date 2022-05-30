<?php

declare(strict_types=1);

namespace App\Repositories\Elastic;

use App\Dtos\ProductSearchDto;
use App\Models\Attribute;
use App\Models\AttributeOption;
use App\Models\Media;
use App\Models\Metadata;
use App\Models\Product;
use App\Models\Tag;
use App\Repositories\Contracts\ProductRepositoryContract;
use App\Services\Contracts\SortServiceContract;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use JeroenG\Explorer\Domain\Syntax\Matching;
use JeroenG\Explorer\Domain\Syntax\Nested;
use JeroenG\Explorer\Domain\Syntax\Range;
use JeroenG\Explorer\Domain\Syntax\Term;
use JeroenG\Explorer\Domain\Syntax\Terms;
use Laravel\Scout\Builder;

class ProductRepository implements ProductRepositoryContract
{
    private const CRITERIA = [
        'ids' => 'filterIds',
        'slug' => 'must',
        'name' => 'filterName',
        'public' => 'filter',
        'available' => 'filter',
        'sets' => 'filterSlug',
        'tags' => 'filterId',
        'metadata' => 'filterMeta',
        'metadata_private' => 'filterMeta',
        'price_min' => 'filterPriceMin',
        'price_max' => 'filterPriceMax',
        'attribute' => 'filterAttributes',
    ];

    public function __construct(
        private SortServiceContract $sortService,
    ) {
    }

    public function search(ProductSearchDto $dto): LengthAwarePaginator
    {
        $query = Product::search($dto->getSearch());

        if ($dto->getSort() !== null) {
            $query = $this->sortService->sortScout($query, $dto->getSort());
        }

        $hide_on_index = true;

        foreach ($dto->toArray() as $key => $value) {
            if (array_key_exists($key, self::CRITERIA)) {
                $query = $this->{self::CRITERIA[$key]}($query, $key, $value);
                $hide_on_index = false;
            }
        }

        if (Gate::denies('products.show_hidden')) {
            $query->filter(new Term('public', true));

            // If no criteria are set, toArray() will return sort and search.
            if ($hide_on_index && $dto->getSearch() === null && count($dto->toArray()) < 3) {
                $query->filter(new Term('hide_on_index', false));
            }
        }

        $results = $query->paginateRaw(Config::get('pagination.per_page'));
        $products = new Collection();

        foreach ($results->items() as $item) {
            if (!isset($item['hits']) || !isset($item['hits']['hits'])) {
                continue;
            }
            foreach ($item['hits']['hits'] as $hit) {
                $products->push($this->mapProduct($hit));
            }
        }

        $results->setCollection($products);

        return $results;
    }

    private function mapProduct(array $hit): Product
    {
        $product = new Product();
        $product->forceFill(Arr::only($hit['_source'], [
            'id',
            'name',
            'slug',
            'price',
            'price_min',
            'price_max',
            'price_min_initial',
            'price_max_initial',
            'public',
            'available',
            'google_product_category',
        ]));

        if ($hit['_source']['cover'] !== null) {
            $media = new Media();
            $media->forceFill(Arr::except($hit['_source']['cover'], ['metadata', 'metadata_private']));
            $media->setRelation(
                'metadata',
                $this->mapMetadata($hit['_source']['cover']['metadata'], true),
            );
            $media->setRelation(
                'metadataPrivate',
                $this->mapMetadata($hit['_source']['cover']['metadata_private'], false),
            );
            $product->setRelation('media', Collection::make([$media]));
        } else {
            $product->setRelation('media', new Collection());
        }

        $tags = new Collection();
        foreach ($hit['_source']['tags'] as $raw) {
            $tag = new Tag();
            $tag->forceFill($raw);
            $tags->push($tag);
        }
        $product->setRelation('tags', $tags);

        $attributes = new Collection();
        foreach ($hit['_source']['attributes'] as $raw) {
            $attribute = new Attribute();
            $properties = Arr::except($raw, ['values', 'metadata', 'metadata_private', 'attribute_type']);
            $properties['type'] = $raw['attribute_type'];
            $attribute->forceFill($properties);

            $options = new Collection();
            foreach ($raw['values'] as $value) {
                $option = new AttributeOption();
                $option->forceFill(Arr::except($value, ['metadata', 'metadata_private']));
                $option->setRelation(
                    'metadata',
                    $this->mapMetadata($value['metadata'], true),
                );
                $option->setRelation(
                    'metadataPrivate',
                    $this->mapMetadata($value['metadata_private'], false),
                );
                $options->push($option);
            }
            $attribute->setRelation('options', $options);
            $attribute->setRelation(
                'metadata',
                $this->mapMetadata($raw['metadata'], true),
            );
            $attribute->setRelation(
                'metadataPrivate',
                $this->mapMetadata($raw['metadata_private'], false),
            );

            $attributes->push($attribute);
        }
        $product->setRelation('attributes', $attributes);

        $product->setRelation(
            'metadata',
            $this->mapMetadata($hit['_source']['metadata'], true),
        );
        $product->setRelation(
            'metadataPrivate',
            $this->mapMetadata($hit['_source']['metadata_private'], false),
        );

        return $product;
    }

    private function mapMetadata(array $metaList, bool $public): Collection
    {
        $collection = new Collection();

        foreach ($metaList as $meta) {
            $metadata = new Metadata();
            $metadata->forceFill([
                'id' => $meta['id'],
                'name' => $meta['name'],
                'value' => $meta['value'],
                'value_type' => $meta['value_type'],
                'public' => $public,
            ]);
            $collection->push($metadata);
        }

        return $collection;
    }

    private function must(Builder $query, string $key, string|int|float|bool $value): Builder
    {
        return $query->must(new Matching($key, $value));
    }

    private function filterName(Builder $query, string $key, string|int|float|bool $value): Builder
    {
        $matching = new Matching($key, $value);
        $matching->setBoost(10);

        return $query->must($matching);
    }

    private function filter(Builder $query, string $key, string|int|float|bool $value): Builder
    {
        return $query->filter(new Term($key, $value));
    }

    private function filterSlug(Builder $query, string $key, array $slugs): Builder
    {
        return $query->filter(new Terms("${key}_slug", $slugs));
    }

    private function filterId(Builder $query, string $key, array $ids): Builder
    {
        return $query->filter(new Terms("${key}_id", $ids));
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

    private function filterAttributes(Builder $query, string $key, array $attributes): Builder
    {
        $values = array_values($attributes);

        $query->filter(new Terms('attributes_slug', array_keys($attributes)));
        $query->must(new Nested('attributes.values', new Terms('attributes.values.id', $values)));
        //TODO: transform values min&max to search by value range
        // $query->should(new Terms('attributes.values.value_number', $values));
        // $query->should(new Terms('attributes.values.value_date', $values));

        return $query;
    }
}
