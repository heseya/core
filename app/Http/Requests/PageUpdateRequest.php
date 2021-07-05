<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * @OA\RequestBody(
 *   request="PageUpdate",
 *   @OA\JsonContent(
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
class PageUpdateRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => ['string', 'max:255'],
            'slug' => [
                'string', 
                'max:255',
                Rule::unique('pages')->ignore($this->route('page')->slug, 'slug'),
            ],
            'public' => ['boolean'],
            'content_html' => ['string', 'min:1'],
        ];
    }
}
