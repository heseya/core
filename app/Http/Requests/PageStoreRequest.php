<?php

namespace App\Http\Requests;

use App\Http\Requests\Contracts\SeoRequestContract;
use App\Rules\Translations;
use App\Traits\MetadataRules;
use App\Traits\SeoRules;
use Illuminate\Foundation\Http\FormRequest;

class PageStoreRequest extends FormRequest implements SeoRequestContract
{
    use MetadataRules;
    use SeoRules;

    public function rules(): array
    {
        return array_merge(
            $this->seoRules(),
            $this->metadataRules(),
            [
            'translations' => [
                'required',
                new Translations(['name', 'content_html']),
            ],
            'translations.*.name' => ['string', 'max:255'],
            'translations.*.content_html' => ['string', 'min:1'],

            'published' => ['required', 'array', 'min:1'],
            'published.*' => ['uuid', 'exists:languages,id'],

            'slug' => ['required', 'string', 'max:255'],
            'public' => ['boolean'],
        ]);
    }
}
