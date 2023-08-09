<?php

declare(strict_types=1);

namespace Domain\ProductSet\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class ProductSetProductReorderRequest extends FormRequest
{
    /**
     * @return array<string, string[]>
     */
    public function rules(): array
    {
        return [
            'products' => ['required', 'array', 'size:1'],
            'products.*.order' => ['required', 'integer', 'gte:0'],
            'products.*.id' => ['required', 'uuid', 'exists:products,id'],
        ];
    }
}
