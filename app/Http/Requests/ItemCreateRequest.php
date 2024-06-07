<?php

namespace App\Http\Requests;

use App\Traits\MetadataRules;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ItemCreateRequest extends FormRequest
{
    use MetadataRules;

    public function rules(): array
    {
        return array_merge(
            $this->metadataRules(),
            [
                'id' => ['uuid'],

                'name' => ['required', 'string', 'max:255'],
                'sku' => ['required', 'string', 'max:255', Rule::unique('items', 'sku')->whereNull('deleted_at')],
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
