<?php

namespace App\Http\Requests;

use App\Rules\Boolean;
use App\Traits\BooleanRules;
use Illuminate\Foundation\Http\FormRequest;

class ProductSetIndexRequest extends FormRequest
{
    use BooleanRules;

    protected array $booleanFields = [
        'public',
        'tree',
        'root',
    ];

    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:255'],
            'name' => ['nullable', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255'],
            'public' => [new Boolean()],
            'tree' => [new Boolean()],
            'root' => [new Boolean()],
            'metadata' => ['nullable', 'array'],
            'metadata_private' => ['nullable', 'array'],
        ];
    }
}
