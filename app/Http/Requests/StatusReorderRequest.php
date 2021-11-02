<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * @OA\RequestBody(
 *   request="StatusReorderRequest",
 *   @OA\JsonContent(
 *     @OA\Property(
 *       property="statuses",
 *       type="array",
 *       @OA\Items(
 *         type="string",
 *         example="026bc5f6-8373-4aeb-972e-e78d72a67121",
 *       ),
 *     ),
 *   )
 * )
 */
class StatusReorderRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules(): array
    {
        return [
            'statuses' => ['required', 'array'],
            'statuses.*' => ['uuid', 'exists:statuses,id'],
        ];
    }
}
