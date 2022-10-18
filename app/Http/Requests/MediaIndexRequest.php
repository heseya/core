<?php

namespace App\Http\Requests;

use App\Enums\MediaType;
use App\Rules\Boolean;
use App\Traits\BooleanRules;
use BenSampo\Enum\Rules\EnumValue;
use Illuminate\Foundation\Http\FormRequest;

class MediaIndexRequest extends FormRequest
{
    use BooleanRules;

    protected array $booleanFields = [
        'has_relationships',
    ];

    public function rules(): array
    {
        return [
            'type' => ['nullable', new EnumValue(MediaType::class, false), 'max:255'],
            'has_relationships' => [new Boolean()],
        ];
    }
}
