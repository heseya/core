<?php

namespace App\Http\Requests;

use App\Rules\Boolean;
use App\Traits\BooleanRules;
use Illuminate\Foundation\Http\FormRequest;

class ProductSetShowRequest extends FormRequest
{
    use BooleanRules;

    protected array $booleanFields = [
        'tree',
    ];

    public function rules(): array
    {
        return [
            'tree' => [new Boolean()],
        ];
    }
}
