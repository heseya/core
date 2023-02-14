<?php

namespace App\Http\Requests;

use App\Rules\Boolean;
use Illuminate\Foundation\Http\FormRequest;

class ProductSetShowRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'tree' => [new Boolean()],
        ];
    }
}
