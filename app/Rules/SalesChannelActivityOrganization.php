<?php

namespace App\Rules;

use App\Enums\ExceptionsEnums\Exceptions;
use Closure;
use Domain\SalesChannel\Enums\SalesChannelActivityType;
use Domain\SalesChannel\Models\SalesChannel;
use Illuminate\Contracts\Validation\ValidationRule;

class SalesChannelActivityOrganization implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param Closure(string): \Illuminate\Translation\PotentiallyTranslatedString $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        /** @var SalesChannel|null $channel */
        $channel = request()->route('sales_channel');

        if ($channel) {
            if ($value === SalesChannelActivityType::INACTIVE->value && $channel->organizations()->count() > 0) {
                $fail(Exceptions::CLIENT_SALES_CHANNEL_ORGANIZATION_ACTIVE->value);
            }
        }
    }
}
