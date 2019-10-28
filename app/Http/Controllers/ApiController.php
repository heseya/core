<?php

namespace App\Http\Controllers;

use App\Product;
use App\Http\Controllers\Controller;

class ApiController extends Controller
{
    public function products()
    {
        $products = Product::select([
            'id',
            'name',
            'price',
            'color',
            'brand_id',
            'category_id',
        ])
        ->with([
            'brand',
            'category',
        ])
        ->get();

        return response()->json($products);
    }

    public function product(Product $product)
    {
        $product->brand;
        $product->category;

        return response()->json($product);
    }
}
