<?php

namespace App\Rules;

use App\Models\Item;
use App\Services\Contracts\DepositServiceContract;
use Carbon\Carbon;
use Illuminate\Contracts\Validation\Rule;
use Illuminate\Support\Facades\App;

class UnlimitedShippingDate implements Rule
{
    public function __construct(private Item $item) {}

    /**
     * Determine if the validation rule passes.
     *
     * @param string $attribute
     */
    public function passes($attribute, $value): bool
    {
        if ($value === null) {
            return true;
        }
        $shippingDate = Carbon::parse($value);

        $depositService = App::make(DepositServiceContract::class);
        $deposits = $depositService->getDepositsGroupByDateForItem($this->item, 'DESC');

        if (!isset($deposits[0])) {
            return true;
        }

        $depositsDate = Carbon::parse($deposits[0]['shipping_date']);

        return !$shippingDate->isBefore($depositsDate);
    }

    public function message(): string
    {
        return 'Unlimited stock shipping date cannot by lesser then shipping date.';
    }
}
