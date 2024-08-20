<?php

declare(strict_types=1);

namespace Domain\Price;

use App\Models\DiscountCondition;
use App\Models\Price;
use App\Models\Product;
use Domain\Currency\Currency;
use Domain\Price\Dtos\PriceDto;
use Domain\Price\Enums\DiscountConditionPriceType;
use Domain\Price\Enums\OptionPriceType;
use Domain\Price\Enums\ProductPriceType;
use Domain\PriceMap\PriceMap;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Arr;
use Ramsey\Uuid\Uuid;
use Spatie\LaravelData\DataCollection;
use Support\Dtos\ModelIdentityDto;

final class PriceRepository
{
    /**
     * @param array<ProductPriceType|OptionPriceType|DiscountConditionPriceType|string,PriceDto[]> $priceMatrix
     */
    public function setModelPrices(DiscountCondition|ModelIdentityDto|Product $model, array $priceMatrix, PriceMap|string|null $priceMap = null): void
    {
        $rows = [];

        $currency = Currency::DEFAULT;
        $priceMapId = $currency->getDefaultPriceMapId();

        if (is_string($priceMap)) {
            $priceMap = PriceMap::find($priceMap);
        }
        if ($priceMap instanceof PriceMap) {
            $currency = $priceMap->currency;
            $priceMapId = $priceMap->id;
        }

        foreach ($priceMatrix as $type => $prices) {
            $prices = new DataCollection(PriceDto::class, $prices);
            foreach ($prices as $price) {
                if ($priceMap === null) {
                    $currency = Currency::from($price->value->getCurrency()->getCurrencyCode());
                    $priceMapId = $currency->getDefaultPriceMapId();
                }
                $rows[] = [
                    'id' => Uuid::uuid4(),
                    'model_id' => $model instanceof ModelIdentityDto ? $model->uuid : $model->getKey(),
                    'model_type' => $model instanceof ModelIdentityDto ? $model->class : $model->getMorphClass(),
                    'price_type' => $type,
                    'currency' => $currency->value,
                    'price_map_id' => $priceMapId,
                    'value' => (string) $price->value->getMinorAmount(),
                    'is_net' => false,
                ];
            }
        }

        Price::query()->upsert(
            $rows,
            ['model_id', 'price_type', 'price_map_id'],
            ['value', 'is_net'],
        );
    }

    /**
     * @param array<integer,ProductPriceType|DiscountConditionPriceType|string> $priceTypes
     *
     * @return Collection<int, Price>
     */
    public function getModelPrices(DiscountCondition|ModelIdentityDto|Product $model, array $priceTypes, Currency|PriceMap|null $priceMap = null): Collection
    {
        /** @var Builder<Price> $query */
        $query = Price::query()
            ->where('model_id', $model instanceof ModelIdentityDto ? $model->uuid : $model->getKey())
            ->where('model_type', $model instanceof ModelIdentityDto ? $model->class : $model->getMorphClass())
            ->whereIn('price_type', Arr::map($priceTypes, fn (DiscountConditionPriceType|ProductPriceType|string $item) => is_string($item) ? $item : $item->value));

        if ($priceMap instanceof PriceMap) {
            $query->where('price_map_id', $priceMap->getKey());
        } elseif ($priceMap instanceof Currency) {
            $query->where('price_map_id', $priceMap->getDefaultPriceMapId());
        } else {
            $query->whereIn('price_map_id', Currency::defaultPriceMapIds());
        }

        return $query->get();
    }
}
