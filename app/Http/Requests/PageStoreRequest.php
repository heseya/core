<?php

namespace App\Http\Requests;

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
 *     @OA\Property(
 *       property="seo",
 *       type="object",
 *       ref="#/components/schemas/SeoStore",
 *     ),
 *   )
 * )
 */
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
