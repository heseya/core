<?php

namespace App\Http\Requests;

use App\Rules\EventExist;
use Illuminate\Foundation\Http\FormRequest;

/**
 * @OA\RequestBody (
 *   request="WebHookUpdate",
 *   @OA\JsonContent(
 *     @OA\Property(
 *       property="name",
 *       type="string",
 *       description="Displayed webhook name",
 *       example="WebHook name",
 *     ),
 *     @OA\Property(
 *       property="url",
 *       type="string",
 *       description="Displayed webhook url",
 *       example="https://app.heseya.com",
 *     ),
 *     @OA\Property(
 *       property="secret",
 *       type="string",
 *       description="Displayed webhook secret",
 *       example="secret",
 *     ),
 *     @OA\Property(
 *       property="with_issuer",
 *       type="boolean",
 *       example=true,
 *       description="Whether issuer is visible in WebHookEvent.",
 *     ),
 *     @OA\Property(
 *       property="with_hidden",
 *       type="boolean",
 *       example=true,
 *       description="Whether hidden data are visible in WebHookEvent.",
 *     ),
 *     @OA\Property(
 *       property="events",
 *       type="array",
 *       description="List of WebHook events",
 *       @OA\Items(
 *         type="string",
 *         example="OrderCreated",
 *       ),
 *     ),
 *   )
 * )
 */
class WebHookUpdateRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => ['nullable', 'string', 'max:255'],
            'url' => ['nullable', 'string', 'max:255'],
            'secret' => ['nullable', 'string', 'max:255'],
            'events' => ['bail', 'nullable', 'array', new EventExist()],
            'with_issuer' => ['nullable'],
            'with_hidden' => ['nullable'],
        ];
    }
}
