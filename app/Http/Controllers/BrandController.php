<?php

namespace App\Http\Controllers;

use App\Exceptions\StoreException;
use App\Http\Controllers\Swagger\BrandControllerSwagger;
use App\Http\Requests\BrandCreateRequest;
use App\Http\Requests\BrandIndexRequest;
use App\Http\Requests\BrandOrderRequest;
use App\Http\Requests\BrandUpdateRequest;
use App\Http\Resources\BrandResource;
use App\Models\Brand;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Response;

class BrandController extends Controller implements BrandControllerSwagger
{
    public function index(BrandIndexRequest $request): JsonResource
    {
        $query = Brand::search($request->validated())->orderBy('order');

        if (!Auth::check()) {
            $query->where('public', true);
        }

        return BrandResource::collection($query->get());
    }

    public function store(BrandCreateRequest $request): JsonResource
    {
        $validated = $request->validated();
        $validated['order'] = Brand::count() + 1;

        $brand = Brand::create($validated);

        return BrandResource::make($brand);
    }

    public function update(Brand $brand, BrandUpdateRequest $request): JsonResource
    {
        $brand->update($request->validated());

        return BrandResource::make($brand);
    }

    public function order(BrandOrderRequest $request): JsonResponse
    {
        foreach ($request->input('brands') as $key => $id) {
            Brand::where('id', $id)->update(['order' => $key]);
        }

        return Response::json(null, 204);
    }

    public function destroy(Brand $brand): JsonResponse
    {
        if ($brand->products()->count() > 0) {
            throw new StoreException(__('admin.error.delete_with_relations'));
        }

        $brand->delete();

        return Response::json(null, 204);
    }
}
