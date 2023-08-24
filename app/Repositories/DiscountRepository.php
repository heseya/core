<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Enums\ExceptionsEnums\Exceptions;
use App\Enums\RelationAlias;
use App\Exceptions\ServerException;
use App\Models\Price;
use Domain\Currency\Currency;
use Domain\Price\Dtos\PriceDto;
use Ramsey\Uuid\Uuid;

class DiscountRepository
{
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
                'model_type' => RelationAlias::DISCOUNT->value,
                'price_type' => 'amount',
                'currency' => $amount->value->getCurrency()->getCurrencyCode(),
                'value' => $amount->value->getMinorAmount(),
                'is_net' => false,
            ];
        }

        Price::query()->upsert(
            $rows,
            ['model_id', 'price_type', 'currency'],
            ['value', 'is_net'],
        );
    }

    /**
     * @return PriceDto[]
     *
     * @throws ServerException
     */
    public static function getDiscountAmounts(string $discountId, ?Currency $currency = null): array
    {
        $amounts = Price::query()
            ->where('model_id', $discountId)
            ->where('price_type', 'amount');

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
    }
}
