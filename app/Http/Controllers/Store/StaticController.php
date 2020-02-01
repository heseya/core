<?php

namespace App\Http\Controllers\Store;

use App\Brand;
use App\Category;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use App\Http\Resources\BrandResource;
use App\Http\Resources\CategoryResource;
use Illuminate\Http\Resources\Json\ResourceCollection;

class StaticController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json([
            'name' => config('app.name'),
            'store_url' => config('app.store_url'),
        ]);
    }

    public function categories(): ResourceCollection
    {
        return CategoryResource::collection(
            Category::where(['public' => 1])->get()
        );
    }

    public function brands(): ResourceCollection
    {
        return BrandResource::collection(
            Brand::where(['public' => 1])->get()
        );
    }
}
