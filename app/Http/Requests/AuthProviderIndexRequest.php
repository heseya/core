<?php

namespace App\Http\Requests;

use App\Rules\Boolean;
use App\Traits\BooleanRules;
use Illuminate\Foundation\Http\FormRequest;

class AuthProviderIndexRequest extends FormRequest
{
    use BooleanRules;

    protected array $booleanFields = [
        'active',
    ];

    public function rules(): array
    {
        return [
            'active' => [new Boolean()],
        ];
    }
}
