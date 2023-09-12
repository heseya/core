<?php

namespace App\Http\Requests;

use Domain\Redirect\Enums\RedirectType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class RedirectUpdateRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => ['string', 'max:255'],
            'url' => ['url'],
            'slug' => ['string', 'max:255'],
            'type' => [new Enum(RedirectType::class)],
        ];
    }
}
