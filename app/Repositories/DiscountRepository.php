<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Enums\ExceptionsEnums\Exceptions;
use App\Exceptions\ServerException;
use App\Models\Discount;
use App\Models\Price;
use Domain\Currency\Currency;
use Domain\Price\Dtos\PriceDto;
use Domain\Price\Enums\DiscountPriceType;
use Illuminate\Support\Facades\Cache;
use Ramsey\Uuid\Uuid;

class DiscountRepository
{
    private static function getCacheKey(string $discountId, ?Currency $currency = null): string
    {
        return 'discount_' . $discountId . '_' . ($currency?->value ?? 'all');
    }

    /**
     * @param PriceDto[] $amounts
     */
    public static function setDiscountAmounts(string $discountId, array $amounts): void
    {
        $rows = [];

        foreach ($amounts as $amount) {
            $rows[] = [
                'id' => Uuid::uuid4(),
                'model_id' => $discountId,
                'model_type' => (new Discount())->getMorphClass(),
                'price_type' => DiscountPriceType::AMOUNT->value,
                'price_map_id' => Currency::from($amount->value->getCurrency()->getCurrencyCode())->getDefaultPriceMapId(), // required for unique index to work correctly
                'currency' => $amount->value->getCurrency()->getCurrencyCode(),
                'value' => $amount->value->getMinorAmount(),
                'gross' => $amount->value->getMinorAmount(),
                'is_net' => true,
            ];
            Cache::driver('array')->forget(self::getCacheKey($discountId, Currency::from($amount->value->getCurrency()->getCurrencyCode())));
        }

        Price::query()->upsert(
            $rows,
            ['model_id', 'price_type', 'currency', 'price_map_id'],
            ['value', 'gross', 'is_net'],
        );
    }

    /**
     * @return PriceDto[]
     *
     * @throws ServerException
     */
    public static function getDiscountAmounts(string $discountId, ?Currency $currency = null): array
    {
        return Cache::driver('array')->rememberForever(self::getCacheKey($discountId, $currency), function () use ($discountId, $currency) {
            $amounts = Price::query()
                ->where('model_id', $discountId)
                ->where('price_type', DiscountPriceType::AMOUNT->value);

            if ($currency !== null) {
                $amounts = $amounts->where('currency', $currency->value);
            }

            $amountDtos = $amounts->get()->map(fn (Price $price) => PriceDto::fromModel($price));

            if ($amountDtos->isEmpty()) {
                throw new ServerException(Exceptions::SERVER_NO_PRICE_MATCHING_CRITERIA);
            }

            return $amountDtos->reduce(function (array $carry, PriceDto $dto) {
                $carry[] = $dto;

                return $carry;
            }, []);
        });
    }
}
