<?php

namespace App\Rules;

use App\Models\Order;
use Illuminate\Contracts\Validation\Rule;

class DocumentsBelongToOrder implements Rule
{
    private string $error;

    public function passes($attribute, $value): bool
    {
        /** @var Order $order */
        $order = request()->route('order');

        $this->error = $value;

        return $order->documents()->wherePivot('id', $value)->exists();
    }

    public function message(): string
    {
        return 'Document with id ' . $this->error . ' doesn\'t belong to this order.';
    }
}
