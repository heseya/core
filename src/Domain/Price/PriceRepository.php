<?php

declare(strict_types=1);

namespace Domain\Price;

use App\Models\DiscountCondition;
use App\Models\Option;
use App\Models\Price;
use App\Models\Product;
use App\Models\Schema;
use Domain\Currency\Currency;
use Domain\Price\Dtos\PriceDto;
use Domain\Price\Enums\DiscountConditionPriceType;
use Domain\Price\Enums\OptionPriceType;
use Domain\Price\Enums\ProductPriceType;
use Domain\Price\Enums\SchemaPriceType;
use Domain\SalesChannel\Models\SalesChannel;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Arr;
use Ramsey\Uuid\Uuid;
use Spatie\LaravelData\DataCollection;
use Support\Dtos\ModelIdentityDto;

final class PriceRepository
{
    /**
     * @param array<ProductPriceType|SchemaPriceType|OptionPriceType|DiscountConditionPriceType|string,PriceDto[]> $priceMatrix
     */
    public function setModelPrices(DiscountCondition|ModelIdentityDto|Option|Product|Schema $model, array $priceMatrix): void
    {
        $rows = [];

        foreach ($priceMatrix as $type => $prices) {
            $prices = new DataCollection(PriceDto::class, $prices);
            foreach ($prices as $price) {
                /** @var PriceDto $price */
                $row = [
                    'id' => Uuid::uuid4(),
                    'model_id' => $model->getKey(),
                    'model_type' => $model->getMorphClass(),
                    'price_type' => $type,
                    'currency' => $price->value->getCurrency()->getCurrencyCode(),
                    'value' => (string) $price->value->getMinorAmount(),
                    'is_net' => false,
                    'sales_channel_id' => $price->sales_channel_id,
                ];

                if ($row['sales_channel_id'] === null && ($model instanceof Product || ($model instanceof ModelIdentityDto && $model->class === Product::class))) {
                    $model = $model instanceof Product ? $model : $model->getInstance();
                    if ($model instanceof Product) {
                        $row['sales_channel_id'] = $model->publicSalesChannels->first()?->id;
                    }
                }

                $rows[] = $row;
            }
        }

        Price::query()->upsert(
            $rows,
            ['model_id', 'price_type', 'currency', 'sales_channel_id'],
            ['value', 'is_net'],
        );
    }

    /**
     * @param array<integer,ProductPriceType|SchemaPriceType|OptionPriceType|DiscountConditionPriceType|string> $priceTypes
     *
     * @return Collection<int, Price>
     */
    public function getModelPrices(DiscountCondition|ModelIdentityDto|Option|Product|Schema $model, array $priceTypes, ?Currency $currency = null, ?SalesChannel $salesChannel = null): Collection
    {
        /** @var Builder<Price> $query */
        $query = Price::query()
            ->where('model_id', $model->getKey())
            ->where('model_type', $model->getMorphClass())
            ->whereIn('price_type', Arr::map($priceTypes, fn (DiscountConditionPriceType|OptionPriceType|ProductPriceType|SchemaPriceType|string $item) => is_string($item) ? $item : $item->value));

        if ($currency !== null) {
            $query->where('currency', $currency->value);
        }

        if ($salesChannel !== null) {
            $query->where('sales_channel_id', $salesChannel->getKey());
        }

        return $query->get();
    }
}
