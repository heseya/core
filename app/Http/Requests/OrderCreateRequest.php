<?php

namespace App\Http\Requests;

use App\Rules\DiscountAvailable;

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
 *           property="schemas",
 *           type="object",
 *           @OA\Property(
 *             property="119c0a63-1ea1-4769-8d5f-169f68de5598",
 *             type="string",
 *             example="123459fb-39a4-4dd0-8240-14793aa1f73b",
 *           ),
 *           @OA\Property(
 *             property="02b97693-857c-4fb9-9999-47400ac5fbef",
 *             type="string",
 *             example="HE + YA",
 *           ),
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
 *     ),
 *     @OA\Property(
 *       property="discounts",
 *       type="array",
 *       @OA\Items()
 *     )
 *   )
 * )
 */
class OrderCreateRequest extends OrderItemsRequest
{
    public function rules(): array
    {
        return parent::rules() + [
            'email' => ['required', 'email'],
            'comment' => ['nullable', 'string', 'max:1000'],
            'shipping_method_id' => ['required', 'uuid', 'exists:shipping_methods,id'],

            'delivery_address.name' => ['required', 'string', 'max:255'],
            'delivery_address.phone' => ['required', 'string', 'max:20'],
            'delivery_address.address' => ['required', 'string', 'max:255'],
            'delivery_address.zip' => ['required', 'string', 'max:16'],
            'delivery_address.city' => ['required', 'string', 'max:255'],
            'delivery_address.country' => ['required', 'string', 'size:2'],
            'delivery_address.vat' => ['nullable', 'string', 'max:15'],

            'invoice_address.name' => ['nullable', 'string', 'max:255'],
            'invoice_address.phone' => ['nullable', 'string', 'max:20'],
            'invoice_address.address' => ['nullable', 'string', 'max:255'],
            'invoice_address.vat' => ['nullable', 'string', 'max:15'],
            'invoice_address.zip' => ['nullable', 'string', 'max:16'],
            'invoice_address.city' => ['nullable', 'string', 'max:255'],
            'invoice_address.country' => ['nullable', 'string', 'size:2'],

            'discounts' => ['nullable', 'array'],
            'discounts.*' => ['string', 'exists:discounts,code', new DiscountAvailable()],

            'validation' => ['boolean'],
        ];
    }
}
