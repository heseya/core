<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Swagger\ItemControllerSwagger;
use App\Http\Requests\ItemCreateRequest;
use App\Http\Requests\ItemIndexRequest;
use App\Http\Requests\ItemUpdateRequest;
use App\Http\Resources\ItemResource;
use App\Models\Item;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\JsonResource;

class ItemController extends Controller implements ItemControllerSwagger
{
    public function index(ItemIndexRequest $request): JsonResource
    {
        return ItemResource::collection(
            Item::search($request->validated())->paginate(12),
        );
    }

    public function show(Item $item): JsonResource
    {
        return ItemResource::make($item);
    }

    public function store(ItemCreateRequest $request): JsonResource
    {
        $item = Item::create($request->validated());

        return ItemResource::make($item);
    }

    public function update(Item $item, ItemUpdateRequest $request): JsonResource
    {
        $item->update($request->validated());

        return ItemResource::make($item);
    }

    public function destroy(Item $item): JsonResponse
    {
        $item->delete();

        return response()->json(null, 204);
    }
}
