<?php

namespace App\Http\Requests;

use App\Rules\Boolean;
use Illuminate\Foundation\Http\FormRequest;

class AppDeleteRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'force' => [new Boolean()],
        ];
    }
}
