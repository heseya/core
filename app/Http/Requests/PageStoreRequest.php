<?php

namespace App\Http\Requests;

use App\Http\Requests\Contracts\SeoRequestContract;
use App\Rules\Boolean;
use App\Traits\BooleanRules;
use App\Traits\MetadataRules;
use App\Traits\SeoRules;
use Illuminate\Foundation\Http\FormRequest;

class PageStoreRequest extends FormRequest implements SeoRequestContract
{
    use SeoRules, BooleanRules, MetadataRules;

    protected array $booleanFields = [
        'public',
        'seo.no_index',
    ];

    public function rules(): array
    {
        return array_merge(
            $this->seoRules(),
            $this->metadataRules(),
            [
                'name' => ['required', 'string', 'max:255'],
                'slug' => ['required', 'unique:pages', 'string', 'max:255'],
                'public' => [new Boolean()],
                'content_html' => ['required', 'string', 'min:1'],
            ],
        );
    }
}
