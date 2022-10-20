<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class FilterIndexRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'sets' => ['array'],
        ];
    }
}
