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
 *       property="comment",
 *       type="string",
 *       example="asap plz",
 *     ),
 *     @OA\Property(
 *       property="shipping_method_id",
 *       type="string",
 *       example="026bc5f6-8373-4aeb-972e-e78d72a67121",
 *     ),
 *     @OA\Property(
 *       property="items",
 *       type="array",
 *       @OA\Items(
 *         type="object",
 *         @OA\Property(
 *           property="product_id",
 *           type="string",
 *           example="026bc5f6-8373-4aeb-972e-e78d72a67121",
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
            'email' => ['required', 'email'],
            'comment' => ['nullable', 'string', 'max:1000'],
            'shipping_method_id' => ['required', 'uuid', 'exists:shipping_methods,id'],

            'delivery_address.name'    => ['required', 'string', 'max:255'],
            'delivery_address.phone'   => ['required', 'string', 'max:20'],
            'delivery_address.address' => ['required', 'string', 'max:255'],
            'delivery_address.zip'     => ['required', 'string', 'max:16'],
            'delivery_address.city'    => ['required', 'string', 'max:255'],
            'delivery_address.country' => ['required', 'string', 'size:2'],
            'delivery_address.vat'     => ['nullable', 'string', 'max:15'],

            'invoice_address.name'    => ['nullable', 'string', 'max:255'],
            'invoice_address.phone'   => ['nullable', 'string', 'max:20'],
            'invoice_address.address' => ['nullable', 'string', 'max:255'],
            'invoice_address.vat'     => ['nullable', 'string', 'max:15'],
            'invoice_address.zip'     => ['nullable', 'string', 'max:16'],
            'invoice_address.city'    => ['nullable', 'string', 'max:255'],
            'invoice_address.country' => ['nullable', 'string', 'size:2'],

            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'uuid', 'exists:products,id'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
        ];
    }
}
