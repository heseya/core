<?php

namespace App\Http\Controllers\Admin;

use Auth;
use App\Brand;
use App\Product;
use App\Category;
use Illuminate\Http\Request;
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
        ])->get();

        return response()->view('admin/products/index', [
            'user' => Auth::user(),
            'products' => $products,
        ]);
    }

    public function single($slug)
    {
        $product = Product::where(['slug' => $slug])->with([
            'brand',
            'category',
            'gallery',
        ])->first();

        if (empty($product)) {
            abort(404);
        }

        return response()->view('admin/products/single', [
            'product' => $product,
            'user' => Auth::user(),
        ]);
    }

    public function addForm()
    {
        return response()->view('admin/products/add', [
            'user' => Auth::user(),
            'brands' => Brand::all(),
            'categories' => Category::all(),
            'taxes' => [
                [
                    'id' => 0,
                    'name' => '23%',
                ],
                [
                    'id' => 1,
                    'name' => '8%',
                ],
            ],
        ]);
    }

    public function store(Request $request)
    {
        $product = Product::create($request->all());

        foreach ($request->photos as $photo) {
            if ($photo !== null) {
                $product->photos()->attach($photo);
            }
        }

        return redirect('/admin/products/' . $product->slug);
    }

    public function delete(Product $product)
    {
        $product->delete();

        return redirect('/admin/products');
    }
}
