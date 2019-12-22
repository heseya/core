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

    public function view(Item $item)
    {
        return response()->view('admin/items/view', [
            'user' => Auth::user(),
            'item' => $item,
        ]);
    }

    public function createForm()
    {
        return response()->view('admin/items/create', [
            'user' => Auth::user(),
            'categories' => Category::all(),
        ]);
    }

    public function create(Request $request)
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
