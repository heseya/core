<?php

namespace App\Http\Requests;

use App\Traits\SeoMetadataRules;
use Illuminate\Foundation\Http\FormRequest;

/**
 * @OA\RequestBody(
 *   request="PageStore",
 *   @OA\JsonContent(
 *     required={
 *       "name",
 *       "slug",
 *       "content_html",
 *     },
 *     @OA\Property(
 *       property="name",
 *       type="string",
 *       description="Name displayed as a page header",
 *       example="Terms & Conditions",
 *     ),
 *     @OA\Property(
 *       property="slug",
 *       type="string",
 *       description="Name used in the URL path",
 *       example="terms-and-conditions",
 *     ),
 *     @OA\Property(
 *       property="public",
 *       type="boolean",
 *       description="Whether page is visible to unauthorized users",
 *       example=true,
 *     ),
 *     @OA\Property(
 *       property="content_html",
 *       type="string",
 *       description="HTML formated text to be displayed as a main content under the page header",
 *       example="<h1>Lorem ipsum dolor sit amet</h1>",
 *     ),
 *   )
 * )
 */
class PageStoreRequest extends FormRequest
{
    use SeoMetadataRules;

    public function rules(): array
    {
        return array_merge([
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255'],
            'public' => ['boolean'],
            'content_html' => ['required', 'string', 'min:1'],
        ], $this->seoRules());
    }
}
