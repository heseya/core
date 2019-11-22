<?php

namespace App\Http\Controllers\Store;

use App\Brand;
use App\Category;
use App\Http\Controllers\Controller;

class StaticController extends Controller
{
    public function index()
    {
        return response()->json([
            'name' => config('app.name'),
            'store_url' => config('app.store_url'),
        ]);
    }

    public function categories()
    {
        return response()->json(Category::where(['public' => 1])->get());
    }

    public function brands()
    {
        return response()->json(Brand::where(['public' => 1])->get());
    }
}
