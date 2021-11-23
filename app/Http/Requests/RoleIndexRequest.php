<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RoleIndexRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'search' => ['string'],
            'name' => ['string'],
            'description' => ['string'],
            'assignable' => ['boolean'],
        ];
    }
}
