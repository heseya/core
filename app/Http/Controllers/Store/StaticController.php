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
            'developer' => 'heseya.com',
            'created_with' => '❤️'
        ]);
    }

    public function categories()
    {
        return response()->json(Category::all());
    }

    public function brands()
    {
        return response()->json(Brand::all());
    }
}
