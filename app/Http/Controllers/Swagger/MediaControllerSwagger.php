<?php

namespace App\Http\Controllers\Swagger;

use Illuminate\Http\Request;

interface MediaControllerSwagger
{
    /**
     * @OA\Post(
     *   path="/media",
     *   summary="upload new file",
     *   tags={"Media"},
     *   @OA\RequestBody(
     *     @OA\MediaType(
     *       mediaType="multipart/form-data",
     *       @OA\Schema(
     *         @OA\Property(
     *           property="file",
     *           description="File.",
     *           type="binary",
     *         ),
     *       ),
     *     ),
     *   ),
     *   @OA\Response(
     *     response=200,
     *     description="Success",
     *     @OA\JsonContent(
     *       @OA\Property(
     *         property="data",
     *         type="array",
     *         @OA\Items(ref="#/components/schemas/Media"),
     *       )
     *     )
     *   )
     * )
     */
    public function store(Request $request);
}
