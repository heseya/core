<?php

namespace App\Http\Requests;

class LanguageCreateRequest extends LanguageUpdateRequest
{
    public function rules(): array
    {
        return array_merge_recursive(parent::rules(), [
            'iso' => ['required'],
            'name' => ['required'],
            'default' => ['required'],
            'hidden' => ['required'],
        ]);
    }
}
