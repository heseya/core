<?php

namespace App\Http\Controllers\Admin;

use Auth;
use App\Tax;
use App\Item;
use App\Brand;
use Parsedown;
use App\Product;
use App\Category;
use App\ProductSchema;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;

class ProductController extends Controller
{
    public function index()
    {
        $products = Product::paginate(16);

        return view('admin.products.index', [
            'products' => $products,
        ]);
    }

    public function view(Product $product)
    {
        return view('admin.products.view', [
            'product' => $product,
        ]);
    }

    public function createForm()
    {
        return view('admin.products.form', [
            'brands' => Brand::all(),
            'categories' => Category::all(),
            'taxes' => Tax::all(),
            'items' => Item::all(),
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
        
        DB::transaction(function () use ($request) {
            $product = Product::create($request->all());

            if (!$product->digital) {
                $schemas = 0;
                for ($i = 0; $i < $request->input('schemas', 0); $i++) {
                    if ($request->filled("schema-$i-name")) {
                        $request->validate([
                            "schema-$i-name" => 'string|max:255',
                            "schema-$i-type" => 'required|integer',
                            "schema-$i-required" => 'required|boolean',
                        ]);
    
                        $schema = $product->schemas()->create([
                            'name' => $request->input("schema-$i-name"),
                            'type' => $request->input("schema-$i-type"),
                            'required' => $request->boolean("schema-$i-required"),
                        ]);
    
                        for ($j = 0; $j < $request->input("schema-$i-items", 0); $j++) {
                            if ($request->filled("schema-$i-item-$j-id")) {
                                $request->validate([
                                    "schema-$i-item-$j-id" => 'integer',
                                    "schema-$i-item-$j-price" => 'numeric',
                                ]);        
                                
                                $schema->schemaItems()->updateOrCreate([
                                    'item_id' => $request->input("schema-$i-item-$j-id"),
                                    'extra_price' => $request->input("schema-$i-item-$j-price", 0),
                                ]);
                            }
                        }
    
                        $schemas++;
                    }
                }
    
                if ($schemas == 0) {
                    $schema = $product->schemas()->create([
                        'required' => true,
                    ]);
                    $item = Item::create($request->all());
                    $schemaItem = $schema->schemaItems()->create([
                        'extra_price' => 0,
                        'item_id' => $item->id,
                    ]);
    
                    if (isset($request->photos[0]) && $request->photos[0] !== null) {
                        $item->photo()->associate($request->photos[0])->save();
                    }
                }
            }

            foreach ($request->photos as $photo) {
                if ($photo !== null) {
                    $product->gallery()->attach($photo, [
                        'media_type' => 'photo'
                    ]);
                }
            }
        });

        return redirect()->route('products.view', $request->slug);
    }

    public function updateForm(Product $product)
    {
        return view('admin.products.form', [
            'product' => $product,
            'brands' => Brand::all(),
            'categories' => Category::all(),
            'taxes' => Tax::all(),
            'items' => Item::all(),
            'schemas' => $product->schemas,
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

        if (!$product->digital) {
            foreach ($product->schemas as $schema) {
                if ($schema->name == NULL) {
                    continue;
                }
    
                if ($request->filled('schema-old-' . $schema->id . '-name')) {
                    $request->validate([
                        'schema-old-' . $schema->id . '-name' => 'string|max:255',
                        'schema-old-' . $schema->id . '-type' => 'required|integer',
                        'schema-old-' . $schema->id . '-required' => 'required|boolean',
                    ]);
    
                    $schema->update([
                        'name' => $request->input('schema-old-' . $schema->id . '-name'),
                        'type' => $request->input('schema-old-' . $schema->id . '-type'),
                        'required' => $request->boolean('schema-old-' . $schema->id . '-required'),
                    ]);
    
                    if ($request->input('schema-old-' . $schema->id . '-type') == 0) {
                        foreach ($schema->schemaItems as $schemaItem) {
                            if ($request->filled('schema-old-' . $schema->id . '-item-' . $schemaItem->id . '-id')) {
                                $request->validate([
                                    'schema-old-' . $schema->id . '-item-' . $schemaItem->id . '-id' => 'integer',
                                    'schema-old-' . $schema->id . '-item-' . $schemaItem->id . '-price' => 'numeric',
                                ]);

                                $item_id = $request->input('schema-old-' . $schema->id . '-item-' . $schemaItem->id . '-id');
                                $extra_price = $request->input('schema-old-' . $schema->id . '-item-' . $schemaItem->id . '-price', 0);

                                if ($schemaItem->item_id != $item_id && $schemaItem->orderItem()->exists()) {
                                    $schemaItem->delete();
                                    $schema->schemaItems()->create([
                                        'item_id' => $item_id,
                                        'extra_price' => $extra_price,
                                    ]);
                                } else if ($schemaItem->item_id != $item_id || $schemaItem->extra_price != $extra_price) {
                                    $oldItem = $schema->schemaItems()->onlyTrashed()->where('item_id', $item_id);
                                    if ($oldItem->exists()) {
                                        $schemaItem->forceDelete();
                                        $oldItem->restore();
                                        $schemaItem = $oldItem;
                                    }

                                    $schemaItem->update([
                                        'item_id' => $item_id,
                                        'extra_price' => $extra_price,
                                    ]);
                                }
                            } else {
                                $schemaItem->orderItem()->exists() ? $schemaItem->delete() : $schemaItem->forceDelete();
                            }
                        }
        
                        for ($i = 0; $i < $request->input('schema-old-' . $schema->id . '-items', 0); $i++) {
                            if ($request->filled('schema-old-' . $schema->id . "-item-new-$i-id")) {
                                $request->validate([
                                    'schema-old-' . $schema->id . "-item-new-$i-id" => 'integer',
                                    'schema-old-' . $schema->id . "-item-new-$i-price" => 'numeric',
                                ]);

                                $item_id = $request->input('schema-old-' . $schema->id . "-item-new-$i-id");
                                $extra_price = $request->input('schema-old-' . $schema->id . "-item-new-$i-price", 0);
                                $oldItem = $schema->schemaItems()->withTrashed()->where('item_id', $item_id);

                                if ($oldItem->exists()) {
                                    $oldItem->restore();

                                    $oldItem->update([
                                        'extra_price' => $extra_price,
                                    ]);
                                } else {
                                    $schema->schemaItems()->create([
                                        'item_id' => $item_id,
                                        'extra_price' => $extra_price,
                                    ]);
                                }
                            }
                        }
                    } else {
                        foreach ($schema->schemaItems as $schemaItem) {
                            $schemaItem->orderItem()->exists() ? $schemaItem->delete() : $schemaItem->forceDelete();
                        }
                    }
                } else {
                    foreach ($schema->schemaItems as $schemaItem) {
                        $schemaItem->orderItem()->exists() ? $schemaItem->delete() : $schemaItem->forceDelete();
                    }
                    
                    $schema->schemaItems()->withTrashed()->exists() ? $schema->delete() : $schema->forceDelete();
                }
            }

            DB::transaction(function () use ($request, $product) {
                for ($i = 0; $i < $request->input('schemas', 0); $i++) {
                    if ($request->filled("schema-$i-name")) {
                        $request->validate([
                            "schema-$i-name" => 'string|max:255',
                            "schema-$i-type" => 'required|integer',
                            "schema-$i-required" => 'required|boolean',
                        ]);
    
                        $schema = $product->schemas()->create([
                            'name' => $request->input("schema-$i-name"),
                            'type' => $request->input("schema-$i-type"),
                            'required' => $request->boolean("schema-$i-required"),
                        ]);
    
                        for ($j = 0; $j < $request->input("schema-$i-items", 0); $j++) {
                            if ($request->filled("schema-$i-item-$j-id")) {
                                $request->validate([
                                    "schema-$i-item-$j-id" => 'integer',
                                    "schema-$i-item-$j-price" => 'numeric',
                                ]);        
                                
                                $schema->schemaItems()->updateOrCreate([
                                    'item_id' => $request->input("schema-$i-item-$j-id"),
                                    'extra_price' => $request->input("schema-$i-item-$j-price", 0),
                                ]);
                            }
                        }
                    }
                }
            });
        } else {
            foreach ($product->schemas as $schema) {
                foreach ($schema->schemaItems as $schemaItem) {
                    $schemaItem->orderItem()->exists() ? $schemaItem->delete() : $schemaItem->forceDelete();
                }
                
                $schema->schemaItems()->withTrashed()->exists() ? $schema->delete() : $schema->forceDelete();
            }
        }

        foreach ($request->photos as $photo) {
            if ($photo !== null) {
                $product->gallery()->attach($photo, [
                    'media_type' => 'photo'
                ]);
            }
        }

        return redirect()->route('products.view', $product->slug);
    }

    public function delete(Product $product)
    {
        foreach ($product->schemas as $schema) {
            foreach ($schema->schemaItems as $schemaItem) {
                $schemaItem->orderItem()->exists() ? $schemaItem->delete() : $schemaItem->forceDelete();
            }
            
            $schema->schemaItems()->withTrashed()->exists() ? $schema->delete() : $schema->forceDelete();
        }

        $product->schemas()->withTrashed()->exists() ? $product->delete() : $product->forceDelete();

        return redirect()->route('products');
    }
}
