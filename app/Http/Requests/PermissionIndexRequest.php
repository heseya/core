<?php

namespace App\Http\Requests;

use App\Rules\Boolean;
use Illuminate\Foundation\Http\FormRequest;

class PermissionIndexRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'assignable' => [new Boolean()],
            'ids' => ['array'],
            'ids.*' => ['uuid'],
        ];
    }
}
