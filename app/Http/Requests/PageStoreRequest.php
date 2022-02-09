<?php

namespace App\Http\Requests;

use App\Rules\Translations;

class PageStoreRequest extends SeoMetadataRulesRequest
{
    public function rules(): array
    {
        return $this->rulesWithSeo([
            'translations' => [
                new Translations(['name', 'content_html']),
            ],
//            'name' => ['required', 'string', 'max:255'],
//            'content_html' => ['required', 'string', 'min:1'],

            'published' => ['required', 'array', 'min:1'],
            'published.*' => ['uuid', 'exists:languages,id'],

            'slug' => ['required', 'string', 'max:255'],
            'public' => ['boolean'],
        ]);
    }
}
