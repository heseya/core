<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * @OA\RequestBody(
 *   request="OrderUpdate",
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
 *       property="delivery_address",
 *       ref="#/components/schemas/Address",
 *     ),
 *     @OA\Property(
 *       property="invoice_address",
 *       ref="#/components/schemas/Address",
 *     ),
 *   )
 * )
 */
class OrderUpdateRequest extends FormRequest
{
    public function rules(): array
    {
        return [
                'email' => ['required', 'email'],
                'comment' => ['nullable', 'string', 'max:1000'],

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
                'invoice_address.zip' => ['nullable', 'string', 'max:16'],
                'invoice_address.city' => ['nullable', 'string', 'max:255'],
                'invoice_address.country' => ['nullable', 'string', 'size:2'],
                'invoice_address.vat' => ['nullable', 'string', 'max:15'],

                'validation' => ['boolean'],
            ];
    }
}
