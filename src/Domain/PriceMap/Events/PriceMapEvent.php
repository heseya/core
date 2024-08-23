<?php

declare(strict_types=1);

namespace Domain\PriceMap\Events;

use App\Events\WebHookEvent;
use Domain\PriceMap\PriceMap;
use Illuminate\Support\Str;

abstract class PriceMapEvent extends WebHookEvent
{
    protected PriceMap $priceMap;

    public function __construct(PriceMap $priceMap)
    {
        parent::__construct();
        $this->priceMap = $priceMap;
    }

    /**
     * @return array<string,mixed>
     */
    public function getDataContent(): array
    {
        return $this->priceMap->getData()->toArray();
    }

    public function getDataType(): string
    {
        return Str::remove('Domain\\PriceMap\\', $this->priceMap::class);
    }
}
