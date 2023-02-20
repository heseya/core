<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ProductSetShowRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'tree' => ['boolean'],
        ];
    }
}
