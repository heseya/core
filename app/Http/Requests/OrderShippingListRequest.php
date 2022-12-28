<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class OrderShippingListRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'package_template_id' => ['required', 'uuid', 'exists:package_templates,id'],
        ];
    }
}
