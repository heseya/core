<?php

declare(strict_types=1);

namespace Domain\Order\Resources;

use App\Http\Resources\Resource;
use App\Models\Discount;
use App\Models\OrderDiscount;
use Illuminate\Http\Request;

/**
 * @property Discount $resource
 */
final class OrderDiscountResource extends Resource
{
    /**
     * @return array<string, mixed>
     */
    public function base(Request $request): array
    {
        $pivot = $this->resource->order_discount;
        assert($pivot instanceof OrderDiscount);

        $amount = $pivot->amount;
        $applied = $pivot->applied;

        return [
            'discount_id' => $this->resource->id,
            'name' => $pivot->name,
            'code' => $this->resource->code,
            'percentage' => $pivot->percentage,
            'amount' => $amount?->getAmount(),
            'target_type' => $pivot->target_type,
            'applied_discount' => $applied?->getAmount(),
        ];
    }
}
