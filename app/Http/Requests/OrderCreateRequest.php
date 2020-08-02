<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * @OA\RequestBody(
 *   request="OrderCreate",
 *   @OA\JsonContent(
 *     @OA\Property(
 *       property="email",
 *       type="string",
 *       example="admin@example.com",
 *     ),
 *     @OA\Property(
 *       property="shipping_method_id",
 *       type="integer",
 *     ),
 *     @OA\Property(
 *       property="comment",
 *       type="string",
 *       example="asap plz",
 *     ),
 *     @OA\Property(
 *       property="is_statute_accepted",
 *       type="boolean",
 *     ),
 *     @OA\Property(
 *       property="items",
 *       type="array",
 *       @OA\Items(
 *         type="object",
 *         @OA\Property(
 *           property="product_id",
 *           type="integer",
 *         ),
 *         @OA\Property(
 *           property="quantity",
 *           type="number",
 *         ),
 *         @OA\Property(
 *           property="schema_items",
 *           type="array",
 *           @OA\Items(
 *             type="integer"
 *           )
 *         ),
 *         @OA\Property(
 *           property="custom_schemas",
 *           type="array",
 *           @OA\Items(
 *             type="object",
 *             @OA\Property(
 *               property="schema_id",
 *               type="integer",
 *             ),
 *             @OA\Property(
 *               property="value",
 *               type="string",
 *             )
 *           )
 *         ),
 *       )
 *     ),
 *     @OA\Property(
 *       property="delivery_address",
 *       ref="#/components/schemas/Address",
 *     ),
 *     @OA\Property(
 *       property="invoice_address",
 *       ref="#/components/schemas/Address",
 *     )
 *   )
 * )
 */
class OrderCreateRequest extends FormRequest
{
    public function rules()
    {
        return [
            'email' => 'required|email',
            'comment' => 'string|max:1000|nullable',
            'shipping_method_id' => 'required|uuid|exists:shipping_methods,id',
            'is_statute_accepted' => 'accepted',

            'items' => 'required|array|min:1',

            'delivery_address.name' => 'required|string|max:255',
            'delivery_address.phone' => 'required|string|max:20',
            'delivery_address.address' => 'required|string|max:255',
            'delivery_address.vat' => 'string|max:15|nullable',
            'delivery_address.zip' => 'required|string|max:16',
            'delivery_address.city' => 'required|string|max:255',
            'delivery_address.country' => 'required|string|size:2',

            'invoice_address.name' => 'string|max:255|nullable',
            'invoice_address.phone' => 'string|max:20|nullable',
            'invoice_address.address' => 'string|max:255|nullable',
            'invoice_address.vat' => 'string|max:15|nullable',
            'invoice_address.zip' => 'string|max:16|nullable',
            'invoice_address.city' => 'string|max:255|nullable',
            'invoice_address.country' => 'string|size:2|nullable',
        ];
    }
}
