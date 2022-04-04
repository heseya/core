<?php

namespace App\Http\Requests;

use App\Http\Requests\Contracts\SeoRequestContract;
use App\Traits\SeoRules;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PageUpdateRequest extends FormRequest implements SeoRequestContract
{
    use SeoRules;

    public function rules(): array
    {
        return array_merge(
            $this->seoRules(),
            [
                'name' => ['string', 'max:255'],
                'slug' => [
                    'string',
                    'max:255',
                    Rule::unique('pages')->ignore($this->route('page')->slug, 'slug'),
                ],
                'public' => ['boolean'],
                'content_html' => ['string', 'min:1'],
            ],
        );
    }
}
