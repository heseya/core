<?php

namespace App\Http\Requests;

use App\Enums\MediaType;
use App\Rules\Boolean;
use App\Traits\BooleanRules;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class MediaIndexRequest extends FormRequest
{
    use BooleanRules;

    protected array $booleanFields = [
        'has_relationships',
    ];

    public function rules(): array
    {
        return [
            'type' => ['nullable', new Enum(MediaType::class), 'max:255'],
            'has_relationships' => [new Boolean()],
        ];
    }
}
