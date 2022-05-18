<?php

namespace App\Http\Requests;

use App\Models\Item;
use App\Rules\ShippingDate;
use App\Rules\ShippingTime;
use App\Rules\UnlimitedShippingDate;
use App\Rules\UnlimitedShippingTime;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ItemUpdateRequest extends FormRequest
{
    public function rules(): array
    {
        /** @var Item $item */
        $item = $this->route('item');

        return [
            'name' => ['string', 'max:255'],
            'sku' => [
                'string',
                'max:255',
                Rule::unique('items')->ignore($item->sku, 'sku'),
            ],
            'unlimited_stock_shipping_time' => [
                'nullable',
                'integer',
                'prohibited_unless:unlimited_stock_shipping_date,null',
                new UnlimitedShippingTime($item),
            ],
            'unlimited_stock_shipping_date' => [
                'nullable',
                'date',
                'prohibited_unless:unlimited_stock_shipping_time,null',
                new UnlimitedShippingDate($item),
            ],
        ];
    }
}
