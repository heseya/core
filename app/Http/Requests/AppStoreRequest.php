<?php

namespace App\Http\Requests;

use App\Rules\AppUniqueUrl;
use App\Traits\MetadataRules;
use Illuminate\Foundation\Http\FormRequest;

class AppStoreRequest extends FormRequest
{
    use MetadataRules;
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules(): array
    {
        return array_merge(
            $this->metadataRules(),
            [
                'url' => ['required', 'url', new AppUniqueUrl()],
                'name' => ['nullable', 'string'],
                'licence_key' => ['nullable', 'string'],
                'allowed_permissions' => ['present', 'array'],
                'allowed_permissions.*' => ['string'],
                'public_app_permissions' => ['present', 'array'],
                'public_app_permissions.*' => ['string'],
            ]
        );
    }
}
