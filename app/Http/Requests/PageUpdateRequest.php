<?php

namespace App\Http\Requests;

use Illuminate\Validation\Rule;

class PageUpdateRequest extends SeoMetadataRulesRequest
{
    public function rules(): array
    {
        return $this->rulesWithSeo([
            'name' => ['string', 'max:255'],
            'slug' => [
                'string',
                'max:255',
                Rule::unique('pages')->ignore($this->route('page')->slug, 'slug'),
            ],
            'public' => ['boolean'],
            'content_html' => ['string', 'min:1'],
        ]);
    }
}
