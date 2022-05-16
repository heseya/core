<?php

namespace App\Rules;

use App\Models\Item;
use App\Services\Contracts\DepositServiceContract;
use Illuminate\Contracts\Validation\Rule;
use Illuminate\Support\Facades\App;

class UnlimitedShippingTime implements Rule
{
    public function __construct(private Item $item)
    {
    }

    /**
     * Determine if the validation rule passes.
     *
     * @param  string  $attribute
     * @param  mixed  $value
     */
    public function passes($attribute, $value): bool
    {
        $depositService = App::make(DepositServiceContract::class);
        $depositsTime = $depositService->getDepositsGroupByTimeForItem($this->item, 'DESC');

        return !isset($depositsTime[0]) ||
            $this->item->unlimited_stock_shipping_time >= $depositsTime[0]['shipping_time'];
    }

    public function message(): string
    {
        return 'Unlimited stock shipping time cannot by lesser then shipping time.';
    }
}
