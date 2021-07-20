<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class DiscountIndexRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'search' => ['string', 'max:255'],
            'description' => ['string', 'max:255'],
            'code' => ['string', 'max:64'],
        ];
    }
}
