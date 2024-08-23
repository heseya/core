<?php

declare(strict_types=1);

namespace App\Services;

use App\Events\ProductCreated;
use App\Events\ProductDeleted;
use App\Events\ProductUpdated;
use App\Exceptions\PublishingException;
use App\Models\Discount;
use App\Models\Product;
use App\Models\ProductAttribute;
use App\Repositories\ProductRepository;
use App\Services\Contracts\AvailabilityServiceContract;
use App\Services\Contracts\MediaServiceContract;
use App\Services\Contracts\MetadataServiceContract;
use App\Services\Contracts\TranslationServiceContract;
use Brick\Math\Exception\MathException;
use Brick\Money\Exception\MoneyMismatchException;
use Brick\Money\Money;
use Domain\Price\Dtos\ProductCachedPriceDto;
use Domain\Price\Enums\ProductPriceType;
use Domain\PriceMap\PriceMap;
use Domain\PriceMap\PriceMapService;
use Domain\Product\Dtos\ProductCreateDto;
use Domain\Product\Dtos\ProductUpdateDto;
use Domain\Product\Models\ProductBannerMedia;
use Domain\ProductAttribute\Models\Attribute;
use Domain\ProductAttribute\Models\AttributeOption;
use Domain\ProductAttribute\Services\AttributeService;
use Domain\ProductSchema\Models\Schema;
use Domain\ProductSchema\Services\SchemaService;
use Domain\ProductSet\ProductSetService;
use Domain\SalesChannel\Models\SalesChannel;
use Domain\SalesChannel\SalesChannelService;
use Domain\Seo\SeoMetadataService;
use Heseya\Dto\DtoException;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;
use Spatie\LaravelData\DataCollection;
use Spatie\LaravelData\Optional;

final readonly class ProductService
{
    public function __construct(
        private AttributeService $attributeService,
        private AvailabilityServiceContract $availabilityService,
        private DiscountService $discountService,
        private MediaServiceContract $mediaService,
        private MetadataServiceContract $metadataService,
        private PriceMapService $priceMapService,
        private ProductRepository $productRepository,
        private ProductSetService $productSetService,
        private SalesChannelService $salesChannelService,
        private SchemaService $schemaService,
        private SeoMetadataService $seoMetadataService,
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

    public function updateMinPrices(Product $product): void
    {
        $this->updateInitialPricesForAllActiveSalesChannels($product);

        $this->discountService->applyDiscountsOnProduct($product);
    }

    public function updateInitialPricesForAllActiveSalesChannels(Product $product): void
    {
        $prices = [];

        foreach (SalesChannel::active()->hasPriceMap()->with('priceMap')->get() as $salesChannel) {
            $priceMap = $salesChannel->priceMap;
            assert($priceMap instanceof PriceMap);

            $price = $product->mappedPriceForPriceMap($priceMap);

            if ($price === null) {
                continue;
            }

            $vat_rate = $this->salesChannelService->getVatRate($salesChannel);

            if ($priceMap->is_net) {
                $net = $price->value;
                $gross = $this->salesChannelService->addVat($price->value, $vat_rate);
            } else {
                $net = $this->salesChannelService->removeVat($price->value, $vat_rate);
                $gross = $price->value;
            }

            $prices[] = ProductCachedPriceDto::from([
                'net' => $net,
                'gross' => $gross,
                'currency' => $priceMap->currency,
                'sales_channel_id' => $salesChannel->id,
            ]);
        }

        $this->productRepository->setCachedProductPrices($product->getKey(), [ProductPriceType::PRICE_INITIAL->value => $prices]);
    }

    public function getMaxPriceInPriceMap(Product $product, PriceMap $priceMap): Money
    {
        $max = $product->mappedPriceForPriceMap($priceMap)?->value ?? Money::zero($priceMap->currency->value);

        /** @var Schema $schema */
        foreach ($product->schemas as $schema) {
            $schema_max = Money::zero($priceMap->currency->value);
            $most_valuable_option = null;

            foreach ($schema->options as $option) {
                $option_price = $option->getPriceForPriceMap($priceMap);
                $schema_max = Money::max($option_price, $schema_max);
                if ($option_price->isEqualTo($schema_max)) {
                    $most_valuable_option = $option;
                }
            }

            if ($most_valuable_option !== null) {
                $max = $max->plus($schema->getPrice($most_valuable_option->id, $product->schemas->toArray(), $priceMap));
            }
        }

        return $max;
    }

    public function updateProductIndex(Product $product): void
    {
        $product = $this->prepareProductSearchValues($product);
        $product->save();
    }

    /**
     * @return Collection<int,Discount>
     */
    public function productSales(Product $product): Collection
    {
        return $product->sales()->with('amounts', 'metadata', 'metadataPrivate')->get();
    }

    private function setup(Product $product, ProductCreateDto|ProductUpdateDto $dto): Product
    {
        if (!($dto->schemas instanceof Optional)) {
            $this->schemaService->sync($product, $dto->schemas);
        }

        if (!($dto->sets instanceof Optional)) {
            $new = array_diff($dto->sets, $product->sets->pluck('id')->toArray());
            $product->sets()->sync($dto->sets);
            $product->ancestorSets()->sync(array_merge($dto->sets, $this->productSetService->getAllAncestorsIds($dto->sets)));
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
            $product->loadMissing(['productAttributes' => fn(Builder|HasMany $query) => $query->whereIn('attribute_id', array_keys($dto->attributes))]);
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
            $this->priceMapService->updateProductPricesForDefaultMaps($product, $dto->prices_base);
        }

        $this->setBannerMedia($product, $dto);

        $this->updateMinPrices($product);

        $availability = $this->availabilityService->getCalculateProductAvailability($product);
        $product->quantity = $availability['quantity'];
        $product->available = $availability['available'];
        $product->shipping_time = $availability['shipping_time'];
        $product->shipping_date = $availability['shipping_date'];

        return $product;
    }

    private function assignItems(Product $product, ?array $items): void
    {
        $product->items()->sync(collect($items)->mapWithKeys(fn(array $item): array => [$item['id'] => ['required_quantity' => $item['required_quantity']]]));
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
        if (!($dto->banner instanceof Optional)) {
            if ($dto->banner) {
                /** @var ProductBannerMedia|null $bannerMedia */
                $bannerMedia = $product->banner;
                if (!$bannerMedia) {
                    /** @var ProductBannerMedia $bannerMedia */
                    $bannerMedia = ProductBannerMedia::create($dto->banner->toArray());
                } else {
                    $bannerMedia->fill($dto->banner->toArray());
                }
                if (!($dto->banner->translations instanceof Optional)) {
                    foreach ($dto->banner->translations as $lang => $translation) {
                        $bannerMedia->setLocale($lang)->fill($translation);
                    }
                }
                $bannerMedia->save();

                if (!($dto->banner->media instanceof Optional)) {
                    $medias = [];
                    foreach ($dto->banner->media as $media) {
                        $medias[$media->media] = ['min_screen_width' => $media->min_screen_width];
                    }
                    $bannerMedia->media()->sync($medias);
                }
                $product->banner_media_id = $bannerMedia->getKey();
            } else {
                $product->banner()->delete();
            }
        }
    }
}
