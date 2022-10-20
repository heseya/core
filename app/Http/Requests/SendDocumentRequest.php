<?php

namespace App\Http\Requests;

use App\Rules\DocumentsBelongToOrder;
use Illuminate\Foundation\Http\FormRequest;

class SendDocumentRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'uuid' => ['required', 'array'],
            'uuid.*' => ['uuid', 'exists:order_document,id', new DocumentsBelongToOrder()],
        ];
    }
}
