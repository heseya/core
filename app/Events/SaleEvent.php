<?php

namespace App\Events;

use App\Http\Resources\SaleResource;
use App\Models\Discount;

abstract class SaleEvent extends WebHookEvent
{
    protected Discount $sale;

    public function __construct(Discount $sale)
    {
        parent::__construct();
        $this->sale = $sale;
    }

    public function getDataContent(): array
    {
        return SaleResource::make($this->sale)->resolve();
    }

    public function getDataType(): string
    {
        return $this->getModelClass($this->sale);
    }
}
