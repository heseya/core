<?php

namespace App\Http\Requests;

use App\Http\Requests\Contracts\SeoRequestContract;
use App\Traits\SeoRules;
use Illuminate\Foundation\Http\FormRequest;

class SeoRequest extends FormRequest implements SeoRequestContract
{
    use SeoRules;

    public function rules(): array
    {
        return $this->seoRules('');
    }
}
