<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class MediaStoreRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'file' => ['required', 'file', 'mimes:jpeg,png,gif,bmp'],
        ];
    }
}
