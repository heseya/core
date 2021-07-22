<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * @OA\RequestBody(
 *   request="ProductSetReorder",
 *   @OA\JsonContent(
 *     required={
 *       "product_sets",
 *     },
 *     @OA\Property(
 *       property="product_sets",
 *       type="array",
 *       description="Ids of reordered sets",
 *       @OA\Items(
 *         type="string",
 *         example="026bc5f6-8373-4aeb-972e-e78d72a67121",
 *       ),
 *     ),
 *   )
 * )
 */
class ProductSetReorderRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'product_sets' => ['required', 'array'],
            'product_sets.*' => ['uuid', 'exists:product_sets,id'],
        ];
    }
}
