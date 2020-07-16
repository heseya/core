<?php

namespace App\Http\Controllers;

use App\Exceptions\Error;
use App\Http\Controllers\Swagger\BrandControllerSwagger;
use App\Http\Resources\BrandResource;
use App\Models\Brand;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class BrandController extends Controller implements BrandControllerSwagger
{
    public function index(): JsonResource
    {
        $query = Brand::select();

        if (!Auth::check()) {
            $query->where('public', true);
        }

        return BrandResource::collection($query->get());
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'required|string|max:255|unique:brands|alpha_dash',
            'public' => 'boolean',
        ]);

        $brand = Brand::create($validated);

        return BrandResource::make($brand)
            ->response()
            ->setStatusCode(201);
    }

    public function update(Brand $brand, Request $request): JsonResource
    {
        $validated = $request->validate([
            'name' => 'string|max:255',
            'public' => 'boolean',
            'slug' => [
                'required',
                'string',
                'max:255',
                'alpha_dash',
                Rule::unique('brands')->ignore($brand->slug, 'slug'),
            ],
        ]);

        $brand->update($validated);

        return BrandResource::make($brand);
    }

    public function destroy(Brand $brand)
    {
        if ($brand->products()->count() > 0) {
            return Error::abort(
                "Brand can't be deleted, because has relations.",
                400,
            );
        }

        $brand->delete();

        return response()->json(null, 204);
    }
}
