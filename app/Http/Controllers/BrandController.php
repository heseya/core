<?php

namespace App\Http\Controllers;

use App\Exceptions\Error;
use App\Http\Controllers\Swagger\BrandControllerSwagger;
use App\Http\Requests\BrandCreateRequest;
use App\Http\Requests\BrandIndexRequest;
use App\Http\Requests\BrandUpdateRequest;
use App\Http\Resources\BrandResource;
use App\Models\Brand;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Auth;

class BrandController extends Controller implements BrandControllerSwagger
{
    public function index(BrandIndexRequest $request): JsonResource
    {
        $query = Brand::search($request->validated());

        if (!Auth::check()) {
            $query->where('public', true);
        }

        return BrandResource::collection($query->get());
    }

    public function store(BrandCreateRequest $request): JsonResource
    {
        $brand = Brand::create($request->validated());

        return BrandResource::make($brand);
    }

    public function update(Brand $brand, BrandUpdateRequest $request): JsonResource
    {
        $brand->update($request->validated());

        return BrandResource::make($brand);
    }

    public function destroy(Brand $brand): JsonResponse
    {
        if ($brand->products()->count() > 0) {
            return Error::abort(
                'Brand can\'t be deleted, because has relations.',
                409,
            );
        }

        $brand->delete();

        return response()->json(null, 204);
    }
}
