<?php

namespace App\Http\Controllers\Swagger;

use Illuminate\Http\Resources\Json\JsonResource;

interface EventControllerSwagger
{
    /**
     * @OA\Get(
     *     path="/web-hooks/events",
     *     summary="list available events",
     *     tags={"WebHooks"},
     *     @OA\Response(
     *         response=200,
     *         description="Success",
     *         @OA\JsonContent(
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(
     *                     ref="#/components/schemas/Event"
     *                 ),
     *             ),
     *         ),
     *     ),
     * )
     */
    public function index(): JsonResource;
}
