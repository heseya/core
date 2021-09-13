<?php

namespace App\Http\Resources\Swagger;

use Illuminate\Http\Request;

interface AuditResourceSwagger
{
    /**
     * @OA\Schema(
     *   schema="Audit",
     *   @OA\Property(
     *     property="id",
     *     type="string",
     *     example="026bc5f6-8373-4aeb-972e-e78d72a67121",
     *   ),
     *   @OA\Property(
     *     property="event",
     *     type="string",
     *     example="updated",
     *   ),
     *   @OA\Property(
     *     property="created_at",
     *     type="string",
     *     description="Date of event",
     *     example="2021-10-10T12:00:00",
     *   ),
     *   @OA\Property(
     *     property="old_values",
     *     type="array",
     *     @OA\Items(
     *       @OA\Property(
     *         property="key",
     *         type="string",
     *         example="value",
     *       ),
     *     ),
     *   ),
     *   @OA\Property(
     *     property="new_values",
     *     type="array",
     *     @OA\Items(
     *       @OA\Property(
     *         property="key",
     *         type="string",
     *         example="value",
     *       ),
     *     ),
     *   ),
     *   @OA\Property(
     *     property="user",
     *     type="object",
     *     ref="#/components/schemas/User",
     *   ),
     * )
     */
    public function base(Request $request): array;
}
