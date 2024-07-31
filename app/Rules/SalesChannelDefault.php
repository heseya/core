<?php

namespace App\Rules;

use App\Enums\ExceptionsEnums\Exceptions;
use Closure;
use Domain\SalesChannel\Enums\SalesChannelActivityType;
use Domain\SalesChannel\Enums\SalesChannelStatus;
use Domain\SalesChannel\Models\SalesChannel;
use Illuminate\Contracts\Validation\DataAwareRule;
use Illuminate\Contracts\Validation\ValidationRule;
use Spatie\LaravelData\Optional;

class SalesChannelDefault implements DataAwareRule, ValidationRule
{
    private array $data = [];

    /**
     * Run the validation rule.
     *
     * @param Closure(string): \Illuminate\Translation\PotentiallyTranslatedString $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        /** @var SalesChannel|null $channel */
        $channel = request()->route('sales_channel');

        if ($value) {
            if ($channel) {
                $status = !isset($this->data['status']) || $this->data['status'] instanceof Optional ? $channel->status->value : $this->data['status'];
                $activity = !isset($this->data['activity']) || $this->data['activity'] instanceof Optional ? $channel->activity->value : $this->data['activity'];
            } else {
                $status = $this->data['status'];
                $activity = $this->data['activity'];
            }

            if (!$this->canBeDefault($status, $activity)) {
                $fail(Exceptions::CLIENT_SALES_CHANNEL_DEFAULT_ACTIVE_AND_PUBLIC->value);
            }
        } else {
            if ($channel && $channel->default) {
                $fail(Exceptions::CLIENT_SALES_CHANNEL_DEFAULT->value);
            }
        }
    }

    public function setData(array $data): self
    {
        $this->data = $data;

        return $this;
    }

    private function canBeDefault(string $status, string $activity): bool
    {
        return $status === SalesChannelStatus::PUBLIC->value && $activity === SalesChannelActivityType::ACTIVE->value;
    }
}
