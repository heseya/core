<?php

namespace App\Http\Controllers\Swagger;

use Illuminate\Http\Resources\Json\JsonResource;

interface CountryControllerSwagger
{
    /**
     * @OA\Get(
     *   path="/countries",
     *   summary="list of all countries",
     *   tags={"Shipping"},
     *   @OA\Response(
     *     response=200,
     *     description="Success",
     *     @OA\JsonContent(
     *       @OA\Property(
     *         property="data",
     *         type="array",
     *         @OA\Items(ref="#/components/schemas/Country"),
     *       )
     *     )
     *   )
     * )
     */
    public function index(): JsonResource;
}
