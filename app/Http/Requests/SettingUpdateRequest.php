<?php

namespace App\Http\Requests;

use App\Models\Setting;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SettingUpdateRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => [
                'string',
                'max:255',
                Rule::unique('settings')->whereNot('name', $this->setting),
                Rule::notIn(
                    collect(config('settings'))
                        ->except($this->setting)->keys()->toArray(),
                ),
            ],
            'value' => ['string', 'max:255'],
            'public' => ['boolean'],
        ];
    }
}
