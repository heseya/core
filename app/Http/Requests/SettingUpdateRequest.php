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
        /** @var Collection<int, mixed> $settings */
        $settings = Config::get('settings');

        return [
            'name' => [
                'string',
                'max:255',
                Rule::unique('settings')->whereNot('name', $this->setting),
                Rule::notIn(
                    Collection::make($settings)
                        ->except($this->setting)->keys()->toArray(),
                ),
            ],
            'value' => ['string', 'max:1000'],
            'public' => ['nullable', 'boolean'],
        ];
    }
}
