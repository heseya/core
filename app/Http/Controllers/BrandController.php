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
use App\Models\ProductSet;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Response;

class BrandController extends Controller implements BrandControllerSwagger
{
    public function index(BrandIndexRequest $request): JsonResource
    {
        $query = ProductSet::where('slug', 'brands')->search($request->validated())->orderBy('order');

        if (!Auth::check()) {
            $query->where('public', true);
        }

        return BrandResource::collection($query->get());
    }
}
