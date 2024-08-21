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
use Illuminate\Support\Facades\Cache;

final readonly class SalesChannelService
{
    public function __construct(private readonly SalesChannelRepository $repository) {}

    public function getCurrentSalesChannel(): SalesChannel
    {
        return Cache::driver('array')->rememberForever(
            'current_sales_channel',
            fn () => request()->header('X-Sales-Channel')
                ? $this->repository->getOne(request()->header('X-Sales-Channel'))
                : $this->repository->getDefault(),
        );
    }

    /**
     * @throws MathException
     */
    public function getVatRate(SalesChannel|string $sales_channel_id): BigDecimal
    {
        if (is_string($sales_channel_id)) {
            /** @var SalesChannel $sales_channel */
            $sales_channel = SalesChannel::query()
                ->where('id', '=', $sales_channel_id)
                ->firstOr(fn () => throw new ClientException(Exceptions::CLIENT_SALES_CHANNEL_NOT_FOUND));
        } else {
            $sales_channel = $sales_channel_id;
        }

        return BigDecimal::of($sales_channel->vat_rate)->multipliedBy(0.01);
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
    public function addVatString(string $decimal, BigDecimal $vat_rate): string
    {
        return (string) $vat_rate->plus(1)->multipliedBy($decimal)->toScale(0, RoundingMode::HALF_EVEN)->toInt();
    }
}
