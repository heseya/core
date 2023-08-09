<?php

declare(strict_types=1);

namespace Domain\ProductSet\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class ProductSetReorderRequest extends FormRequest
{
    /**
     * @return array<string, string[]>
     */
    public function rules(): array
    {
        return [
            'product_sets' => ['required', 'array'],
            'product_sets.*' => ['uuid', 'exists:product_sets,id'],
        ];
    }
}
