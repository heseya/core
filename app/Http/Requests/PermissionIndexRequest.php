<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PermissionIndexRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'assignable' => ['boolean'],
            'ids' => ['array'],
            'ids.*' => ['uuid'],
        ];
    }
}
