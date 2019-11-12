<?php

namespace App\Http\Controllers\Admin;

use Auth;
use App\Brand;
use App\Photo;
use App\Product;
use App\Category;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class ProductController extends Controller
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
        ])->with([
            'brand',
            'category',
            'photos',
        ])->get();

        return response()->view('admin/products/list', [
            'user' => Auth::user(),
            'products' => $products,
        ]);
    }

    public function productJson(Product $product)
    {
        $product->brand;
        $product->category;

        return response()->json($product);
    }

    public function product(Product $product)
    {
        return response()->view('admin/products/single', [
            'product' => $product,
            'user' => Auth::user(),
        ]);
    }

    public function productsAdd()
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

    public function productsStore(Request $request)
    {
        $product = Product::create($request->all());

        foreach ($request->photos as $photo) {
            if ($photo !== null) {
                $product->photos()->attach(Photo::create([
                    'url' => $photo,
                ]));
            }
        }

        return redirect('/admin/products/' . $product->id);
    }
}
