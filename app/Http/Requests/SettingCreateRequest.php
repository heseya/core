<?php

namespace App\Http\Requests;

use App\Rules\Boolean;
use App\Traits\BooleanRules;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Config;
use Illuminate\Validation\Rule;

class SettingCreateRequest extends FormRequest
{
    use BooleanRules;

    protected array $booleanFields = [
        'public',
    ];

    public function rules(): array
    {
        return [
            'name' => [
                'required',
                'string',
                'max:255',
                'unique:settings',
                Rule::notIn(array_keys(Config::get('settings'))),
            ],
            'value' => ['required', 'string', 'max:1000'],
            'public' => ['required', new Boolean()],
        ];
    }
}
