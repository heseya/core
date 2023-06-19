<?php

namespace App\Http\Requests;

use App\Enums\MediaAttachmentType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class OrderDocumentRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'file' => ['required', 'file'],
            'name' => ['string'],
            'type' => ['required', new Enum(MediaAttachmentType::class)],
        ];
    }
}
