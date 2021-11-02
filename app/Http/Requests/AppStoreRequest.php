<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * @OA\RequestBody(
 *   request="AppStore",
 *   @OA\JsonContent(
 *     required={
 *       "url",
 *       "allowed_permissions",
 *     },
 *     @OA\Property(
 *       property="url",
 *       type="string",
 *       description="Url of the applications microfrontend configuration page",
 *       example="https://microfront.app.heseya.com",
 *     ),
 *     @OA\Property(
 *       property="name",
 *       type="string",
 *       description="Name of the app",
 *       example="Super App",
 *     ),
 *     @OA\Property(
 *       property="licence_key",
 *       type="string",
 *       description="Licence key allowing to install application",
 *       example="6v5e*B^%e8n76rn869r6n9r75nim76E&%nm996f7e87m",
 *     ),
 *     @OA\Property(
 *       property="allowed_permissions",
 *       type="array",
 *       description="Granted permission names",
 *       @OA\Items(
 *         type="string",
 *         example="products.add",
 *       ),
 *     ),
 *   )
 * )
 */
class AppStoreRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules(): array
    {
        return [
            'url' => ['required', 'url'],
            'name' => ['nullable', 'string'],
            'licence_key' => ['nullable', 'string'],
            'allowed_permissions' => ['array'],
            'allowed_permissions.*' => ['string'],
        ];
    }
}
