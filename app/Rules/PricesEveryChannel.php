<?php

namespace App\Rules;

use App\Models\Product;
use Closure;
use Domain\Product\Enums\ProductSalesChannelStatus;
use Illuminate\Contracts\Validation\DataAwareRule;
use Illuminate\Contracts\Validation\ValidationRule;

class PricesEveryChannel implements DataAwareRule, ValidationRule
{
    public function __construct(
        private string $sales_channel_field = 'sales_channel_id',
        private string $channel_list_field = 'sales_channels',
        private Product|null $product = null,
        private array $data = [],
    ) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (!is_array($value)) {
            $fail('The :attribute is not an array');

            return;
        }

        $sales_channel_ids = [];

        foreach ($value as $price) {
            $sales_channel_id = $price[$this->sales_channel_field] ?? '';

            if (!in_array($sales_channel_id, $sales_channel_ids)) {
                $sales_channel_ids[] = $sales_channel_id;
            }
        }

        if (isset($this->data[$this->channel_list_field])) {
            foreach ($this->data[$this->channel_list_field] as $channel) {
                if ($channel['availability_status'] !== ProductSalesChannelStatus::DISABLED->value && !in_array($channel['id'], $sales_channel_ids)) {
                    $fail("The :attribute has no price for channel {$channel->id}");
                }
            }
        } elseif (!empty($this->product)) {
            foreach ($this->product->salesChannels as $channel) {
                if ($channel->pivot->availability_status !== ProductSalesChannelStatus::DISABLED->value && !in_array($channel->id, $sales_channel_ids)) {
                    $fail("The :attribute has no price for channel {$channel->id}");
                }
            }
        }
    }

    public function setData($data): self|static
    {
        $this->data = $data;

        return $this;
    }
}
