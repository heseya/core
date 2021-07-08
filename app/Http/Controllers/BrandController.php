<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Swagger\BrandControllerSwagger;
use App\Http\Requests\BrandIndexRequest;
use App\Http\Resources\BrandResource;
use App\Models\ProductSet;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Auth;

class BrandController extends Controller implements BrandControllerSwagger
{
    /**
     * @deprecated
     */
    public function index(BrandIndexRequest $request): JsonResource
    {
        $query = ProductSet::where('slug', 'brands')->search($request->validated())->orderBy('order');

        if (!Auth::check()) {
            $query->where('public', true);
        }

        return BrandResource::collection($query->get());
    }
}


#!/bin/sh

composer style
docker exec -it store-api_app_1 composer style