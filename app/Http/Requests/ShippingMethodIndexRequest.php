<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * @OA\RequestBody(
 *   request="ShippingMethodIndex",
 *   @OA\JsonContent(
 *     @OA\Property(
 *       property="country",
 *       type="string",
 *     ),
 *     @OA\Property(
 *       property="cart_value",
 *       type="float",
 *     ),
 *   )
 * )
 */
class ShippingMethodIndexRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'country' => ['string', 'size:2', 'exists:countries,code'],
            'cart_value' => ['numeric'],
        ];
    }
}
