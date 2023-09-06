<?php

namespace App\Http\Requests;

use App\Enums\RedirectType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class RedirectUpdateRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:30'],
            'url' => ['url'],
            'slug' => ['string', 'max:30'],
            'type' => [new Enum(RedirectType::class)],
        ];
    }
}
