<?php

namespace App\Http\Controllers\Swagger;

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
     *         type="integer",
     *       ),
     *       @OA\Property(
     *         property="package_template_id",
     *         type="integer",
     *       ),
     *     ),
     *   ),
     *   @OA\Response(
     *     response=204,
     *     description="Success",
     *   ),
     *   security={
     *     {"oauth": {}}
     *   }
     * )
     */
    public function createPackage(Request $request);
}
