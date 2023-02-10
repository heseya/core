<?php

namespace App\Http\Requests;

use App\Http\Requests\Contracts\SeoRequestContract;
use App\Traits\BooleanRules;
use App\Traits\SeoRules;
use Illuminate\Foundation\Http\FormRequest;

class SeoRequest extends FormRequest implements SeoRequestContract
{
    use SeoRules;
    use BooleanRules;

    protected array $booleanFields = [
        'no_index',
    ];

    public function rules(): array
    {
        return $this->seoRules('');
    }
}
