<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PackageTemplateUpdateRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => ['string', 'max:255'],
            'weight' => ['numeric'],
            'width' => ['integer'],
            'height' => ['integer'],
            'depth' => ['integer'],
        ];
    }
}
