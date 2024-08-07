<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ItemIndexRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => ['nullable', 'string', 'max:255'],
            'sku' => ['nullable', 'string', 'max:255'],
            'search' => ['nullable', 'string', 'max:255'],
            // TODO poprawić sortowanie po ilości w przypadku stanu na dany dzień
            'sort' => ['nullable', 'string', 'max:255'],
            'sold_out' => ['boolean', 'prohibited_unless:day,null'],
            'day' => ['nullable', 'date', 'before_or_equal:now'],
            'metadata' => ['nullable', 'array'],
            'metadata_private' => ['nullable', 'array'],
            'ids' => ['array'],
            'ids.*' => ['uuid'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->sometimes(
            'sort',
            Rule::notIn(['quantity:asc', 'quantity:desc']),
            fn ($input) => $input->day !== null,
        );
    }
}
