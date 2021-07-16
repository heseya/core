<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * @OA\RequestBody(
 *   request="OrderOrder",
 *   @OA\JsonContent(
 *     @OA\Property(
 *       property="orders",
 *       type="array",
 *     ),
 *   )
 * )
 */
class OrderOrderRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'orders' => ['required', 'array'],
            'orders.*' => ['uuid', 'exists:orders,id'],
        ];
    }
}
