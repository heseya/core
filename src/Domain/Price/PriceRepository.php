<?php

declare(strict_types=1);

namespace Domain\Price;

use App\Models\DiscountCondition;
use App\Models\Price;
use App\Models\Product;
use Domain\Currency\Currency;
use Domain\Price\Dtos\DiscountPricesDto;
use Domain\Price\Dtos\DiscountPricesDtoCollection;
use Domain\Price\Dtos\PriceDto;
use Domain\Price\Dtos\ProductCachedPriceDto;
use Domain\Price\Dtos\ProductCachedPricesDto;
use Domain\Price\Dtos\ProductCachedPricesDtoCollection;
use Domain\Price\Enums\DiscountConditionPriceType;
use Domain\Price\Enums\ProductPriceType;
use Domain\SalesChannel\Models\SalesChannel;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;
use Ramsey\Uuid\Uuid;
use Support\Dtos\ModelIdentityDto;

final class PriceRepository
{
    /**
     * @param ProductCachedPricesDtoCollection|ProductCachedPricesDto[]|array<value-of<ProductPriceType>,ProductCachedPriceDto[]> $priceMatrix
     */
    public function setCachedProductPrices(ModelIdentityDto|Product $product, array|ProductCachedPricesDtoCollection $priceMatrix): void
    {
        $priceMatrix = new ProductCachedPricesDtoCollection(items: $priceMatrix);

        $rows = [];
        $salesChannelsToPreserve = [];

        /** @var ProductCachedPricesDto $pricesCollection */
        foreach ($priceMatrix as $pricesCollection) {
            foreach ($pricesCollection->prices as $price) {
                $salesChannel = Cache::driver('array')->rememberForever('sales_channel_' . $price->sales_channel_id, fn () => SalesChannel::with('priceMap')->findOrFail($price->sales_channel_id));

                $rows[] = [
                    'id' => Uuid::uuid4(),
                    'model_id' => $product->getKey(),
                    'model_type' => $product->getMorphClass(),
                    'price_type' => $pricesCollection->type,
                    'currency' => $salesChannel->priceMap?->currency ?? $price->currency,
                    'sales_channel_id' => $salesChannel->id,
                    'price_map_id' => null,
                    'is_net' => $salesChannel->priceMap?->is_net ?? false,
                    'value' => (string) $price->gross->getMinorAmount(),
                    'net' => (string) $price->net->getMinorAmount(),
                    'gross' => (string) $price->gross->getMinorAmount(),
                ];

                $salesChannelsToPreserve[$salesChannel->getKey()] = [
                    'sales_channel_id' => $salesChannel->getKey(),
                    'currency' => $salesChannel->priceMap?->currency ?? $price->currency,
                ];
            }
        }

        Price::query()->upsert(
            $rows,
            ['model_id', 'price_type', 'currency', 'sales_channel_id'],
            ['value', 'net', 'gross', 'is_net'],
        );

        foreach ($salesChannelsToPreserve as $salesChannelToPreserve) {
            Price::query()
                ->where('model_id', $product->getKey())
                ->where('sales_channel_id', $salesChannelToPreserve['sales_channel_id'])
                ->whereNot('currency', $salesChannelToPreserve['currency'])
                ->delete();
        }
    }

    /**
     * @param DiscountPricesDtoCollection|DiscountPricesDto[]|array<value-of<DiscountConditionPriceType>,PriceDto[]> $priceMatrix
     */
    public function setDiscountConditionPrices(DiscountCondition|ModelIdentityDto $discountCondition, array|DiscountPricesDtoCollection $priceMatrix): void
    {
        $priceMatrix = new DiscountPricesDtoCollection(items: $priceMatrix);

        $rows = [];

        /** @var DiscountPricesDto $pricesCollection */
        foreach ($priceMatrix as $pricesCollection) {
            foreach ($pricesCollection->prices as $price) {
                $rows[] = [
                    'id' => Uuid::uuid4(),
                    'model_id' => $discountCondition->getKey(),
                    'model_type' => $discountCondition->getMorphClass(),
                    'price_type' => $pricesCollection->type,
                    'currency' => $price->currency,
                    'price_map_id' => $price->currency->getDefaultPriceMapId(), // required for unique index to work correctly
                    'is_net' => $price->is_net,
                    'value' => (string) $price->value->getMinorAmount(),
                    'net' => $price->is_net ? (string) $price->value->getMinorAmount() : 0,
                    'gross' => $price->is_net ? 0 : (string) $price->value->getMinorAmount(),
                ];
            }
        }

        Price::query()->upsert(
            $rows,
            ['model_id', 'price_type', 'currency', 'price_map_id'],
            ['value', 'net', 'gross', 'is_net'],
        );
    }

    /**
     * @param ProductPriceType[]|DiscountConditionPriceType[]|string[] $priceTypes
     *
     * @return Collection<int,Price>
     */
    public function getModelPrices(DiscountCondition|ModelIdentityDto|Product $model, array $priceTypes, Currency|SalesChannel|null $filter = null): Collection
    {
        /** @var Builder<Price> $query */
        $query = Price::query()
            ->where('model_id', $model->getKey())
            ->where('model_type', $model->getMorphClass())
            ->whereIn('price_type', Arr::map($priceTypes, fn (DiscountConditionPriceType|ProductPriceType|string $item) => is_string($item) ? $item : $item->value));

        if ($model->getMorphClass() === (new DiscountCondition())->getMorphClass()) {
            $query = match (true) {
                $filter instanceof SalesChannel => $filter->priceMap ? $query->where('currency', $filter->priceMap->currency->value) : $query,
                $filter instanceof Currency => $query->where('currency', $filter->value),
                default => $query,
            };
        } else {
            $query = match (true) {
                $filter instanceof SalesChannel => $query->where('sales_channel_id', $filter->getKey()),
                $filter instanceof Currency => $query->whereHas('salesChannel', fn (Builder $query) => $query->where('price_map_id', $filter->getDefaultPriceMapId())),
                default => $query->whereHas('salesChannel', fn (Builder $query) => $query->whereIn('price_map_id', Currency::defaultPriceMapIds())),
            };
        }

        return $query->get();
    }
}
