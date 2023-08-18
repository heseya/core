<?php

declare(strict_types=1);

namespace Domain\SalesChannel;

use App\Enums\ExceptionsEnums\Exceptions;
use App\Exceptions\ClientException;
use Illuminate\Support\Facades\Cache;

final readonly class SalesChannelService
{
    /**
     * @throws ClientException
     */
    public function addVat(float $price): float
    {
        $vat_rate = Cache::get('vat_rate');

        if (!is_float($vat_rate)) {
            throw new ClientException(Exceptions::CLIENT_SALES_CHANNEL_NOT_FOUND);
        }

        return $price + ($price * $vat_rate);
    }
}
