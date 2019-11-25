<?php

namespace App\Http\Controllers\Store;

use App\Product;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        $request->validate([
            'brand' => 'integer',
            'category' => 'integer',
        ]);

        $query = Product::select([
            'id',
            'name',
            'slug',
            'price',
            'brand_id',
            'category_id',
        ])->with([
            'brand',
            'category',
        ])->with(['gallery' => function ($q) {
            $q->first();
        }]);

        if ($request->brand) {
            $query->where('brand_id', $request->brand);
        }

        if ($request->category) {
            $query->where('category_id', $request->category);
        }

        return response()->json($query->paginate(20));
    }

    public function single($slug)
    {
        $product = Product::where(['slug' => $slug])->with([
            'brand',
            'category',
            'gallery',
            'shema',
        ])->first();

        if (empty($product)) {
            abort(404);
        }

        return response()->json($product);
    }
}
