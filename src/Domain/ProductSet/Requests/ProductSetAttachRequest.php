<?php

declare(strict_types=1);

namespace Domain\ProductSet\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class ProductSetAttachRequest extends FormRequest
{
    /**
     * @return array<string, string[]>
     */
    public function rules(): array
    {
        return [
            'products' => ['present', 'array'],
            'products.*' => ['uuid', 'exists:products,id'],
        ];
    }
}
