<?php

namespace App\Http\Requests;

use App\Rules\AuthProviderActive;
use Illuminate\Foundation\Http\FormRequest;

class AuthProviderUpdateRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'active' => ['boolean', new AuthProviderActive()],
            'client_id' => ['string'],
            'client_secret' => ['string'],
        ];
    }
}
