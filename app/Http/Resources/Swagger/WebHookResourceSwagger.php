<?php

namespace App\Http\Resources\Swagger;

use Illuminate\Http\Request;

interface WebHookResourceSwagger
{
    /**
     * @OA\Schema(
     *   schema="WebHook",
     *   @OA\Property(
     *     property="id",
     *     type="string",
     *     example="026bc5f6-8373-4aeb-972e-e78d72a67121",
     *   ),
     *   @OA\Property(
     *     property="name",
     *     type="string",
     *     description="Displayed webhook name",
     *     example="WebHook name",
     *   ),
     *   @OA\Property(
     *     property="url",
     *     type="string",
     *     description="Displayed webhook url",
     *     example="https://app.heseya.com",
     *   ),
     *   @OA\Property(
     *     property="with_issuer",
     *     type="boolean",
     *     example=true,
     *     description="Whether issuer is visible in WebHookEvent.",
     *   ),
     *   @OA\Property(
     *     property="with_hidden",
     *     type="boolean",
     *     example=true,
     *     description="Whether hidden data are visible in WebHookEvent.",
     *   ),
     * )
     */
    public function base(Request $request): array;
}
