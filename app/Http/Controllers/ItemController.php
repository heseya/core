<?php

namespace App\Http\Controllers;

use App\Events\ItemCreated;
use App\Events\ItemDeleted;
use App\Events\ItemUpdated;
use App\Http\Controllers\Swagger\ItemControllerSwagger;
use App\Http\Requests\ItemCreateRequest;
use App\Http\Requests\ItemIndexRequest;
use App\Http\Requests\ItemUpdateRequest;
use App\Http\Resources\ItemResource;
use App\Models\Item;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Config;

class ItemController extends Controller implements ItemControllerSwagger
{
    public function index(ItemIndexRequest $request): JsonResource
    {
        $items = Item::search($request->validated())
            ->sort($request->input('sort', 'sku'))
            ->with('deposits');

        return ItemResource::collection(
            $items->paginate(Config::get('pagination.per_page')),
        );
    }

    public function show(Item $item): JsonResource
    {
        return ItemResource::make($item);
    }

    public function store(ItemCreateRequest $request): JsonResource
    {
        $item = Item::create($request->validated());

        ItemCreated::dispatch($item);

        return ItemResource::make($item);
    }

    public function update(Item $item, ItemUpdateRequest $request): JsonResource
    {
        $item->update($request->validated());

        ItemUpdated::dispatch($item);

        return ItemResource::make($item);
    }

    public function destroy(Item $item): JsonResponse
    {
        if ($item->delete()) {
            ItemDeleted::dispatch($item);
        }

        return response()->json(null, 204);
    }
}
