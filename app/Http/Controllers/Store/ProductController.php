<?php

namespace App\Http\Controllers\Store;

use App\Product;
use App\Http\Controllers\Controller;

class ProductController extends Controller
{
    public function index()
    {
        $products = Product::select([
            'id',
            'name',
            'slug',
            'price',
            'brand_id',
            'category_id',
        ])->with([
            'brand',
            'category',
            'gallery',
        ])->paginate(20);

        return response()->json($products);
    }

    public function single($slug)
    {
        $product = Product::where(['slug' => $slug])->with([
            'brand',
            'category',
        ])->first();

        if (empty($product)) {
            abort(404);
        }

        $product->gallery;

        return response()->json($product);
    }
}
