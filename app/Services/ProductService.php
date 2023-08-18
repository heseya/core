<?php

namespace App\Services;

use App\Enums\Product\ProductPriceType;
use App\Enums\SchemaType;
use App\Events\ProductCreated;
use App\Events\ProductDeleted;
use App\Events\ProductPriceUpdated;
use App\Events\ProductUpdated;
use App\Exceptions\PublishingException;
use App\Models\Option;
use App\Models\Product;
use App\Models\Schema;
use App\Repositories\Contracts\ProductRepositoryContract;
use App\Services\Contracts\AvailabilityServiceContract;
use App\Services\Contracts\DiscountServiceContract;
use App\Services\Contracts\MediaServiceContract;
use App\Services\Contracts\MetadataServiceContract;
use App\Services\Contracts\SchemaServiceContract;
use App\Services\Contracts\TranslationServiceContract;
use Brick\Math\Exception\MathException;
use Brick\Money\Exception\MoneyMismatchException;
use Domain\Currency\Currency;
use Domain\Price\Dtos\PriceDto;
use Domain\Product\Dtos\ProductCreateDto;
use Domain\Product\Dtos\ProductUpdateDto;
use Domain\ProductAttribute\Services\AttributeService;
use Domain\Seo\SeoMetadataService;
use Heseya\Dto\DtoException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Spatie\LaravelData\DataCollection;
use Spatie\LaravelData\Optional;

final class ProductService
{
    public function __construct(
        private MediaServiceContract $mediaService,
        private SchemaServiceContract $schemaService,
        private SeoMetadataService $seoMetadataService,
        private AvailabilityServiceContract $availabilityService,
        private MetadataServiceContract $metadataService,
        private AttributeService $attributeService,
        private DiscountServiceContract $discountService,
        private ProductRepositoryContract $productRepository,
        private TranslationServiceContract $translationService,
    ) {}

    /**
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

        $productPrices = $this->productRepository->getProductPrices($product->getKey(), [
            ProductPriceType::PRICE_MIN,
            ProductPriceType::PRICE_MAX,
        ]);

        $productPricesMin = $productPrices->get(ProductPriceType::PRICE_MIN->value);
        $productPricesMax = $productPrices->get(ProductPriceType::PRICE_MAX->value);

        foreach (Currency::cases() as $currency) {
            $priceMin = $productPricesMin?->where(fn (PriceDto $dto) => $dto->currency === $currency)->first();
            $priceMax = $productPricesMax?->where(fn (PriceDto $dto) => $dto->currency === $currency)->first();

            if ($priceMin && $priceMax) {
                ProductPriceUpdated::dispatch(
                    $product->getKey(),
                    null,
                    null,
                    $priceMin->value,
                    $priceMax->value,
                    $currency,
                );
            }
        }

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
        $oldPrices = $this->productRepository->getProductPrices($product->getKey(), [
            ProductPriceType::PRICE_MIN,
            ProductPriceType::PRICE_MAX,
        ]);
        $oldPricesMin = $oldPrices->get(ProductPriceType::PRICE_MIN->value)->groupBy(fn (PriceDto $dto) => $dto->currency->value);
        $oldPricesMax = $oldPrices->get(ProductPriceType::PRICE_MAX->value)->groupBy(fn (PriceDto $dto) => $dto->currency->value);

        DB::beginTransaction();

        $product->fill($dto->toArray());
        if (is_array($dto->translations)) {
            foreach ($dto->translations as $lang => $translations) {
                $product->setLocale($lang)->fill($translations);
            }
        }

        $this->translationService->checkPublished($product, ['name']);

        $product = $this->setup($product, $dto);
        $product->save();
        $product->refresh();

        DB::commit();

        $newPrices = $this->productRepository->getProductPrices($product->getKey(), [
            ProductPriceType::PRICE_MIN,
            ProductPriceType::PRICE_MAX,
        ]);
        $newPricesMin = $newPrices->get(ProductPriceType::PRICE_MIN->value)->groupBy(fn (PriceDto $dto) => $dto->currency->value);
        $newPricesMax = $newPrices->get(ProductPriceType::PRICE_MAX->value)->groupBy(fn (PriceDto $dto) => $dto->currency->value);

        foreach (Currency::cases() as $currency) {
            /** @var PriceDto|null $oldPriceMin */
            $oldPriceMin = $oldPricesMin->get($currency->value)?->first();
            /** @var PriceDto|null $oldPriceMax */
            $oldPriceMax = $oldPricesMax->get($currency->value)?->first();
            $priceMin = $newPricesMin->get($currency->value)?->firstOrFail();
            $priceMax = $newPricesMax->get($currency->value)?->firstOrFail();

            if (
                $priceMin instanceof PriceDto && $priceMax instanceof PriceDto && (!$oldPriceMin?->value->isEqualTo($priceMin->value) || !$oldPriceMax?->value->isEqualTo($priceMax->value))
            ) {
                ProductPriceUpdated::dispatch(
                    $product->getKey(),
                    $oldPriceMin?->value,
                    $oldPriceMax?->value,
                    $priceMin->value,
                    $priceMax->value,
                    $currency
                );
            }
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
     * @return array<int, PriceDto>
     */
    public function getMinMaxPrices(Product $product, Currency $currency = Currency::DEFAULT): array
    {
        [$schemaMin, $schemaMax] = $this->getSchemasPrices(
            clone $product->schemas,
            clone $product->schemas,
            [],
            $currency,
        );

        $price = $product->pricesBase->where('currency', $currency->value)->firstOrFail();

        return [
            PriceDto::from($price->value->plus($schemaMin)),
            PriceDto::from($price->value->plus($schemaMax)),
        ];
    }

    public function updateMinMaxPrices(Product $product): void
    {
        $pricesMinMax = [
            ProductPriceType::PRICE_MIN_INITIAL->value => [],
            ProductPriceType::PRICE_MAX_INITIAL->value => [],
        ];
        foreach (Currency::cases() as $currency) {
            [$pricesMin, $pricesMax] = $this->getMinMaxPrices($product, $currency);

            $pricesMinMax[ProductPriceType::PRICE_MIN_INITIAL->value][] = $pricesMin;
            $pricesMinMax[ProductPriceType::PRICE_MAX_INITIAL->value][] = $pricesMax;
        }

        $this->productRepository->setProductPrices($product->getKey(), $pricesMinMax);

        $this->discountService->applyDiscountsOnProduct($product);
    }

    private function setup(Product $product, ProductCreateDto|ProductUpdateDto $dto): Product
    {
        if (!($dto->schemas instanceof Optional)) {
            $this->schemaService->sync($product, $dto->schemas);
        }

        if (!($dto->sets instanceof Optional)) {
            $product->sets()->sync($dto->sets);
        }

        if (!($dto->items instanceof Optional)) {
            $this->assignItems($product, $dto->items);
        }

        if (!($dto->media instanceof Optional)) {
            $this->mediaService->sync($product, $dto->media);
        }

        if (!($dto->tags instanceof Optional)) {
            $product->tags()->sync($dto->tags);
        }

        if (is_array($dto->metadata_computed)) {
            $this->metadataService->sync($product, $dto->metadata_computed);
        }

        if (!($dto->attributes instanceof Optional)) {
            $this->attributeService->sync($product, $dto->attributes);
        }

        if (!($dto->descriptions instanceof Optional)) {
            $product->pages()->sync($dto->descriptions);
        }

        if (!($dto->seo instanceof Optional)) {
            $this->seoMetadataService->createOrUpdateFor($product, $dto->seo);
        }

        if (!($dto->related_sets instanceof Optional)) {
            $product->relatedSets()->sync($dto->related_sets);
        }

        if ($dto->prices_base instanceof DataCollection) {
            $this->productRepository->setProductPrices($product->getKey(), [
                ProductPriceType::PRICE_BASE->value => $dto->prices_base->items(),
            ]);
        }

        $this->updateMinMaxPrices($product);

        $availability = $this->availabilityService->getCalculateProductAvailability($product);
        $product->quantity = $availability['quantity'];
        $product->available = $availability['available'];
        $product->shipping_time = $availability['shipping_time'];
        $product->shipping_date = $availability['shipping_date'];

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
        Currency $currency = Currency::DEFAULT,
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
                $currency,
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
                    $currency,
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
        Currency $currency = Currency::DEFAULT,
    ): array {
        return $this->bestMinMax(Collection::make($values)->map(
            fn ($value) => $this->getSchemasPrices(
                $allSchemas,
                clone $remainingSchemas,
                $currentValues + [
                    $schema->getKey() => $value,
                ],
                $currency,
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
