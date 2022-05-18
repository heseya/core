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
                'prohibited_unless:shipping_date,null',
                new ShippingTime($item),
            ],
            'shipping_date' => [
                'nullable',
                'date',
                'prohibited_unless:shipping_time,null',
                new ShippingDate($item),
            ],
        ];
    }
}
