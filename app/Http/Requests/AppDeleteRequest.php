<?php

namespace App\Http\Requests;

use App\Rules\Boolean;
use App\Traits\BooleanRules;
use Illuminate\Foundation\Http\FormRequest;

class AppDeleteRequest extends FormRequest
{
    use BooleanRules;

    protected array $booleanFields = [
        'force',
    ];

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules(): array
    {
        return [
            'force' => [new Boolean()],
        ];
    }
}
