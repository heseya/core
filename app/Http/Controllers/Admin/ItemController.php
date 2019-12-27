<?php

namespace App\Http\Controllers\Admin;

use Auth;
use App\Item;
use App\Category;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use App\Http\Controllers\Controller;

class ItemController extends Controller
{
    public function index()
    {
        $items = Item::orderBy('symbol')->paginate(20);

        return view('admin/items/index', [
            'items' => $items,
        ]);
    }

    public function view(Item $item)
    {
        return view('admin/items/view', [
            'item' => $item,
        ]);
    }

    public function createForm()
    {
        return view('admin/items/form', [
            'categories' => Category::all(),
        ]);
    }

    public function create(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'symbol' => 'required|string|unique:items',
        ]);

        $item = Item::create($request->all());

        if (isset($request->photos[0]) && $request->photos[0] !== null) {
            $item->photo()->associate($request->photos[0])->save();
        }

        return redirect('/admin/items/' . $item->id);
    }

    public function updateForm(Item $item)
    {
        return view('admin/items/form', [
            'item' => $item,
            'categories' => Category::all(),
        ]);
    }

    public function update(Item $item, Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'symbol' => [
                'required',
                'string',
                Rule::unique('items')->ignore($item->symbol, 'symbol'),
            ],
        ]);

        $item->update($request->all());

        if (isset($request->photos[0]) && $request->photos[0] !== null) {
            $item->photo()->associate($request->photos[0])->save();
        }

        return redirect('/admin/items/' . $item->id);
    }

    public function delete(Item $item)
    {
        $item->delete();

        return redirect('/admin/items');
    }
}
