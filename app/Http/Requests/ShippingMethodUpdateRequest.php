<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * @OA\RequestBody(
 *   request="ShippingMethodUpdate",
 *   @OA\JsonContent(
 *     @OA\Property(
 *       property="name",
 *       type="string",
 *       example="dpd",
 *     ),
 *     @OA\Property(
 *       property="public",
 *       type="boolean",
 *       example="1",
 *     ),
 *     @OA\Property(
 *       property="black_list",
 *       type="boolean",
 *       example="0",
 *     ),
 *     @OA\Property(
 *       property="payment_methods",
 *       type="array",
 *       @OA\Items(),
 *     ),
 *     @OA\Property(
 *       property="countries",
 *       type="array",
 *       @OA\Items(),
 *     ),
 *     @OA\Property(
 *       property="price_ranges",
 *       type="array",
 *       @OA\Items(),
 *     ),
 *   )
 * )
 */
class ShippingMethodUpdateRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => ['string', 'max:255'],
            'public' => 'boolean',
            'black_list' => 'boolean',
            'payment_methods' => 'array',
            'payment_methods.*' => ['uuid', 'exists:payment_methods,id'],
            'countries' => 'array',
            'countries.*' => ['string', 'size:2', 'exists:countries,code'],
            'price_ranges' => ['array', 'min:1'],
            'price_ranges.*.start' => ['required', 'numeric', 'min:0'],
            'price_ranges.*.value' => ['required', 'numeric', 'min:0'],
        ];
    }
}
