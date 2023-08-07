<?php

declare(strict_types=1);

namespace Domain\ProductSet\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class ProductSetIndexRequest extends FormRequest
{
    /**
     * @return array<string, string[]>
     */
    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:255'],
            'name' => ['nullable', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255'],
            'parent_id' => ['nullable', 'uuid', 'exists:product_sets,id'],
            'public' => ['boolean'],
            'root' => ['boolean'],
            'metadata' => ['nullable', 'array'],
            'metadata_private' => ['nullable', 'array'],
            'ids' => ['array'],
            'ids.*' => ['uuid'],
        ];
    }
}
