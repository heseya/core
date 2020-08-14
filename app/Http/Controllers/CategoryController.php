<?php

namespace App\Http\Controllers;

use App\Exceptions\Error;
use App\Http\Controllers\Swagger\CategoryControllerSwagger;
use App\Http\Resources\CategoryResource;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class CategoryController extends Controller implements CategoryControllerSwagger
{
    public function index(Request $request): JsonResource
    {
        $query = Category::search($request->all());

        if (!Auth::check()) {
            $query->where('public', true);
        }

        return CategoryResource::collection($query->get());
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'required|string|max:255|unique:categories|alpha_dash',
            'public' => 'boolean',
        ]);

        $category = Category::create($validated);

        return CategoryResource::make($category);
    }

    public function update(Category $category, Request $request): JsonResource
    {
        $validated = $request->validate([
            'name' => 'string|max:255',
            'public' => 'boolean',
            'slug' => [
                'required',
                'string',
                'max:255',
                'alpha_dash',
                Rule::unique('categories')->ignore($category->slug, 'slug'),
            ],
        ]);

        $category->update($validated);

        return CategoryResource::make($category);
    }

    public function destroy(Category $category)
    {
        if ($category->products()->count() > 0) {
            return Error::abort(
                "Category can't be deleted, because has relations.",
                409,
            );
        }

        $category->delete();

        return response()->json(null, 204);
    }
}
