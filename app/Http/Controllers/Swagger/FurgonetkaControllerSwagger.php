<?php

namespace App\Http\Controllers\Swagger;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

interface FurgonetkaControllerSwagger
{
    /**
     * @OA\Post(
     *   path="/furgonetka/create-package",
     *   summary="creates package on furgonetka",
     *   tags={"Furgonetka"},
     *   @OA\RequestBody(
     *     @OA\JsonContent(
     *       @OA\Property(
     *         property="order_id",
     *         type="string",
     *         example="0246aa10-2585-44c0-8937-f7155af61f03",
     *       ),
     *       @OA\Property(
     *         property="package_template_id",
     *         type="string",
     *         example="8026e9a2-78f8-456c-9008-48c77442e9a4",
     *       ),
     *     ),
     *   ),
     *   @OA\Response(
     *     response=201,
     *     description="Created",
     *     @OA\JsonContent(
     *       @OA\Property(
     *         property="shipping_number",
     *         type="string",
     *         example="0000000773354Q",
     *       ),
     *     ),
     *   ),
     *   security={
     *     {"oauth": {}}
     *   }
     * )
     */
    public function createPackage(Request $request): JsonResponse;
}
