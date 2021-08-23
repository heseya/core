<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * @OA\RequestBody(
 *   request="ProductSetAttach",
 *   @OA\JsonContent(
 *     required={
 *       "name",
 *       "slug",
 *     },
 *     @OA\Property(
 *       property="products",
 *       type="array",
 *       description="Ids of assigned products",
 *       @OA\Items(
 *         type="string",
 *         example="026bc5f6-8373-4aeb-972e-e78d72a67121",
 *       ),
 *     ),
 *   )
 * )
 */
class ProductSetAttachRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'products' => ['present', 'array'],
            'products.*' => ['uuid', 'exists:products,id'],
        ];
    }
}
