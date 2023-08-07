<?php

declare(strict_types=1);

namespace Domain\ProductSet\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class ProductSetProductsRequest extends FormRequest
{
    /**
     * @return array<string, string[]>
     */
    public function rules(): array
    {
        return [
            'limit' => ['nullable', 'integer', 'min:1', 'max:500'],
        ];
    }
}
