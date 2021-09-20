<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateAppRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules(): array
    {
        return [
            'url' => ['required', 'url'],
            'name' => ['nullable', 'string'],
            'licence_key' => ['nullable', 'string'],
            'allowed_permissions' => ['array'],
            'allowed_permissions.*' => ['string'],
        ];
    }
}
