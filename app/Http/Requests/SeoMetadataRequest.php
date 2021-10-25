<?php

namespace App\Http\Requests;

use App\Enums\TwitterCardType;
use BenSampo\Enum\Rules\EnumValue;
use Illuminate\Foundation\Http\FormRequest;

class SeoMetadataRequest extends FormRequest
{
    /**
     * @OA\Schema(
     *   schema="SeoStore",
     *   @OA\Property(
     *     property="title",
     *     type="string",
     *     description="Displayed seo title",
     *     example="Title",
     *   ),
     *   @OA\Property(
     *     property="description",
     *     type="string",
     *     description="Displayed SEO description",
     *     example="Description SEO",
     *   ),
     *   @OA\Property(
     *     property="keywords",
     *     type="array",
     *     description="List of SEO keywords",
     *     @OA\Items(
     *         type="string",
     *         example="PHP",
     *     ),
     *   ),
     *   @OA\Property(
     *     property="og_image",
     *     type="string",
     *     example="0006c3a0-21af-4485-b7fe-9c42233cf03a",
     *   ),
     *   @OA\Property(
     *     property="twitter_card",
     *     type="string",
     *     description="summary | summary_large_image",
     *     example="summary",
     *   ),
     * )
     */
    public function rules(): array
    {
        return [
            'title' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'keywords' => ['nullable', 'array'],
            'og_image' => ['nullable', 'uuid', 'exists:media,id'],
            'twitter_card' => ['nullable', new EnumValue(TwitterCardType::class, false)],
        ];
    }
}
