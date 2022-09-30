<?php

namespace App\Services;

use App\Dtos\ProductCreateDto;
use App\Dtos\ProductUpdateDto;
use App\Enums\SchemaType;
use App\Events\ProductCreated;
use App\Events\ProductDeleted;
use App\Events\ProductUpdated;
use App\Models\Option;
use App\Models\Product;
use App\Models\Schema;
use App\Services\Contracts\AttributeServiceContract;
use App\Services\Contracts\AvailabilityServiceContract;
use App\Services\Contracts\DiscountServiceContract;
use App\Services\Contracts\MediaServiceContract;
use App\Services\Contracts\MetadataServiceContract;
use App\Services\Contracts\ProductServiceContract;
use App\Services\Contracts\SchemaServiceContract;
use App\Services\Contracts\SeoMetadataServiceContract;
use Heseya\Dto\Missing;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ProductService implements ProductServiceContract
{
    public function __construct(
        private MediaServiceContract $mediaService,
        private SchemaServiceContract $schemaService,
        private SeoMetadataServiceContract $seoMetadataService,
        private AvailabilityServiceContract $availabilityService,
        private MetadataServiceContract $metadataService,
        private AttributeServiceContract $attributeService,
        private DiscountServiceContract $discountService,
    ) {
    }

    private function setup(Product $product, ProductCreateDto|ProductUpdateDto $dto): Product
    {
        if (!($dto->getSchemas() instanceof Missing)) {
            $this->schemaService->sync($product, $dto->getSchemas());
        }

        if (!($dto->getSets() instanceof Missing)) {
            $product->sets()->sync($dto->getSets());
        }

        if (!($dto->getItems() instanceof Missing)) {
            $this->assignItems($product, $dto->getItems());
        }

        if (!($dto->getMedia() instanceof Missing)) {
            $this->mediaService->sync($product, $dto->getMedia());
        }

        if (!($dto->getTags() instanceof Missing)) {
            $product->tags()->sync($dto->getTags());
        }

        if (!($dto->getMetadata() instanceof Missing)) {
            $this->metadataService->sync($product, $dto->getMetadata());
        }

        if (!($dto->getAttributes() instanceof Missing)) {
            $this->attributeService->sync($product, $dto->getAttributes());
        }

        $product->seo()->save($this->seoMetadataService->create($dto->getSeo()));

        [$priceMin, $priceMax] = $this->getMinMaxPrices($product);
        $product->price_min_initial = $priceMin;
        $product->price_max_initial = $priceMax;
        $product->available = $this->availabilityService->isProductAvailable($product);
        $this->discountService->applyDiscountsOnProduct($product, false);

        return $product;
    }

    public function create(ProductCreateDto $dto): Product
    {
        DB::beginTransaction();

        $product = Product::create($dto->toArray());
        $product = $this->setup($product, $dto);
        $product->save();

        DB::commit();
        ProductCreated::dispatch($product);
        // @phpstan-ignore-next-line
        Product::where($product->getKeyName(), $product->getKey())->searchable();

        return $product->refresh();
    }

    public function update(Product $product, ProductUpdateDto $dto): Product
    {
        DB::beginTransaction();

        $product->fill($dto->toArray());
        $product = $this->setup($product, $dto);
        $product->save();

        DB::commit();
        ProductUpdated::dispatch($product);
        // @phpstan-ignore-next-line
        Product::where($product->getKeyName(), $product->getKey())->searchable();

        return $product;
    }

    public function delete(Product $product): void
    {
        ProductDeleted::dispatch($product);

        DB::beginTransaction();

        $this->mediaService->sync($product, []);

        $productId = $product->getKey();
        $product->delete();

        if ($product->seo !== null) {
            $this->seoMetadataService->delete($product->seo);
        }

        DB::commit();

        Product::where('id', $productId)->withTrashed()->first()->unsearchable();
    }

    public function getMinMaxPrices(Product $product): array
    {
        [$schemaMin, $schemaMax] = $this->getSchemasPrices(
            clone $product->schemas,
            clone $product->schemas,
        );

        return [
            $product->price + $schemaMin,
            $product->price + $schemaMax,
        ];
    }

    public function updateMinMaxPrices(Product $product): void
    {
        $productMinMaxPrices = $this->getMinMaxPrices($product);
        $product->update([
            'price_min_initial' => $productMinMaxPrices[0],
            'price_max_initial' => $productMinMaxPrices[1],
        ]);
        $this->discountService->applyDiscountsOnProduct($product);
    }

    private function assignItems(Product $product, ?array $items): void
    {
        $items = Collection::make($items)->mapWithKeys(fn (array $item): array => [
            $item['id'] => [
                'required_quantity' => $item['required_quantity'],
            ],
        ]);

        $product->items()->sync($items);
    }

    private function getSchemasPrices(
        Collection $allSchemas,
        Collection $remainingSchemas,
        array $values = [],
    ): array {
        if ($remainingSchemas->isNotEmpty()) {
            /** @var Schema $schema */
            $schema = $remainingSchemas->pop();

            $getBestSchemasPrices = fn ($newValues) => $this->getBestSchemasPrices(
                $allSchemas,
                $remainingSchemas,
                $values,
                $schema,
                $newValues,
            );

            $required = $schema->required;
            $options = $schema->options->map(
                fn (Option $option) => $option->getKey(),
            )->toArray();
            $valueMinMax = [$schema->min, $schema->max];

            $minmax = match ($schema->type) {
                default => $getBestSchemasPrices(
                    $required ? ['filled'] : [null, 'filled'],
                ),
                SchemaType::BOOLEAN => $getBestSchemasPrices([true, false]),
                SchemaType::SELECT => $getBestSchemasPrices(
                    $required ? $options : array_merge($options, [null]),
                ),
                SchemaType::MULTIPLY, SchemaType::MULTIPLY_SCHEMA => $getBestSchemasPrices(
                    $required ? $valueMinMax : array_merge($valueMinMax, [null]),
                ),
            };
        } else {
            $price = $allSchemas->reduce(
                fn (float $carry, Schema $current) => $carry + $current->getPrice(
                    $values[$current->getKey()],
                    $values,
                ),
                0,
            );

            $minmax = [
                $price,
                $price,
            ];
        }

        return $minmax;
    }

    private function getBestSchemasPrices(
        Collection $allSchemas,
        Collection $remainingSchemas,
        array $currentValues,
        Schema $schema,
        array $values,
    ): array {
        return $this->bestMinMax(Collection::make($values)->map(
            fn ($value) => $this->getSchemasPrices(
                $allSchemas,
                clone $remainingSchemas,
                $currentValues + [
                    $schema->getKey() => $value,
                ],
            ),
        ));
    }

    private function bestMinMax(Collection $minmaxCol): array
    {
        return [
            $minmaxCol->reduce(function (?float $carry, array $current) {
                if ($carry === null) {
                    return $current[0];
                }

                return $current[0] < $carry ? $current[0] : $carry;
            }),
            $minmaxCol->reduce(function (?float $carry, array $current) {
                if ($carry === null) {
                    return $current[1];
                }

                return $current[1] > $carry ? $current[1] : $carry;
            }),
        ];
    }
}
