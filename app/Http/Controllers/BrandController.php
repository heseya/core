<?php

namespace App\Http\Controllers;

use App\Brand;
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
            Brand::where(['public' => 1])->get()
        );
    }
}
