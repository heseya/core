<?php

namespace App\Http\Requests;

use App\Traits\MetadataRules;
use Illuminate\Foundation\Http\FormRequest;

class ItemCreateRequest extends FormRequest
{
    use MetadataRules;

    public function rules(): array
    {
        return array_merge(
            $this->metadataRules(),
            [
                'id' => ['nullable', 'uuid'],

                'name' => ['required', 'string', 'max:255'],
                'sku' => ['required', 'string', 'unique:items', 'max:255'],
                'unlimited_stock_shipping_time' => [
                    'nullable',
                    'integer',
                    'prohibited_unless:unlimited_stock_shipping_date,null',
                ],
                'unlimited_stock_shipping_date' => [
                    'nullable',
                    'date',
                    'prohibited_unless:unlimited_stock_shipping_time,null',
                ],
            ],
        );
    }
}
