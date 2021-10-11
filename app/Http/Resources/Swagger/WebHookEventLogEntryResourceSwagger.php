<?php

namespace App\Http\Resources\Swagger;

use Illuminate\Http\Request;

interface WebHookEventLogEntryResourceSwagger
{
    /**
     * @OA\Schema(
     *   schema="WebHookEventLogEntry",
     *   @OA\Property(
     *     property="id",
     *     type="string",
     *     example="026bc5f6-8373-4aeb-972e-e78d72a67121",
     *   ),
     *   @OA\Property(
     *     property="triggered_at",
     *     type="datetime",
     *     description="Displayed webhook event triggered time",
     *     example="2021-10-07T08:49",
     *   ),
     *   @OA\Property(
     *     property="url",
     *     type="string",
     *     description="Displayed webhook event url",
     *     example="https://app.heseya.com",
     *   ),
     *   @OA\Property(
     *     property="status_code",
     *     type="number",
     *     example=200,
     *     description="Webhook event status code",
     *   ),
     * )
     */
    public function base(Request $request): array;
}
