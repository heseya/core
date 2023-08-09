<?php

declare(strict_types=1);

namespace Domain\Page;

use Illuminate\Foundation\Http\FormRequest;

final class PageIndexRequest extends FormRequest
{
    /**
     * @return array<string, string[]>
     */
    public function rules(): array
    {
        return [
            'metadata' => ['nullable', 'array'],
            'metadata_private' => ['nullable', 'array'],
            'ids' => ['array'],
            'ids.*' => ['uuid'],
        ];
    }
}
