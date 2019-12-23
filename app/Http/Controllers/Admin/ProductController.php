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
        ])->paginate(20);

        return response()->view('admin/products/index', [
            'user' => Auth::user(),
            'products' => $products,
        ]);
    }

    public function view($slug)
    {
        $product = Product::where(['slug' => $slug])->with([
            'brand',
            'category',
            'gallery',
        ])->first();

        if (empty($product)) {
            abort(404);
        }

        return response()->view('admin/products/view', [
            'product' => $product,
            'user' => Auth::user(),
        ]);
    }

    public function createForm()
    {
        return response()->view('admin/products/create', [
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

    public function create(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'required|string|unique:products',
            'category_id' => 'required|integer',
            'price' => 'required',
            'vat' => 'required',
        ]);

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
