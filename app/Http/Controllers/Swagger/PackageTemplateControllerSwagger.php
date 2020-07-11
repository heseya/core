<?php

namespace App\Http\Controllers\Swagger;

use App\Models\PackageTemplate;
use Illuminate\Http\Request;

interface PackageTemplateControllerSwagger
{
    /**
     * @OA\Get(
     *   path="/package-templates",
     *   summary="list packages",
     *   tags={"Packages"},
     *   @OA\Response(
     *     response=200,
     *     description="Success",
     *     @OA\JsonContent(
     *       @OA\Property(
     *         property="data",
     *         type="array",
     *         @OA\Items(ref="#/components/schemas/PackageTemplateSwagger"),
     *       )
     *     )
     *   ),
     *   security={
     *     {"oauth": {}}
     *   }
     * )
     */
    public function index();

    /**
     * @OA\Post(
     *   path="/package-templates",
     *   summary="add new package",
     *   tags={"Packages"},
     *   @OA\RequestBody(
     *     @OA\JsonContent(
     *       ref="#/components/schemas/PackageTemplateSwagger",
     *     ),
     *   ),
     *   @OA\Response(
     *     response=200,
     *     description="Success",
     *     @OA\JsonContent(
     *       @OA\Property(
     *         property="data",
     *         ref="#/components/schemas/PackageTemplateSwagger",
     *       )
     *     )
     *   ),
     *   security={
     *     {"oauth": {}}
     *   }
     * )
     */
    public function create(Request $request);

    /**
     * @OA\Patch(
     *   path="/package-templates/id:{id}",
     *   summary="update package",
     *   tags={"Packages"},
     *   @OA\Parameter(
     *     name="id",
     *     in="path",
     *     required=true,
     *     @OA\Schema(
     *       type="integer",
     *     )
     *   ),
     *   @OA\RequestBody(
     *     @OA\JsonContent(
     *       ref="#/components/schemas/PackageTemplateSwagger",
     *     ),
     *   ),
     *   @OA\Response(
     *     response=200,
     *     description="Success",
     *     @OA\JsonContent(
     *       @OA\Property(
     *         property="data",
     *         ref="#/components/schemas/PackageTemplateSwagger",
     *       )
     *     )
     *   ),
     *   security={
     *     {"oauth": {}}
     *   }
     * )
     */
    public function update(PackageTemplate $package, Request $request);

    /**
     * @OA\Delete(
     *   path="/package-templates/id:{id}",
     *   summary="delete package",
     *   tags={"Packages"},
     *   @OA\Parameter(
     *     name="id",
     *     in="path",
     *     required=true,
     *     @OA\Schema(
     *       type="integer",
     *     )
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
    public function delete(PackageTemplate $package);
}
