<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * @OA\RequestBody(
 *   request="ShippingMethodOrder",
 *   @OA\JsonContent(
 *     @OA\Property(
 *       property="shipping_methods",
 *       type="array",
 *       @OA\Items(),
 *     ),
 *   )
 * )
 */
class ShippingMethodOrderRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'shipping_methods' => ['required', 'array'],
            'shipping_methods.*' => ['uuid', 'exists:shipping_methods,id'],
        ];
    }
}
