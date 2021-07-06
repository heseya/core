<?php

namespace App\Http\Controllers\Swagger;

use App\Http\Requests\MediaStoreRequest;
use Illuminate\Http\Resources\Json\JsonResource;

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
     *     response=201,
     *     description="Created",
     *     @OA\JsonContent(
     *       @OA\Property(
     *         property="data",
     *         type="array",
     *         @OA\Items(ref="#/components/schemas/Media"),
     *       )
     *     )
     *   ),
     *   security={
     *     {"oauth": {}}
     *   }
     * )
     */
    public function store(MediaStoreRequest $request): JsonResource;
}
