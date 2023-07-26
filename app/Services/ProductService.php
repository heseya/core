<?php

namespace App\Services;

use App\Dtos\PriceDto;
use App\Dtos\ProductCreateDto;
use App\Dtos\ProductUpdateDto;
use App\Enums\Product\ProductPriceType;
use App\Enums\SchemaType;
use App\Events\ProductCreated;
use App\Events\ProductDeleted;
use App\Events\ProductPriceUpdated;
use App\Events\ProductUpdated;
use App\Exceptions\PublishingException;
use App\Models\Option;
use App\Models\Price;
use App\Models\Product;
use App\Models\Schema;
use App\Repositories\Contracts\ProductRepositoryContract;
use App\Services\Contracts\AttributeServiceContract;
use App\Services\Contracts\AvailabilityServiceContract;
use App\Services\Contracts\DiscountServiceContract;
use App\Services\Contracts\MediaServiceContract;
use App\Services\Contracts\MetadataServiceContract;
use App\Services\Contracts\ProductServiceContract;
use App\Services\Contracts\SchemaServiceContract;
use App\Services\Contracts\SeoMetadataServiceContract;
use App\Services\Contracts\TranslationServiceContract;
use Brick\Math\Exception\MathException;
use Brick\Money\Exception\MoneyMismatchException;
use Brick\Money\Money;
use Heseya\Dto\DtoException;
use Heseya\Dto\Missing;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

final readonly class ProductService implements ProductServiceContract
{
    public function __construct(
        private MediaServiceContract $mediaService,
        private SchemaServiceContract $schemaService,
        private SeoMetadataServiceContract $seoMetadataService,
        private AvailabilityServiceContract $availabilityService,
        private MetadataServiceContract $metadataService,
        private AttributeServiceContract $attributeService,
        private DiscountServiceContract $discountService,
        private ProductRepositoryContract $productRepository,
        private TranslationServiceContract $translationService,
    ) {}

    /**
     * @throws MathException
     * @throws MoneyMismatchException
     * @throws DtoException
     * @throws PublishingException
     */
    public function create(ProductCreateDto $dto): Product
    {
        DB::beginTransaction();

        $product = new Product($dto->toArray());

        foreach ($dto->translations as $lang => $translations) {
            $product->setLocale($lang)->fill($translations);
        }

        $this->translationService->checkPublished($product, ['name']);

        $product->save();
        $product = $this->setup($product, $dto);
        $product->save();

        DB::commit();

        // TODO: Make this webhook with currencies
        ProductPriceUpdated::dispatch(
            $product->getKey(),
            null,
            null,
            $product->pricesMin->first()->value,
            $product->pricesMax->first()->value,
        );
        ProductCreated::dispatch($product);

        return $product->refresh();
    }

    /**
     * @throws MathException
     * @throws DtoException
     * @throws MoneyMismatchException
     * @throws PublishingException
     */
    public function update(Product $product, ProductUpdateDto $dto): Product
    {
        /** @var ?Money $oldMinPrice */
        $oldMinPrice = $product->pricesMin->first()?->value;
        /** @var ?Money $oldMaxPrice */
        $oldMaxPrice = $product->pricesMax->first()?->value;

        DB::beginTransaction();

        $product->fill($dto->toArray());
        foreach ($dto->translations as $lang => $translations) {
            $product->setLocale($lang)->fill($translations);
        }
        $this->translationService->checkPublished($product, ['name']);

        $product = $this->setup($product, $dto);
        $product->save();
        $product->refresh();

        DB::commit();

        /** @var Money $minPrice */
        $minPrice = $product->pricesMin->first()->value;
        /** @var Money $maxPrice */
        $maxPrice = $product->pricesMax->first()->value;

        // TODO: This is just wrong
        if (
            $oldMinPrice === null
            || $oldMaxPrice === null
            || !$oldMinPrice->isEqualTo($minPrice)
            || !$oldMaxPrice->isEqualTo($maxPrice)
        ) {
            ProductPriceUpdated::dispatch(
                $product->getKey(),
                $oldMinPrice,
                $oldMaxPrice,
                $minPrice,
                $maxPrice,
            );
        }

        ProductUpdated::dispatch($product);

        // fix for duplicated items in relation after recalculating availability
        $product->unsetRelation('items');

        return $product;
    }

    public function delete(Product $product): void
    {
        ProductDeleted::dispatch($product);

        DB::beginTransaction();

        $this->mediaService->sync($product, []);

        $product->delete();

        if ($product->seo !== null) {
            $this->seoMetadataService->delete($product->seo);
        }

        DB::commit();
    }

    /**
     * @return PriceDto[][]
     *
     * @throws MathException
     * @throws MoneyMismatchException
     * @throws DtoException
     */
    public function getMinMaxPrices(Product $product): array
    {
        // TODO: Get schema prices for each currency
        [$schemaMin, $schemaMax] = $this->getSchemasPrices(
            clone $product->schemas,
            clone $product->schemas,
        );

        /** @var Collection $pricesMin */
        $pricesMin = $product->pricesBase->map(
            fn (Price $price) => new PriceDto($price->value->plus($schemaMin)),
        );

        /** @var Collection $pricesMin */
        $pricesMax = $product->pricesBase->map(
            fn (Price $price) => new PriceDto($price->value->plus($schemaMax)),
        );

        return [
            $pricesMin->toArray(),
            $pricesMax->toArray(),
        ];
    }

    /**
     * @throws MathException
     * @throws MoneyMismatchException
     * @throws DtoException
     */
    public function updateMinMaxPrices(Product $product): void
    {
        [$pricesMin, $pricesMax] = $this->getMinMaxPrices($product);

        $this->productRepository->setProductPrices($product->getKey(), [
            ProductPriceType::PRICE_MIN_INITIAL->value => $pricesMin,
            ProductPriceType::PRICE_MAX_INITIAL->value => $pricesMax,
        ]);
        $this->discountService->applyDiscountsOnProduct($product);
    }

    /**
     * @throws MathException
     * @throws MoneyMismatchException
     * @throws DtoException
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

        $this->productRepository->setProductPrices($product->getKey(), [
            'price_base' => $dto->prices_base,
        ]);

        [$pricesMin, $pricesMax] = $this->getMinMaxPrices($product);

        // TODO: Need to calc schema prices for each currency
        $this->productRepository->setProductPrices($product->getKey(), [
            ProductPriceType::PRICE_MIN_INITIAL->value => $pricesMin,
            ProductPriceType::PRICE_MAX_INITIAL->value => $pricesMax,
        ]);

        $availability = $this->availabilityService->getCalculateProductAvailability($product);
        $product->quantity = $availability['quantity'];
        $product->available = $availability['available'];
        $product->shipping_time = $availability['shipping_time'];
        $product->shipping_date = $availability['shipping_date'];

        $this->discountService->applyDiscountsOnProduct($product);

        return $product;
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

    /**
     * @return float[]
     */
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

    /**
     * @return float[]
     */
    private function bestMinMax(Collection $minmaxCol): array
    {
        $bestMin = $minmaxCol->reduce(function (?float $carry, array $current) {
            if ($carry === null) {
                return $current[0];
            }

            return min($current[0], $carry);
        }) ?? 0;

        $bestMax = $minmaxCol->reduce(function (?float $carry, array $current) {
            if ($carry === null) {
                return $current[1];
            }

            return max($current[1], $carry);
        }) ?? $bestMin;

        return [$bestMin, $bestMax];
    }
}
