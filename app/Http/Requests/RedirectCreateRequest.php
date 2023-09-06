<?php

namespace App\Http\Requests;

use App\Enums\RedirectType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class RedirectCreateRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:30'],
            'url' => ['required', 'url'],
            'slug' => ['required', 'string', 'max:30'],
            'type' => ['required', new Enum(RedirectType::class)],
        ];
    }
}
