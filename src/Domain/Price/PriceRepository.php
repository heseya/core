<?php

declare(strict_types=1);

namespace Domain\Price;

use App\Enums\Product\ProductPriceType;
use App\Models\Option;
use App\Models\Price;
use App\Models\Product;
use App\Models\Schema;
use Domain\Currency\Currency;
use Domain\Price\Dtos\PriceDto;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Arr;
use Ramsey\Uuid\Uuid;
use Spatie\LaravelData\DataCollection;
use Support\Dtos\ModelIdentityDto;

final class PriceRepository
{
    /**
     * @param PriceDto[][] $priceMatrix
     */
    public function setModelPrices(ModelIdentityDto|Option|Product|Schema $model, array $priceMatrix): void
    {
        $rows = [];

        foreach ($priceMatrix as $type => $prices) {
            $prices = new DataCollection(PriceDto::class, $prices);
            foreach ($prices as $price) {
                $rows[] = [
                    'id' => Uuid::uuid4(),
                    'model_id' => $model instanceof ModelIdentityDto ? $model->uuid : $model->getKey(),
                    'model_type' => $model instanceof ModelIdentityDto ? $model->class : $model->getMorphClass(),
                    'price_type' => $type,
                    'currency' => $price->value->getCurrency()->getCurrencyCode(),
                    'value' => (string) $price->value->getMinorAmount(),
                    'is_net' => false,
                ];
            }
        }

        Price::query()->upsert(
            $rows,
            ['model_id', 'price_type', 'currency'],
            ['value', 'is_net'],
        );
    }

    /**
     * @param ProductPriceType[] $priceTypes
     *
     * @return Collection<int, Price>
     */
    public function getModelPrices(ModelIdentityDto|Option|Product|Schema $model, array $priceTypes, ?Currency $currency = null): Collection
    {
        /** @var Builder<Price> $query */
        $query = Price::query()
            ->where('model_id', $model instanceof ModelIdentityDto ? $model->uuid : $model->getKey())
            ->where('model_type', $model instanceof ModelIdentityDto ? $model->class : $model::class)
            ->whereIn('price_type', Arr::map($priceTypes, fn (ProductPriceType|string $item) => is_string($item) ? $item : $item->value));

        if ($currency !== null) {
            $query->where('currency', $currency->value);
        }

        return $query->get();
    }
}
