<?php

namespace App\Http\Requests;

use App\Rules\Boolean;
use App\Traits\BooleanRules;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Config;
use Illuminate\Validation\Rule;

class SettingUpdateRequest extends FormRequest
{
    use BooleanRules;

    protected array $booleanFields = [
        'public',
    ];

    public function rules(): array
    {
        return [
            'name' => [
                'string',
                'max:255',
                Rule::unique('settings')->whereNot('name', $this->setting),
                Rule::notIn(
                    Collection::make(Config::get('settings'))
                        ->except($this->setting)->keys()->toArray(),
                ),
            ],
            'value' => ['string', 'max:1000'],
            'public' => ['nullable', new Boolean()],
        ];
    }
}
