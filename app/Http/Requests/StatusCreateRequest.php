<?php

namespace App\Http\Requests;

use App\Traits\MetadataRules;
use Illuminate\Foundation\Http\FormRequest;

class StatusCreateRequest extends FormRequest
{
    use MetadataRules;

    public function rules(): array
    {
        return array_merge(
            $this->metadataRules(),
            [
                'name' => ['required', 'string', 'max:60'],
                'color' => ['required', 'string', 'size:6'],
                'cancel' => ['boolean'],
                'description' => ['string', 'max:255', 'nullable'],
                'hidden' => ['boolean'],
                'no_notifications' => ['boolean'],
            ],
        );
    }
}
