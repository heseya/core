<?php

namespace App\Http\Requests;

use App\Rules\ShippingMethodPriceRanges;
use Illuminate\Foundation\Http\FormRequest;

/**
 * @OA\RequestBody(
 *   request="ShippingMethodStore",
 *   @OA\JsonContent(
 *     @OA\Property(
 *       property="name",
 *       type="string",
 *       example="dpd",
 *     ),
 *     @OA\Property(
 *       property="public",
 *       type="boolean",
 *       example="true",
 *     ),
 *     @OA\Property(
 *       property="black_list",
 *       type="boolean",
 *       example="false",
 *     ),
 *     @OA\Property(
 *       property="payment_methods",
 *       type="array",
 *       @OA\Items(
 *         type="string",
 *         example="026bc5f6-8373-4aeb-972e-e78d72a67121",
 *       ),
 *     ),
 *     @OA\Property(
 *       property="countries",
 *       type="array",
 *       @OA\Items(
 *         type="string",
 *         example="PL",
 *       ),
 *     ),
 *     @OA\Property(
 *       property="price_ranges",
 *       type="array",
 *       @OA\Items(
 *         type="object",
 *         @OA\Property(
 *           property="start",
 *           description="start of the range (min = 0);
 *             range goes from start to start of next range or infinity",
 *           type="number",
 *           example=0.0
 *         ),
 *         @OA\Property(
 *           property="value",
 *           description="price in this range",
 *           type="number",
 *           example=18.70
 *         ),
 *       ),
 *     ),
 *   )
 * )
 */
class ShippingMethodStoreRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'public' => 'boolean',
            'black_list' => 'boolean',
            'payment_methods' => 'array',
            'payment_methods.*' => ['uuid', 'exists:payment_methods,id'],
            'countries' => 'array',
            'countries.*' => ['string', 'size:2', 'exists:countries,code'],
            'price_ranges' => ['required', 'array', 'min:1', new ShippingMethodPriceRanges()],
            'price_ranges.*.start' => ['required', 'numeric', 'min:0', 'distinct'],
            'price_ranges.*.value' => ['required', 'numeric', 'min:0'],
        ];
    }
}
