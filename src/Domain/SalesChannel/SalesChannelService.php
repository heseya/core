<?php

declare(strict_types=1);

namespace Domain\SalesChannel;

use App\Enums\ExceptionsEnums\Exceptions;
use App\Exceptions\ClientException;
use Brick\Math\BigDecimal;
use Brick\Math\Exception\MathException;
use Brick\Math\RoundingMode;
use Brick\Money\Money;
use Domain\SalesChannel\Models\SalesChannel;
use Exception;
use Illuminate\Support\Facades\Cache;

final readonly class SalesChannelService
{
    public function __construct(private readonly SalesChannelRepository $repository) {}

    public function getCurrentRequestSalesChannel(): SalesChannel
    {
        return Cache::driver('array')->rememberForever(
            'current_sales_channel',
            function () {
                try {
                    return request()->header('X-Sales-Channel') === null ? $this->repository->getDefault() : $this->repository->getOne(request()->header('X-Sales-Channel'));
                } catch (Exception $ex) {
                    throw new ClientException(Exceptions::CLIENT_SALES_CHANNEL_NOT_FOUND, $ex);
                }
            },
        );
    }

    /**
     * @throws MathException
     */
    public function getVatRate(SalesChannel|string $sales_channel_id): BigDecimal
    {
        if (is_string($sales_channel_id)) {
            try {
                $sales_channel = $this->repository->getOne($sales_channel_id);
            } catch (Exception $ex) {
                throw new ClientException(Exceptions::CLIENT_SALES_CHANNEL_NOT_FOUND, $ex);
            }
        } else {
            $sales_channel = $sales_channel_id;
        }

        return BigDecimal::of($sales_channel->vat_rate)->multipliedBy(0.01)->abs();
    }

    /**
     * @throws MathException
     */
    public function addVat(Money $price, BigDecimal $vat_rate): Money
    {
        return $price->multipliedBy($vat_rate->plus(1), RoundingMode::HALF_EVEN);
    }

    /**
     * @throws MathException
     */
    public function removeVat(Money $price, BigDecimal $vat_rate): Money
    {
        return $price->dividedBy($vat_rate->plus(1), RoundingMode::HALF_EVEN);
    }
}
