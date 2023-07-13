<?php

namespace App\Services;

use App\Dtos\ProductCreateDto;
use App\Dtos\ProductUpdateDto;
use App\Enums\SchemaType;
use App\Events\ProductCreated;
use App\Events\ProductDeleted;
use App\Events\ProductPriceUpdated;
use App\Events\ProductUpdated;
use App\Models\Option;
use App\Models\Price;
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
use Brick\Math\Exception\MathException;
use Brick\Money\Exception\MoneyMismatchException;
use Brick\Money\Money;
use Heseya\Dto\Missing;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

readonly class ProductService implements ProductServiceContract
{
    public function __construct(
        private MediaServiceContract $mediaService,
        private SchemaServiceContract $schemaService,
        private SeoMetadataServiceContract $seoMetadataService,
        private AvailabilityServiceContract $availabilityService,
        private MetadataServiceContract $metadataService,
        private AttributeServiceContract $attributeService,
        private DiscountServiceContract $discountService,
    ) {}

    /**
     * @throws MathException
     * @throws MoneyMismatchException
     */
    private function setup(Product $product, ProductCreateDto|ProductUpdateDto $dto): Product
    {
        if (!($dto->schemas instanceof Missing)) {
            $this->schemaService->sync($product, $dto->schemas);
        }

        if (!($dto->sets instanceof Missing)) {
            $product->sets()->sync($dto->sets);
        }

        if (!($dto->items instanceof Missing)) {
            $this->assignItems($product, $dto->items);
        }

        if (!($dto->media instanceof Missing)) {
            $this->mediaService->sync($product, $dto->media);
        }

        if (!($dto->tags instanceof Missing)) {
            $product->tags()->sync($dto->tags);
        }

        if (!($dto->getMetadata() instanceof Missing)) {
            $this->metadataService->sync($product, $dto->getMetadata());
        }

        if (!($dto->attributes instanceof Missing)) {
            $this->attributeService->sync($product, $dto->attributes);
        }

        if (!($dto->descriptions instanceof Missing)) {
            $product->pages()->sync($dto->descriptions);
        }

        if (!($dto->seo instanceof Missing)) {
            $this->seoMetadataService->createOrUpdateFor($product, $dto->seo);
        }

        if (!($dto->relatedSets instanceof Missing)) {
            $product->relatedSets()->sync($dto->relatedSets);
        }

        foreach ($dto->price_base as $base_price) {
            $product->pricesBase()->updateOrCreate($base_price->toArray());
        }

        [$priceMin, $priceMax] = $this->getMinMaxPrices($product);

        //        $product->price_min_initial = $priceMin;
        //        $product->price_max_initial = $priceMax;

        //        $product->prices()
        //            ->where('price_type', 'price_min_initial')
        //            ->updateOrCreate([
        //                'price_type' => 'price_min_initial',
        //                'value' => $priceMin,
        //            ]);
        //
        //        $product->prices()
        //            ->where('price_type', 'price_max_initial')
        //            ->updateOrCreate([
        //                'price_type' => 'price_max_initial',
        //                'value' => $priceMax,
        //            ]);

        $product->pricesMinInitial()->updateOrCreate(['value' => $priceMin]);
        $product->pricesMaxInitial()->updateOrCreate(['value' => $priceMax]);

        $availability = $this->availabilityService->getCalculateProductAvailability($product);
        $product->quantity = $availability['quantity'];
        $product->available = $availability['available'];
        $product->shipping_time = $availability['shipping_time'];
        $product->shipping_date = $availability['shipping_date'];

        // TODO: BROKEN!!!!
        $this->discountService->applyDiscountsOnProduct($product, false);

        return $product;
    }

    /**
     * @throws MathException
     * @throws MoneyMismatchException
     */
    public function create(ProductCreateDto $dto): Product
    {
        DB::beginTransaction();

        /** @var Product $product */
        $product = Product::query()->create($dto->toArray());
        $product = $this->setup($product, $dto);
        $product->save();

        DB::commit();
        ProductPriceUpdated::dispatch(
            $product->getKey(),
            null,
            null,
            $product->pricesMin()->first()->value, // @phpstan-ignore-line
            $product->pricesMin()->first()->value, // @phpstan-ignore-line
        );
        ProductCreated::dispatch($product);
        // @phpstan-ignore-next-line
        Product::where($product->getKeyName(), $product->getKey())->searchable();

        return $product->refresh();
    }

    public function update(Product $product, ProductUpdateDto $dto): Product
    {
        $oldMinPrice = $product->price_min;
        $oldMaxPrice = $product->price_max;

        DB::beginTransaction();

        $product->fill($dto->toArray());
        $product = $this->setup($product, $dto);
        $product->save();

        DB::commit();

        if ($oldMinPrice !== $product->price_min || $oldMaxPrice !== $product->price_max) {
            ProductPriceUpdated::dispatch(
                $product->getKey(),
                $oldMinPrice,
                $oldMaxPrice,
                $product->price_min, // @phpstan-ignore-line
                $product->price_max, // @phpstan-ignore-line
            );
        }

        ProductUpdated::dispatch($product);
        // @phpstan-ignore-next-line
        Product::query()->where($product->getKeyName(), $product->getKey())->searchable();

        // fix for duplicated items in relation after recalculating availability
        $product->unsetRelation('items');

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

        Product::query()->where('id', $productId)->withTrashed()->first()?->unsearchable();
    }

    /**
     * @return Money[]
     *
     * @throws MathException
     * @throws MoneyMismatchException
     */
    public function getMinMaxPrices(Product $product): array
    {
        [$schemaMin, $schemaMax] = $this->getSchemasPrices(
            clone $product->schemas,
            clone $product->schemas,
        );

        /** @var Price $price */
        $price = $product->pricesBase()->first();

        return [
            $price->value->plus($schemaMin),
            $price->value->plus($schemaMax),
        ];
    }

    /**
     * @throws MathException
     * @throws MoneyMismatchException
     */
    public function updateMinMaxPrices(Product $product): void
    {
        [$priceMin, $priceMax] = $this->getMinMaxPrices($product);
        $product->update([
            'price_min_initial' => $priceMin,
            'price_max_initial' => $priceMax,
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

    /**
     * @return float[]
     */
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

            $minmax = match ($schema->type->value) {
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

                return min($current[0], $carry);
            }),
            $minmaxCol->reduce(function (?float $carry, array $current) {
                if ($carry === null) {
                    return $current[1];
                }

                return max($current[1], $carry);
            }),
        ];
    }
}
