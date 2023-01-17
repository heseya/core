<?php

namespace App\Http\Requests;

use App\Rules\Boolean;
use App\Traits\BooleanRules;
use Illuminate\Foundation\Http\FormRequest;

class IndexSchemaRequest extends FormRequest
{
    use BooleanRules;

    protected array $booleanFields = [
        'hidden',
        'required',
    ];

    public function rules(): array
    {
        return [
            'name' => ['nullable', 'string', 'max:255'],
            'hidden' => [new Boolean()],
            'required' => [new Boolean()],

            'search' => ['nullable', 'string', 'max:255'],
            'sort' => ['nullable', 'string', 'max:255'],
            'metadata' => ['nullable', 'array'],
            'metadata_private' => ['nullable', 'array'],
            'ids' => ['array'],
            'ids.*' => ['uuid'],
        ];
    }
}
