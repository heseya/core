<?php

namespace App\Http\Controllers;

use App\Brand;
use Illuminate\Http\Request;
use App\Http\Resources\BrandResource;
use Illuminate\Http\Resources\Json\ResourceCollection;

class BrandController extends Controller
{
    /**
     * @OA\Get(
     *   path="/brands",
     *   summary="list brands",
     *   tags={"Brands"},
     *   @OA\Response(
     *     response=200,
     *     description="Success",
     *     @OA\JsonContent(
     *       @OA\Property(
     *         property="data",
     *         type="array",
     *         @OA\Items(ref="#/components/schemas/Brand"),
     *       )
     *     )
     *   )
     * )
     */
    public function index(): ResourceCollection
    {
        return BrandResource::collection(
            Brand::where(['public' => true])->get()
        );
    }

    /**
     * @OA\Post(
     *   path="/brands",
     *   summary="add new brand",
     *   tags={"Brands"},
     *   @OA\RequestBody(
     *     @OA\JsonContent(
     *       ref="#/components/schemas/Brand",
     *     ),
     *   ),
     *   @OA\Response(
     *     response=200,
     *     description="Success",
     *     @OA\JsonContent(
     *       @OA\Property(
     *         property="data",
     *         ref="#/components/schemas/Brand",
     *       )
     *     )
     *   ),
     *   security={
     *     {"oauth": {}}
     *   }
     * )
     */
    public function create(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'required|string|max:255',
            'public' => 'boolean',
        ]);

        $brand = Brand::create($request->all());

        return (new BrandResource($brand))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * @OA\Patch(
     *   path="/brands/id:{id}",
     *   summary="update brand",
     *   tags={"Brands"},
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
     *       ref="#/components/schemas/Brand",
     *     ),
     *   ),
     *   @OA\Response(
     *     response=200,
     *     description="Success",
     *     @OA\JsonContent(
     *       @OA\Property(
     *         property="data",
     *         ref="#/components/schemas/Brand",
     *       )
     *     )
     *   ),
     *   security={
     *     {"oauth": {}}
     *   }
     * )
     */
    public function update(Brand $brand, Request $request)
    {
        $request->validate([
            'name' => 'string|max:255',
            'price' => 'string|max:255',
            'public' => 'boolean',
        ]);

        $brand->update($request->all());

        return new BrandResource($brand);
    }

    /**
     * @OA\Delete(
     *   path="/brands/id:{id}",
     *   summary="delete brand",
     *   tags={"Brands"},
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
    public function delete(Brand $brand)
    {
        $brand->delete();

        return response()->json(null, 204);
    }
}
