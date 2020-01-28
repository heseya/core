<?php

namespace App\Http\Controllers\Store;

use App\Brand;
use App\Category;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;

class StaticController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json([
            'name' => config('app.name'),
            'store_url' => config('app.store_url'),
        ]);
    }

    public function categories(): JsonResponse
    {
        return response()->json(Category::where(['public' => 1])->get());
    }

    public function brands(): JsonResponse
    {
        return response()->json(Brand::where(['public' => 1])->get());
    }
}
