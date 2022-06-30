<?php

namespace App\Http\Requests;

use App\Rules\AuthProviderActive;
use App\Rules\Boolean;
use App\Traits\BooleanRules;
use Illuminate\Foundation\Http\FormRequest;

class AuthProviderUpdateRequest extends FormRequest
{
    use BooleanRules;

    protected array $booleanFields = [
        'active',
    ];

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules(): array
    {
        return [
            'active' => [new Boolean(), new AuthProviderActive()],
            'client_id' => ['string'],
            'client_secret' => ['string'],
        ];
    }
}
