<?php

namespace App\Http\Requests;

use App\Rules\Boolean;
use App\Traits\BooleanRules;
use App\Traits\MetadataRules;
use Illuminate\Foundation\Http\FormRequest;

class StatusCreateRequest extends FormRequest
{
    use BooleanRules;
    use MetadataRules;

    protected array $booleanFields = [
        'cancel',
        'hidden',
        'no_notifications',
    ];

    public function rules(): array
    {
        return array_merge(
            $this->metadataRules(),
            [
                'name' => ['required', 'string', 'max:60'],
                'color' => ['required', 'string', 'size:6'],
                'cancel' => [new Boolean()],
                'description' => ['string', 'max:255', 'nullable'],
                'hidden' => [new Boolean()],
                'no_notifications' => [new Boolean()],
            ]
        );
    }
}
