<?php

namespace App\Http\Controllers;

use App\Error;
use App\Category;
use Illuminate\Http\Request;
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

    /**
     * @OA\Post(
     *   path="/categories",
     *   summary="add new category",
     *   tags={"Categories"},
     *   @OA\RequestBody(
     *     @OA\JsonContent(
     *       ref="#/components/schemas/Category",
     *     ),
     *   ),
     *   @OA\Response(
     *     response=200,
     *     description="Success",
     *     @OA\JsonContent(
     *       @OA\Property(
     *         property="data",
     *         ref="#/components/schemas/Category",
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

        $category = Category::create($request->all());

        return (new CategoryResource($category))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * @OA\Patch(
     *   path="/categories/id:{id}",
     *   summary="update category",
     *   tags={"Categories"},
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
     *       ref="#/components/schemas/Category",
     *     ),
     *   ),
     *   @OA\Response(
     *     response=200,
     *     description="Success",
     *     @OA\JsonContent(
     *       @OA\Property(
     *         property="data",
     *         ref="#/components/schemas/Category",
     *       )
     *     )
     *   ),
     *   security={
     *     {"oauth": {}}
     *   }
     * )
     */
    public function update(Category $category, Request $request)
    {
        $request->validate([
            'name' => 'string|max:255',
            'price' => 'string|max:255',
            'public' => 'boolean',
        ]);

        $category->update($request->all());

        return new CategoryResource($category);
    }

    /**
     * @OA\Delete(
     *   path="/categories/id:{id}",
     *   summary="delete category",
     *   tags={"Categories"},
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
    public function delete(Category $category)
    {
        if ($category->products()->count() > 0) {
            return Error::abort("Category can't be deleted, because has relations.",
                400,
            );
        }

        $category->delete();

        return response()->json(null, 204);
    }
}
