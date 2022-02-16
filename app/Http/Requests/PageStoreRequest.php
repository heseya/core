<?php

namespace App\Http\Requests;

use App\Rules\Translations;

class PageStoreRequest extends SeoMetadataRulesRequest
{
    public function rules(): array
    {
        return $this->rulesWithSeo([
            'translations' => [
                'required',
                new Translations(['name', 'content_html']),
            ],
            'translations.*.name' => ['string', 'max:255'],
            'translations.*.content_html' => ['string', 'min:1'],

//            'name' => ['required', 'string', 'max:255'],
//            'content_html' => ['required', 'string', 'min:1'],

            'published' => ['required', 'array', 'min:1'],
            'published.*' => ['uuid', 'exists:languages,id'],

            'slug' => ['required', 'string', 'max:255'],
            'public' => ['boolean'],
        ]);
    }
}
