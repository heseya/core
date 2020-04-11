<?php

namespace App\Http\Controllers;

use App\Category;
use App\Http\Resources\CategoryResource;
use Illuminate\Http\Resources\Json\ResourceCollection;

class CategoryController extends Controller
{
    /**
     * @OA\Get(
     *   path="/categories",
     *   summary="list categories",
     *   tags={"Categories"},
     *   @OA\Response(
     *     response=200,
     *     description="Success",
     *     @OA\JsonContent(
     *       @OA\Property(
     *         property="data",
     *         type="array",
     *         @OA\Items(ref="#/components/schemas/Category"),
     *       )
     *     )
     *   )
     * )
     */
    public function index(): ResourceCollection
    {
        return CategoryResource::collection(
            Category::where(['public' => true])->get()
        );
    }
}
