<?php

namespace App\Http\Controllers\Swagger;

use Illuminate\Http\Resources\Json\JsonResource;

interface AuditControllerSwagger
{
    /**
     * @OA\Get(
     *   path="/audits/{object}/id:{id}",
     *   summary="list changes for object",
     *   tags={"Audits"},
     *   @OA\Parameter(
     *     name="object",
     *     in="path",
     *     required=true,
     *     @OA\Schema(
     *       type="string",
     *       example="products",
     *     )
     *   ),
     *   @OA\Parameter(
     *     name="id",
     *     in="path",
     *     required=true,
     *     @OA\Schema(
     *       type="string",
     *       example="026bc5f6-8373-4aeb-972e-e78d72a67121",
     *     )
     *   ),
     *   @OA\Response(
     *     response=200,
     *     description="Success",
     *     @OA\JsonContent(
     *       @OA\Property(
     *         property="data",
     *         type="array",
     *         @OA\Items(ref="#/components/schemas/Audit"),
     *       )
     *     )
     *   )
     * )
     */
    public function index(string $class, string $id): JsonResource;
}
