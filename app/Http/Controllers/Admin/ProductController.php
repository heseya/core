<?php

namespace App\Http\Controllers\Admin;

use Auth;
use App\Tax;
use App\Brand;
use App\Product;
use App\Category;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
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
            'products' => $products,
        ]);
    }

    public function view(Product $product)
    {
        return response()->view('admin/products/view', [
            'product' => $product,
        ]);
    }

    public function createForm()
    {
        return response()->view('admin/products/form', [
            'brands' => Brand::all(),
            'categories' => Category::all(),
            'taxes' => Tax::all(),
        ]);
    }

    public function create(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'required|string|unique:products|regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/',
            'category_id' => 'required|integer',
            'price' => 'required',
        ]);

        $product = Product::create($request->all());

        foreach ($request->photos as $photo) {
            if ($photo !== null) {
                $product->gallery()->attach($photo, [
                    'media_type' => 'photo'
                ]);
            }
        }

        return redirect('/admin/products/' . $product->slug);
    }

    public function updateForm(Product $product)
    {
        return response()->view('admin/products/form', [
            'product' => $product,
            'brands' => Brand::all(),
            'categories' => Category::all(),
            'taxes' => Tax::all(),
        ]);
    }

    public function update(Product $product, Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'category_id' => 'required|integer',
            'price' => 'required',
            'slug' => [
                'required',
                'string',
                'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/',
                Rule::unique('products')->ignore($product->slug, 'slug'),
            ],
        ]);

        $product->update($request->all());

        foreach ($request->photos as $photo) {
            if ($photo !== null) {
                $product->gallery()->attach($photo, [
                    'media_type' => 'photo'
                ]);
            }
        }

        return redirect('/admin/products/' . $product->id);
    }

    public function delete(Product $product)
    {
        $product->delete();

        return redirect('/admin/products');
    }
}
