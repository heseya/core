<?php

namespace App\Http\Controllers;

use App\Exceptions\StoreException;
use App\Http\Controllers\Swagger\CategoryControllerSwagger;
use App\Http\Requests\CategoryCreateRequest;
use App\Http\Requests\CategoryIndexRequest;
use App\Http\Requests\CategoryOrderRequest;
use App\Http\Requests\CategoryUpdateRequest;
use App\Http\Resources\CategoryResource;
use App\Models\Category;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Response;

class CategoryController extends Controller implements CategoryControllerSwagger
{
    public function index(CategoryIndexRequest $request): JsonResource
    {
        $query = Category::search($request->validated())->orderBy('order');

        if (!Auth::check()) {
            $query->where('public', true);
        }

        return CategoryResource::collection($query->get());
    }

    public function store(CategoryCreateRequest $request): JsonResource
    {
        $validated = $request->validated();
        $validated['order'] = Category::count() + 1;

        $category = Category::create($validated);

        return CategoryResource::make($category);
    }

    public function update(Category $category, CategoryUpdateRequest $request): JsonResource
    {
        $category->update($request->validated());

        return CategoryResource::make($category);
    }

    public function order(CategoryOrderRequest $request): JsonResponse
    {
        foreach ($request->input('categories') as $key => $id) {
            Category::where('id', $id)->update(['order' => $key]);
        }

        return Response::json(null, 204);
    }

    public function destroy(Category $category): JsonResponse
    {
        if ($category->products()->count() > 0) {
            throw new StoreException(__('admin.error.delete_with_relations'));
        }

        $category->delete();

        return Response::json(null, 204);
    }
}
