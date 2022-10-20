<?php

namespace App\Http\Requests;

use App\Http\Requests\Contracts\SeoRequestContract;
use App\Models\Page;
use App\Rules\Boolean;
use App\Traits\BooleanRules;
use App\Traits\SeoRules;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PageUpdateRequest extends FormRequest implements SeoRequestContract
{
    use SeoRules, BooleanRules;

    protected array $booleanFields = [
        'public',
        'seo.no_index',
    ];

    public function rules(): array
    {
        /** @var Page $page */
        $page = $this->route('page');

        return array_merge(
            $this->seoRules(),
            [
                'name' => ['string', 'max:255'],
                'slug' => [
                    'string',
                    'max:255',
                    Rule::unique('pages')->ignore($page->slug, 'slug'),
                ],
                'public' => [new Boolean()],
                'content_html' => ['string', 'min:1'],
            ],
        );
    }
}
