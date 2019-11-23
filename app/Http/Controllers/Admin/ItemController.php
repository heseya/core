<?php

namespace App\Http\Controllers\Admin;

use Auth;
use App\Item;
use App\Category;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class ItemController extends Controller
{
    public function index()
    {
        $items = Item::orderBy('symbol')->get();

        return response()->view('admin/items/index', [
            'user' => Auth::user(),
            'items' => $items,
        ]);
    }

    public function single(Item $item)
    {
        return response()->view('admin/items/single', [
            'user' => Auth::user(),
            'item' => $item,
        ]);
    }

    public function addForm()
    {
        return response()->view('admin/items/add', [
            'user' => Auth::user(),
            'categories' => Category::all(),
        ]);
    }

    public function store(Request $request)
    {
        $product = Item::create($request->all());
        $product->photo()->associate($request->photos[0])->save();

        return redirect('/admin/items/' . $product->id);
    }

    public function delete(Item $item)
    {
        $item->delete();

        return redirect('/admin/items');
    }
}