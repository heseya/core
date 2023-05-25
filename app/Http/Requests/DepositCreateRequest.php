<?php

namespace App\Http\Requests;

use App\Models\Item;
use App\Rules\Decimal;
use App\Rules\ShippingDate;
use App\Rules\ShippingTime;
use Illuminate\Foundation\Http\FormRequest;

class DepositCreateRequest extends FormRequest
{
    public function rules(): array
    {
        /** @var Item $item */
        $item = $this->route('item');

        return [
            'quantity' => ['required', ...Decimal::defaults()],
            'shipping_time' => [
                'nullable',
                'integer',
                'required_without:shipping_date',
                'prohibits:shipping_date',
                new ShippingTime($item),
            ],
            'shipping_date' => [
                'nullable',
                'date',
                'required_without:shipping_time',
                'prohibits:shipping_time',
                new ShippingDate($item),
            ],
            'from_unlimited' => ['boolean'],
        ];
    }
}
