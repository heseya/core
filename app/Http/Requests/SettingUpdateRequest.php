<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Config;
use Illuminate\Validation\Rule;

class SettingUpdateRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('settings')->whereNot('name', $this->setting),
                Rule::notIn(
                    Collection::make(Config::get('settings'))
                        ->except($this->setting)->keys()->toArray(),
                ),
            ],
            'value' => ['required', 'string', 'max:1000'],
            'public' => ['nullable', 'boolean'],
        ];
    }
}
