<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class OrderUpdateStatusRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'status_id' => ['required', 'uuid', 'exists:statuses,id'],
        ];
    }
}
