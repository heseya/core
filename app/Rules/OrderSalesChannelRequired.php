<?php

namespace App\Rules;

use App\Enums\ExceptionsEnums\Exceptions;
use Closure;
use Domain\Organization\Models\Organization;
use Domain\SalesChannel\Enums\SalesChannelActivityType;
use Domain\SalesChannel\Enums\SalesChannelStatus;
use Domain\SalesChannel\Models\SalesChannel;
use Illuminate\Contracts\Validation\DataAwareRule;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\Gate;

class OrderSalesChannelRequired implements DataAwareRule, ValidationRule
{
    private array $data = [];

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (isset($this->data['organization_id'])) {
            /** @var Organization $organization */
            $organization = Organization::query()->where('id', '=', $this->data['organization_id'])->firstOrFail();

            if ($organization->sales_channel_id !== $value) {
                $fail(Exceptions::CLIENT_SALES_CHANNEL_IN_ORGANIZATION->value);
            }
        } else {
            /** @var SalesChannel $saleChannel */
            $saleChannel = SalesChannel::query()->where('id', '=', $value)->first();
            if ($saleChannel->status === SalesChannelStatus::PRIVATE && Gate::denies('sales_channels.show_hidden')) {
                $fail(Exceptions::CLIENT_SALES_CHANNEL_PRIVATE->value);

                return;
            }

            if ($saleChannel->activity === SalesChannelActivityType::INACTIVE) {
                $fail(Exceptions::CLIENT_SALES_CHANNEL_INACTIVE->value);
            }
        }
    }

    public function setData(array $data): self
    {
        $this->data = $data;

        return $this;
    }
}
