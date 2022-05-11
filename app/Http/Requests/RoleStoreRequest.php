<?php

namespace App\Http\Requests;

use App\Traits\MetadataRules;
use Illuminate\Foundation\Http\FormRequest;

class RoleStoreRequest extends FormRequest
{
    use MetadataRules;

    public function rules(): array
    {
        return array_merge(
            $this->metadataRules(),
            [
                'name' => ['required', 'string'],
                'description' => ['nullable', 'string'],
                'permissions' => ['array'],
                'permissions.*' => ['string'],
            ]
        );
    }
}
