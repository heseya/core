<?php

declare(strict_types=1);

namespace Domain\SalesChannel;

use App\Enums\ExceptionsEnums\Exceptions;
use App\Exceptions\ClientException;
use Brick\Math\BigDecimal;
use Brick\Math\Exception\MathException;
use Domain\SalesChannel\Models\SalesChannel;

final readonly class SalesChannelService
{
    /**
     * @throws MathException
     */
    public function getVatRate(string $sales_channel_id): BigDecimal
    {
        /** @var SalesChannel $sales_channel */
        $sales_channel = SalesChannel::query()
            ->where('id', '=', $sales_channel_id)
            ->firstOr(fn () => throw new ClientException(Exceptions::CLIENT_SALES_CHANNEL_NOT_FOUND));

        return BigDecimal::of($sales_channel->vat_rate)->multipliedBy(0.01);
    }

    public function addVat(float $price, BigDecimal $vat_rate): float
    {
        // change to multipliedBy when price will be Money
        return $price + ($price * $vat_rate->toFloat());
    }
}
