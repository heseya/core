<?php

namespace App\Http\Requests;

use App\Rules\Boolean;
use App\Traits\BooleanRules;
use Illuminate\Foundation\Http\FormRequest;

class RoleIndexRequest extends FormRequest
{
    use BooleanRules;

    protected array $booleanFields = [
        'assignable',
    ];

    public function rules(): array
    {
        return [
            'search' => ['string'],
            'name' => ['string'],
            'description' => ['string'],
            'assignable' => [new Boolean()],
            'metadata' => ['nullable', 'array'],
            'metadata_private' => ['nullable', 'array'],
        ];
    }
}
