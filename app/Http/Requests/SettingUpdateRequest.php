<?php

namespace App\Http\Requests;

use App\Models\Setting;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SettingUpdateRequest extends FormRequest
{
    public function rules(Setting $setting): array
    {
        return [
            'name' => [
                'string',
                'max:255',
                'unique:settings',
                Rule::unique('settings')->ignore($setting),
                Rule::notIn(
                    collect(config('settings'))
                        ->except($setting->name)->keys()->toArray(),
                ),
            ],
            'value' => ['string', 'max:255'],
            'public' => ['boolean'],
        ];
    }
}
