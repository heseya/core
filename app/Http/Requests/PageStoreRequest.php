<?php

namespace App\Http\Requests;

class PageStoreRequest extends SeoMetadataRulesRequest
{
    public function rules(): array
    {
        return $this->rulesWithSeo([
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255'],
            'public' => ['boolean'],
            'content_html' => ['required', 'string', 'min:1'],
        ]);
    }
}
