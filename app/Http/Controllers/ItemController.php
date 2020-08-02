<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Swagger\ItemControllerSwagger;
use App\Http\Resources\ItemResource;
use App\Models\Item;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Validation\Rule;

class ItemController extends Controller implements ItemControllerSwagger
{
    public function index(): JsonResource
    {
        return ItemResource::collection(
            Item::paginate(12),
        );
    }

    public function show(Item $item): JsonResource
    {
        return ItemResource::make($item);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'sku' => 'string|unique:items|max:255|nullable'
        ]);

        $item = Item::create($validated);

        return ItemResource::make($item);
    }

    public function update(Item $item, Request $request): JsonResource
    {
        $validated = $request->validate([
            'name' => 'string|max:255',
            'sku' => [
                'string',
                'max:255',
                Rule::unique('items')->ignore($item->sku, 'sku'),
            ]
        ]);

        $item->update($validated);

        return ItemResource::make($item);
    }

    public function destroy(Item $item)
    {
        $item->delete();

        return response()->json(null, 204);
    }
}
