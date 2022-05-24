<?php

namespace App\Rules;

use App\Models\Item;
use App\Services\Contracts\DepositServiceContract;
use Illuminate\Contracts\Validation\Rule;
use Illuminate\Support\Facades\App;

class UnlimitedShippingDate implements Rule
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
        if (is_null($value)) {
            return true;
        }
        $depositService = App::make(DepositServiceContract::class);
        $depositsDate = $depositService->getDepositsGroupByDateForItem($this->item, 'DESC');

        return !isset($depositsDate[0]) ||
            $value >= $depositsDate[0]['shipping_date'];
    }

    public function message(): string
    {
        return 'Unlimited stock shipping date cannot by lesser then shipping date.';
    }
}
