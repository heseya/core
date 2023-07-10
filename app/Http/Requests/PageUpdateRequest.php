<?php

namespace App\Http\Requests;

use App\Http\Requests\Contracts\SeoRequestContract;
use App\Rules\Translations;
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
                'translations' => [
                    new Translations(['name', 'content_html']),
                ],
                'translations.*.name' => ['string', 'max:255'],
                'translations.*.content_html' => ['string', 'min:1'],

                'published' => ['array', 'min:1'],
                'published.*' => ['nullable', 'uuid', 'exists:languages,id'],

                'slug' => [
                    'string',
                    'max:255',
                    Rule::unique('pages')->ignore($this->route('page')->slug, 'slug'),
                ],
                'public' => ['boolean'],
            ]);
    }
}
