<?php

namespace App\Http\Requests;

use App\Rules\Boolean;
use App\Traits\BooleanRules;
use Illuminate\Foundation\Http\FormRequest;

class PermissionIndexRequest extends FormRequest
{
    use BooleanRules;

    protected array $booleanFields = [
        'assignable',
    ];

    public function rules(): array
    {
        return [
            'assignable' => [new Boolean()],
        ];
    }
}
