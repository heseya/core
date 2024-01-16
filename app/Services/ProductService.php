<?php

namespace App\Services;

use App\Enums\SchemaType;
use App\Events\ProductCreated;
use App\Events\ProductDeleted;
use App\Events\ProductUpdated;
use App\Exceptions\PublishingException;
use App\Models\Option;
use App\Models\Product;
use App\Models\ProductAttribute;
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
use Brick\Money\Money;
use Domain\Currency\Currency;
use Domain\Price\Dtos\PriceDto;
use Domain\Price\Enums\ProductPriceType;
use Domain\Product\Dtos\ProductCreateDto;
use Domain\Product\Dtos\ProductUpdateDto;
use Domain\Product\Models\ProductBannerMedia;
use Domain\ProductAttribute\Models\Attribute;
use Domain\ProductAttribute\Models\AttributeOption;
use Domain\ProductAttribute\Services\AttributeService;
use Domain\ProductSet\ProductSetService;
use Domain\Seo\SeoMetadataService;
use Heseya\Dto\DtoException;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Spatie\LaravelData\DataCollection;
use Spatie\LaravelData\Optional;

final readonly class ProductService
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
        private ProductSetService $productSetService,
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

    public function updateProductIndex(Product $product): void
    {
        $product = $this->prepareProductSearchValues($product);
        $product->save();
    }

    private function setup(Product $product, ProductCreateDto|ProductUpdateDto $dto): Product
    {
        if (!($dto->schemas instanceof Optional)) {
            $this->schemaService->sync($product, $dto->schemas);
        }

        if (!($dto->sets instanceof Optional)) {
            $new = array_diff($dto->sets, $product->sets->pluck('id')->toArray());
            $product->sets()->sync($dto->sets);
            $product->ancestorSets()->sync($dto->sets);
            $this->productSetService->fixOrderForSets($new, $product);
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
            $product->loadMissing(['productAttributes' => fn (Builder|HasMany $query) => $query->whereIn('attribute_id', array_keys($dto->attributes))]);
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

        $this->setBannerMedia($product, $dto);

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
     * @return Money[]
     *
     * @throws MathException
     * @throws MoneyMismatchException
     */
    private function getSchemasPrices(
        Collection $allSchemas,
        Collection $remainingSchemas,
        Currency $currency,
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
                fn (Money $carry, Schema $current) => $carry->plus(
                    $current->getPrice(
                        $values[$current->getKey()],
                        $values,
                        $currency,
                    ),
                ),
                Money::zero($currency->value),
            );

            $minmax = [
                $price,
                $price,
            ];
        }

        return $minmax;
    }

    /**
     * @return Money[]
     *
     * @throws MoneyMismatchException
     */
    private function getBestSchemasPrices(
        Collection $allSchemas,
        Collection $remainingSchemas,
        array $currentValues,
        Schema $schema,
        array $values,
        Currency $currency,
    ): array {
        return $this->bestMinMax(
            Collection::make($values)->map(
                fn ($value) => $this->getSchemasPrices(
                    $allSchemas,
                    clone $remainingSchemas,
                    $currency,
                    $currentValues + [
                        $schema->getKey() => $value,
                    ],
                ),
            ),
            $currency,
        );
    }

    /**
     * @return Money[]
     *
     * @throws MoneyMismatchException
     */
    private function bestMinMax(Collection $minmaxCol, Currency $currency): array
    {
        $bestMin = $minmaxCol->reduce(function (?Money $carry, array $current) {
            if ($carry === null) {
                return $current[0];
            }

            return Money::min($current[0], $carry);
        }) ?? Money::zero($currency->value);

        $bestMax = $minmaxCol->reduce(function (?Money $carry, array $current) {
            if ($carry === null) {
                return $current[1];
            }

            return Money::max($current[1], $carry);
        }) ?? $bestMin;

        return [$bestMin, $bestMax];
    }

    private function prepareProductSearchValues(Product $product): Product
    {
        $searchValues = [
            ...$product->tags->pluck('name'),
            ...$product->sets->pluck('name'),
        ];

        /** @var Attribute $attribute */
        foreach ($product->attributes as $attribute) {
            $searchValues[] = $attribute->name;
            if ($attribute->product_attribute_pivot instanceof ProductAttribute) {
                /** @var AttributeOption $option */
                foreach ($attribute->product_attribute_pivot->options as $option) {
                    $searchValues[] = $option->name;
                    $searchValues[] = $option->value_number;
                    $searchValues[] = $option->value_date;
                }
            }
        }

        $product->search_values = implode(' ', $searchValues);

        return $product;
    }

    private function setBannerMedia(Product $product, ProductCreateDto|ProductUpdateDto $dto): void
    {
        if (!($dto->banner_media instanceof Optional)) {
            if ($dto->banner_media) {
                /** @var ProductBannerMedia|null $bannerMedia */
                $bannerMedia = $product->bannerMedia;
                if (!$bannerMedia) {
                    /** @var ProductBannerMedia $bannerMedia */
                    $bannerMedia = ProductBannerMedia::create($dto->banner_media->toArray());
                } else {
                    $bannerMedia->fill($dto->banner_media->toArray());
                }
                if (!($dto->banner_media->translations instanceof Optional)) {
                    foreach ($dto->banner_media->translations as $lang => $translation) {
                        $bannerMedia->setLocale($lang)->fill($translation);
                    }
                }
                $bannerMedia->save();

                if (!($dto->banner_media->media instanceof Optional)) {
                    $medias = [];
                    foreach ($dto->banner_media->media as $media) {
                        $medias[$media->media] = ['min_screen_width' => $media->min_screen_width];
                    }
                    $bannerMedia->media()->sync($medias);
                }
                $product->banner_media_id = $bannerMedia->getKey();
            } else {
                $product->bannerMedia()->delete();
            }
        }
    }
}
