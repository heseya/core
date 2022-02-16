<?php

namespace App\Http\Requests;

use App\Rules\Translations;
use Illuminate\Validation\Rule;

class PageUpdateRequest extends SeoMetadataRulesRequest
{
    public function rules(): array
    {
        return $this->rulesWithSeo([
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
