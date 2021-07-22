<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * @OA\RequestBody(
 *   request="ShippingMethodReorder",
 *   @OA\JsonContent(
 *     @OA\Property(
 *       property="shipping_methods",
 *       type="array",
 *       @OA\Items(
 *         type="string",
 *         example="026bc5f6-8373-4aeb-972e-e78d72a67121",
 *       ),
 *     ),
 *   )
 * )
 */
class ShippingMethodReorderRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'shipping_methods' => ['required', 'array'],
            'shipping_methods.*' => ['uuid', 'exists:shipping_methods,id'],
        ];
    }
}
